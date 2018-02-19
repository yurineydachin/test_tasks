package catalog_service

import (
	"context"
	"fmt"

	"mobile_search_api/srv/installment"
	"mobile_search_api/srv/location"
	"mobile_search_api/srv/model"
	"mobile_search_api/srv/product"
	"mobile_search_api/srv/search"
	"mobile_search_api/srv/search_opts"

	"godep.lzd.co/go-i18n"

	"mobile_search_api/api/core/utils"
	"mobile_search_api/api/ext_services/content_api/model/search"

	"github.com/sergei-svistunov/gorpc/transport/cache"
	"godep.lzd.co/mobapi_lib/logger"
	"godep.lzd.co/mobapi_lib/token"
)

const (
	maxCountSearchSuggestionKeywords = 3
)

var sortKinds = []string{
	"popularity",
	search_opts.SortByPrice,
	"name",
	"brand",
	search_opts.SortByDeliveryTime,
}
var sortDetails = []SortDetail{
	{Sort: "popularity", Name: "Relevance"},
	{Sort: "discountspecial", Name: "Discount"},
	{Sort: "priceasc", Name: "Price: Low to High"},
	{Sort: "pricedesc", Name: "Price: High to Low"},
	{Sort: "ratingdesc", Name: "Product Rating"},
}
var viewKinds = []string{
	"list",
	"grid",
}

type CatalogResult struct {
	RequestDump          interface{}
	Models               []*model_service.ModelData
	TotalProducts        uint64
	SelectedFiltersCount uint64
	SearchContextIgnored bool
	Title                string
	DidYouMean           *SearchSuggestions
	SearchInfo           *SearchResultInfo
	ShowDeliveryPanel    bool
	LocationPanel        *DeliveryLocation
	Products             []product_service.Product
	Filters              []search.OneFilter
	Sort                 []string
	SortDetails          []SortDetail
	View                 []string
	TopSticker           *product_service.ProductSticker
	FDHighlights         string
	TrackingData         *search_service.TrackingData
}

type SortDetail struct {
	Sort string
	Name string
}

type SearchSuggestions struct {
	Key           string
	Title         string
	TotalProducts uint64
	Suggestions   []Suggestion
}

type Suggestion struct {
	Title string
}

type DeliveryLocation struct {
	GroupID         int64
	GroupName       string
	ProvinceID      uint64
	ProvinceName    string
	DistrictID      uint64
	DistrictName    string
	SubdistrictID   uint64
	SubdistrictName string
	AreaID          uint64
	AreaName        string
	ZipCode         string
}

type SearchResultInfo struct {
	Title    SearchResultInfoText
	SubTitle *SearchResultInfoText
}

type SearchResultInfoText struct {
	Text      string
	Highlight string
}

type CatalogService struct {
	searchService      *search_service.SearchService
	productService     *product_service.ProductService
	modelService       *model_service.ModelService
	locationService    *location_service.LocationService
	installmentService *installment_service.InstallmentService
	i18nManager        *i18n.Manager
}

func NewCatalogService(searchService *search_service.SearchService, productService *product_service.ProductService, modelService *model_service.ModelService, locationService *location_service.LocationService, installmentService *installment_service.InstallmentService, i18nManager *i18n.Manager) *CatalogService {
	return &CatalogService{
		searchService:      searchService,
		productService:     productService,
		modelService:       modelService,
		locationService:    locationService,
		installmentService: installmentService,
		i18nManager:        i18nManager,
	}
}

func (service *CatalogService) Process(ctx context.Context, opts *search_opts.SearchOpts, apiToken token.INullToken) (*CatalogResult, error) {
	result := &CatalogResult{}

	location, fdHighlights, err := service.locationService.PrepareDeliveryLocation(cache.NewContextWithTransportCache(ctx), location_service.Opts{
		ProvinceID: opts.ProvinceID,
		AreaID:     opts.AreaID,
		ZipCode:    opts.ZipCode,
	}, apiToken)
	if err == location_service.Error.INVALID_ZIPCODE {
		return nil, err
	} else if err == nil && location != nil {
		if opts.Sort == search_opts.SortByDeliveryTime {
			result.ShowDeliveryPanel = true
		}
		if location.GroupID != 0 {
			opts.FastDeliveryLocationID = location.GroupID
		}
		if location.IsFilled {
			result.LocationPanel = &DeliveryLocation{
				GroupID:         location.GroupID,
				GroupName:       location.GroupName,
				ProvinceID:      location.ProvinceID,
				ProvinceName:    location.ProvinceName,
				DistrictID:      location.DistrictID,
				DistrictName:    location.DistrictName,
				SubdistrictID:   location.SubdistrictID,
				SubdistrictName: location.SubdistrictName,
				AreaID:          location.AreaID,
				AreaName:        location.AreaName,
				ZipCode:         location.ZipCode,
			}
		}
	}
	result.FDHighlights = fdHighlights

	if len(opts.SKUs) > 0 {
		products, err := service.productService.GetProducts(ctx, opts)
		if err != nil {
			return nil, err
		}
		result.Products = products
		result.TotalProducts = uint64(len(products))
		service.installmentService.FillProducts(ctx, result.Products)
		service.locationService.FillProducts(result.FDHighlights, result.Products)
		return result, nil
	}

	result.Sort = sortKinds
	result.SortDetails = make([]SortDetail, len(sortDetails))
	result.View = viewKinds
	dictionary := service.i18nManager.GetDictionary(opts.Lang)
	for i := range sortDetails {
		result.SortDetails[i].Sort = sortDetails[i].Sort
		result.SortDetails[i].Name, _ = dictionary.Translate(sortDetails[i].Name, nil, nil)
	}

	result.Models, err = service.modelService.GetResultModels(ctx, opts)
	if err != nil {
		logger.Notice(ctx, "Search model error: %v", err)
	}

	if opts.IsQueryPassed() {
		result.Title = opts.Query
	} else if len(result.Models) > 0 {
		result.Title = model_service.GetConcatedModelLabels(result.Models)
	}

	searchResponse, err := service.searchService.Search(ctx, opts, result.Models)
	if err != nil {
		return result, err
	}

	result.TrackingData = searchResponse.TrackingData
	result.RequestDump = searchResponse.RequestDump
	result.TotalProducts = searchResponse.TotalProducts
	result.SearchContextIgnored = searchResponse.SearchContextIgnored
	if len(searchResponse.SKUs) > 0 {
		opts.SKUs = searchResponse.SKUs
	}
	if len(searchResponse.Products) > 0 {
		result.Products = searchResponse.Products
		result.TopSticker = searchResponse.TopSticker
		service.installmentService.FillProducts(ctx, result.Products)
		service.locationService.FillProducts(result.FDHighlights, result.Products)
	}

	searchResponse.ProcessFilters()
	result.Filters = searchResponse.Filters
	result.SelectedFiltersCount = searchResponse.SelectedFiltersCount

	result.DidYouMean = convertDidYouMean(opts, searchResponse)
	if result.DidYouMean == nil {
		modelLabel := ""
		if len(result.Models) > 0 {
			modelLabel = result.Models[0].Label
		}
		service.fillSearchInfo(result, opts, searchResponse, modelLabel)
	}

	return result, nil
}

func (service *CatalogService) fillSearchInfo(result *CatalogResult, opts *search_opts.SearchOpts, searchResponse *search_service.SearchResult, modelLabel string) {
	dictionary := service.i18nManager.GetDictionary(opts.Lang)
	if searchResponse.SearchContextIgnored {
		result.SearchInfo = &SearchResultInfo{
			Title: SearchResultInfoText{}, SubTitle: &SearchResultInfoText{},
		}
		result.SearchInfo.Title.Text, _ = dictionary.Translate("We didn’t find results for “%s” in %s", nil, nil)
		if opts.IsQueryPassed() && modelLabel != "" {
			result.SearchInfo.Title.Text = fmt.Sprintf(result.SearchInfo.Title.Text, opts.Query, modelLabel)
		}
		result.SearchInfo.Title.Highlight = result.SearchInfo.Title.Text
		result.SearchInfo.SubTitle.Text, _ = dictionary.Translate("Here are %s results found from All Departments", nil, nil)
		result.SearchInfo.SubTitle.Text = fmt.Sprintf(result.SearchInfo.SubTitle.Text, utils.ToString(searchResponse.TotalProducts))
	} else if opts.IsQueryPassed() || opts.SearchContext != "" {
		result.SearchInfo = &SearchResultInfo{
			Title: SearchResultInfoText{},
		}
		if modelLabel != "" && (opts.IsCategoryInFilter() || opts.SearchContext != "") {
			result.SearchInfo.Title.Text, _ = dictionary.Translate("%s results found in %s", nil, nil)
			result.SearchInfo.Title.Text = fmt.Sprintf(result.SearchInfo.Title.Text, utils.ToString(searchResponse.TotalProducts), modelLabel)
			result.SearchInfo.Title.Highlight = modelLabel
		} else {
			result.SearchInfo.Title.Text, _ = dictionary.Translate("%s results found", nil, nil)
			result.SearchInfo.Title.Text = fmt.Sprintf(result.SearchInfo.Title.Text, utils.ToString(searchResponse.TotalProducts))
		}
	}
}

func convertDidYouMean(opts *search_opts.SearchOpts, searchResponse *search_service.SearchResult) *SearchSuggestions {
	if searchResponse == nil || len(searchResponse.DidYouMean) == 0 {
		return nil
	}
	result := &SearchSuggestions{
		Key:           opts.Query,
		Title:         searchResponse.DidYouMean[0].Text,
		TotalProducts: searchResponse.TotalProducts,
		Suggestions:   make([]Suggestion, len(searchResponse.DidYouMean)),
	}
	for i := range searchResponse.DidYouMean {
		result.Suggestions[i].Title = searchResponse.DidYouMean[i].Text
	}
	if len(result.Suggestions) > maxCountSearchSuggestionKeywords {
		result.Suggestions = result.Suggestions[0:maxCountSearchSuggestionKeywords]
	}
	return result
}
