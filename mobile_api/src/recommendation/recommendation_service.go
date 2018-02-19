package recommendation_service

import (
	"context"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/blang/semver"
	"godep.lzd.co/catalog_api_client_go/transfer"
	ctxmanager "godep.lzd.co/mobapi_lib/context"
	"godep.lzd.co/mobapi_lib/logger"
	"godep.lzd.co/mobapi_lib/token"
	"mobile_search_api/srv/product"
	"mobile_search_api/srv/search_opts"
)

const (
	ProviderRR          = "rich_relevance"
	ProviderDS          = "data_science"
	ProviderSP          = "sponsored_product"
	ProviderTB          = "taobao"
	ProviderTBWidget    = "taobao_by_widget"
	ProviderTBPlacement = "taobao_by_placement"
	ProviderNone        = "none"
	ProviderDefault     = ProviderDS

	TITLE_LANG_DELIMITER = "#"
)

type RecommendationOpts struct {
	Lang           string
	Endpoint       string
	EndpointBackup string
	AppID          string
	ClientID       string
	CustomerID     uint64
	AdID           string
	Venture        string
	Placements     []string

	SKU          string
	SimpleSKU    string
	IsTaobaoSKU  bool
	RegionalKeys []string
	BrandID      uint64
	SellerID     uint64

	Platform          string
	AppVersion        semver.Version
	PreferredProvider string
}

func (opts *RecommendationOpts) FillByParams(ctx context.Context, apiToken token.INullToken) {

	if apiToken != nil && !apiToken.IsGuest() {
		opts.CustomerID = apiToken.GetCustomerID()
	}

	opts.AdID = strconv.FormatUint(opts.CustomerID, 10)

	ctxData, err := ctxmanager.FromContext(ctx)
	if err == nil {
		if ctxData.ReqClientId != "" {
			opts.AdID = ctxData.ReqClientId
		}
	}
}

func (opts *RecommendationOpts) copyWithPlacements(placements []string) *RecommendationOpts {
	return &RecommendationOpts{
		Lang:           opts.Lang,
		Endpoint:       opts.Endpoint,
		EndpointBackup: opts.EndpointBackup,
		AppID:          opts.AppID,
		ClientID:       opts.ClientID,
		CustomerID:     opts.CustomerID,
		AdID:           opts.AdID,
		Venture:        opts.Venture,
		Placements:     placements,

		SKU:          opts.SKU,
		SimpleSKU:    opts.SimpleSKU,
		RegionalKeys: opts.RegionalKeys,
		BrandID:      opts.BrandID,
		SellerID:     opts.SellerID,
	}
}

type RecommendationItem struct {
	Title     string
	Source    string
	Placement string
	Products  []product_service.Product
}

type IRecommendationAPI interface {
	GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error)
	GetName() string
	SetRequestTimeout(timeout time.Duration)
}

var providerBackups = map[string][]string{
	ProviderTBWidget:    {ProviderTBWidget},
	ProviderTBPlacement: {ProviderTBPlacement},
	ProviderDS:          {ProviderDS},
	ProviderRR:          {ProviderRR},
	ProviderSP:          {ProviderSP, ProviderDS}, // Sponsor Products & Data Science
}

type RecommendationService struct {
	configAPI      IConfigAPI
	productService *product_service.ProductService
	providers      map[string]IRecommendationAPI

	aliceBaseURL string
	venture      string
	recomendConf IRecommendationsConfig
}

func NewRecommendationService(
	configAPI IConfigAPI,
	productService *product_service.ProductService,
	venture, aliceBaseURL string,
	proxyURL string,
	recomendConf IRecommendationsConfig,
) *RecommendationService {
	taobaoByWidget, taobaoByPlacement := NewTaoBaoAPIClient(proxyURL)

	return &RecommendationService{
		venture:        venture,
		aliceBaseURL:   aliceBaseURL,
		configAPI:      configAPI,
		productService: productService,
		providers: map[string]IRecommendationAPI{
			ProviderTBWidget:    taobaoByWidget,
			ProviderTBPlacement: taobaoByPlacement,
			ProviderDS:          NewDataScienceAPIClient(),
			ProviderRR:          &RichRelevanceAPI{},
			ProviderSP:          NewSponsoredProductsAPIClient(),
		},
		recomendConf: recomendConf,
	}
}

func (service *RecommendationService) Process(ctx context.Context, opts *RecommendationOpts, apiToken token.INullToken) ([]RecommendationItem, error) {
	config, err := service.GetConfig(ctx)
	if err != nil {
		return nil, err
	}
	opts.Venture = service.venture
	opts.FillByParams(ctx, apiToken)

	return service.getRecommendationItems(ctx, opts, config), nil
}

func (service *RecommendationService) IsMainPageEnabled() bool {
	return service.recomendConf.RecommendMainPageEnabled()
}

func (service *RecommendationService) IsEnabled() bool {
	return service.recomendConf.RecommendEnabled()
}

func (service *RecommendationService) getProviderPlacements(opts *RecommendationOpts, config *RecommendationConfig) map[string][]string {
	providerPlacements := map[string]map[string]bool{}
	for _, placement := range opts.Placements {
		providerName := config.GetProviderByPlacement(opts, placement)
		if _, find := service.providers[providerName]; !find {
			continue
		}
		if _, find := providerPlacements[providerName]; !find {
			providerPlacements[providerName] = map[string]bool{}
		}
		providerPlacements[providerName][placement] = true
	}

	result := make(map[string][]string, len(providerPlacements))
	for providerName := range providerPlacements {
		result[providerName] = make([]string, 0, len(providerPlacements[providerName]))
		for placement := range providerPlacements[providerName] {
			result[providerName] = append(result[providerName], placement)
		}
		sort.Strings(result[providerName])
	}
	return result
}

func isAtLeastOneInStock(items []RecommendationItem) bool {
	for _, item := range items {
		for i := range item.Products {
			if item.Products[i].IsInStock {
				return true
			}
		}
	}
	return false
}

func (service *RecommendationService) getRecommendationItems(ctx context.Context, opts *RecommendationOpts, config *RecommendationConfig) []RecommendationItem {
	result := map[string]RecommendationItem{}
	providerPlacements := service.getProviderPlacements(opts, config)

	var err error
	var items []RecommendationItem

	for providerName, placements := range providerPlacements {
		for _, provider := range providerBackups[providerName] {
			providerService, _ := service.providers[provider]
			for i := range placements {
				result[placements[i]] = RecommendationItem{
					Source:    providerService.GetName(),
					Placement: placements[i],
				}
			}
			providerService.SetRequestTimeout(service.recomendConf.GetRecommendRequestTimeout(provider))

			if service.recomendConf.CheckProviderProbability(providerName) {
				items, err = providerService.GetRecommendations(ctx, config, opts.copyWithPlacements(placements))
			} else {
				items = []RecommendationItem{}
				err = nil
			}

			if err != nil {
				logger.Error(ctx, "Error loading from %s: %s", providerName, err)
				continue
			}
			if len(items) == 0 {
				continue
			}
			items, err = service.fillProducts(ctx, items, opts)
			if err != nil {
				logger.Error(ctx, "Error loading from %s: %s", providerName, err)
				continue
			}
			if isAtLeastOneInStock(items) {
				break
			}
		}

		for i := range items {
			if item, find := result[items[i].Placement]; find {
				item.Title = getTitlePartByLang(items[i].Title, opts.Lang)
				item.Products = items[i].Products
				result[item.Placement] = item
			} else {
				result[items[i].Placement] = items[i]
			}
		}
	}
	res := make([]RecommendationItem, 0, len(result))
	for placement := range result {
		res = append(res, result[placement])
	}
	sort.Sort(SortBySourceAndPlacement(res))
	return res
}

func (service *RecommendationService) fillProducts(ctx context.Context, result []RecommendationItem, opts *RecommendationOpts) ([]RecommendationItem, error) {
	skus := []string{}
	for _, item := range result {
		for i := range item.Products {
			skus = append(skus, item.Products[i].SKU)
		}
	}
	if len(skus) == 0 {
		return result, nil
	}

	products, err := service.productService.GetProducts(ctx, &search_opts.SearchOpts{Lang: opts.Lang, SKUs: skus})
	if err != nil {
		return nil, err
	}
	productsBySKU := make(map[string]product_service.Product, len(products))
	for _, product := range products {
		if product.RequestedSKU != nil && *product.RequestedSKU != "" {
			productsBySKU[*product.RequestedSKU] = product
		}
		productsBySKU[product.SKU] = product
	}

	for i := range result {
		for j := range result[i].Products {
			if product, find := productsBySKU[result[i].Products[j].SKU]; find {
				product.CtURL = result[i].Products[j].CtURL
				product.CID = result[i].Products[j].CID
				product.DID = result[i].Products[j].DID
				product.SimpleSKU = result[i].Products[j].SimpleSKU
				product.PDPSKU = result[i].Products[j].PDPSKU
				if strings.Index(product.CtURL, "http") == -1 {
					product.CtURL = service.getAliceBaseURL(opts) + "/" + strings.TrimLeft(product.CtURL, "//")
				}
				result[i].Products[j] = product
			}
		}
	}
	return result, nil
}

func getTitlePartByLang(title, lang string) string {
	if lang == "" || !strings.Contains(title, TITLE_LANG_DELIMITER) {
		return title
	}
	parts := strings.Split(title, TITLE_LANG_DELIMITER)
	if lang == "en" && parts[1] != "" {
		return parts[1]
	} else if parts[0] != "" {
		return parts[0]
	} else if parts[1] != "" {
		return parts[1]
	}
	return title
}

type SortBySourceAndPlacement []RecommendationItem

func (a SortBySourceAndPlacement) Len() int      { return len(a) }
func (a SortBySourceAndPlacement) Swap(i, j int) { a[i], a[j] = a[j], a[i] }
func (a SortBySourceAndPlacement) Less(i, j int) bool {
	if a[i].Source == a[j].Source {
		return strings.ToLower(a[i].Placement) < strings.ToLower(a[j].Placement)
	}
	return strings.ToLower(a[i].Source) < strings.ToLower(a[j].Source)
}

func GetRegionalKeysFromCategories(items []transfer.CategorySearchV2ResponseItem, categoryIds []string) []string {
	regionMap := map[uint64]*string{}
	for _, item := range items {
		if item.RegionalKey != nil {
			regionMap[item.IDCatalogCategory] = item.RegionalKey
		}
	}

	regions := []string{}
	for _, id := range categoryIds {
		categoryID, err := strconv.ParseUint(id, 10, 64)
		if item, ok := regionMap[categoryID]; ok && err == nil {
			regions = append(regions, *item)
		}
	}
	return regions
}

func RemoveDuplicatesInSlice(in []string) []string {
	uniq := make(map[string]bool, len(in))
	for _, item := range in {
		uniq[item] = true
	}
	result := make([]string, 0, len(uniq))
	for _, item := range in {
		if uniq[item] {
			result = append(result, item)
			uniq[item] = false
		}
	}
	return result
}

func (service *RecommendationService) getAliceBaseURL(opts *RecommendationOpts) string {
	if opts != nil && opts.Platform == search_opts.PlatformPWA {
		return strings.Replace(service.aliceBaseURL, "http://", "https://", 1)
	}
	return service.aliceBaseURL
}
