package search_service

import (
	"context"
	"errors"
	"strconv"
	"strings"

	"github.com/blang/semver"
	"github.com/sergei-svistunov/gorpc/transport/cache"
	"godep.lzd.co/go-i18n"
	"godep.lzd.co/go-trace"
	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/model"
	"mobile_search_api/srv/product"
	"mobile_search_api/srv/search_opts"
	"mobile_search_api/srv/version_rules"
)

var filtersMulti = map[string]bool{
	search_opts.FilterIDBrand:       true,
	search_opts.FilterIDSeller:      true,
	search_opts.FilterIDColorFamily: true,
	search_opts.FilterIDHighlight:   true,
	"display_size":                  true,
	"display_size_mobile":           true,
	"display_size_tablet":           true,
	"display_size_tv":               true,
	"delivery_and_payment":          true,
}

var filtersIdTranslateOptions = map[string]bool{
	search_opts.FilterIDHighlight:   true,
	search_opts.FilterIDTaobao:      true,
	search_opts.FilterIDColorFamily: true,
	"is_international":              true,
	"delivery_and_payment":          true,
}

type SearchService struct {
	contentAPI  IContentAPI
	trackingAPI ITrackingAPI
	i18nManager *i18n.Manager
}

type SearchResult struct {
	opts       *search_opts.SearchOpts
	dictionary *i18n.Dictionary
	platform   string
	appVersion semver.Version

	RequestDump          *search.RequestDump // cuted dump of request to elasticsearch which bases on input params
	Products             []product_service.Product
	TotalProducts        uint64
	SelectedFiltersCount uint64
	SKUs                 []string
	Filters              []search.OneFilter
	DidYouMean           []search.DidYouMeanCollation
	SearchContextIgnored bool
	TopSticker           *product_service.ProductSticker
	TrackingData         *TrackingData
}

func NewSearchService(contentAPI IContentAPI, trackingAPI ITrackingAPI, i18nManager *i18n.Manager) *SearchService {
	return &SearchService{
		contentAPI:  contentAPI,
		trackingAPI: trackingAPI,
		i18nManager: i18nManager,
	}
}

func (service *SearchService) Search(ctx context.Context, opts *search_opts.SearchOpts, models []*model_service.ModelData) (*SearchResult, error) {
	if opts == nil {
		return nil, errors.New("No opts")
	}

	span, ctx := gotrace.StartSpanFromContext(ctx, "fillRequestDeviceID")
	defer span.Finish()

	fillRequestDeviceIDInContext(ctx)
	opts.IsStressTest = rules.IsStressTest(ctx)
	response, err := service.contentAPI.SearchGet(cache.NewContextWithoutTransportCache(ctx), opts.Generate())
	if err != nil {
		return nil, err
	}

	trackingData := service.NewTrackingData()
	trackingData.FillByContext(ctx)
	trackingData.FillBySearchResponse(response)
	trackingData.FillBySearchOpts(opts)

	result := &SearchResult{
		opts:                 opts,
		RequestDump:          response.RequestDump,
		TotalProducts:        uint64(response.Total),
		SKUs:                 response.SKU,
		Filters:              response.Filters,
		DidYouMean:           response.DidYouMean,
		SearchContextIgnored: response.SearchContextIgnored,
		TrackingData:         trackingData,
	}
	hasTopSticker := isHasTopSticker(result)
	if hasTopSticker && len(response.Hits) > 0 {
		result.TopSticker = product_service.GetSticker(response.Hits[0])
	}
	result.Products = product_service.ConvertProducts(response.Hits, opts, hasTopSticker)

	if opts.Lang != "" && service.i18nManager != nil {
		result.dictionary = service.i18nManager.GetDictionary(opts.Lang)
	}

	result.platform, result.appVersion = rules.GetPlatformAndVersion(ctx)
	return result, nil
}

// removeChildrenFromCategoryFilter remove nested filters in category filters if them nested level greater 3
func removeChildrenFromCategoryFilter(level int, option *search.FilterOption) {
	if option == nil {
		return
	}
	level++
	if level <= 3 {
		for _, filter := range option.Children {
			removeChildrenFromCategoryFilter(level, filter)
		}
	} else {
		option.Children = nil
	}
}

func isHasTopSticker(result *SearchResult) bool {
	for i, filter := range result.Filters {
		if filter.ID == search_opts.FilterIDProductSticker {
			if len(filter.Options) == 1 && filter.Options[0].ProdCount != nil && int64(*filter.Options[0].ProdCount) == int64(result.TotalProducts) {
				result.Filters = append(result.Filters[:i], result.Filters[i+1:]...)
				return true
			}
		}
	}
	return false
}

func (result *SearchResult) FillSelectedFilters(selectedFilters map[string][]string) {
	for i := range result.Filters {
		result.Filters[i].SelectedOptions = make([]*search.FilterOption, 0)
		for _, val := range selectedFilters[result.Filters[i].ID] {
			fillSelectedFilterOptions(&result.Filters[i], result.Filters[i].Options, val)
		}
	}
	result.SelectedFiltersCount = uint64(len(selectedFilters))
}

func fillPriceOptions(opt *search.FilterOption, value string) bool {
	min_max := strings.Split(value, "-")
	if len(min_max) == 2 {
		min, err := strconv.ParseUint(min_max[0], 10, 64)
		if err != nil {
			return false
		}
		opt.Min = &min
		max, err := strconv.ParseUint(min_max[1], 10, 64)
		if err != nil {
			return false
		}
		opt.Max = &max
		return true
	}
	return false
}

func fillSelectedFilterOptions(filter *search.OneFilter, options []*search.FilterOption, filterValue string) {
	for _, opt := range options {
		if filterValue == opt.Val {
			filter.SelectedOptions = append(filter.SelectedOptions, opt)
			break
		} else if filter.ID == search_opts.FilterIDPrice {
			opt := &search.FilterOption{}
			if fillPriceOptions(opt, filterValue) {
				filter.SelectedOptions = append(filter.SelectedOptions, opt)
			}
		}
		if len(opt.Children) > 0 {
			fillSelectedFilterOptions(filter, opt.Children, filterValue)
		}
	}
}

func (result *SearchResult) ProcessFilters() {
	if len(result.Filters) > 0 {
		resultFilters := make([]search.OneFilter, 0, len(result.Filters))
		model := result.opts.Model
		if model != search_opts.ModelHighlight && rules.CheckRule("version_taobao_filter", result.platform, result.appVersion, "", "") && result.isTaobaoHighlightExist() {
			result.Filters = append([]search.OneFilter{result.getTaobaoFilter()}, result.Filters...)
		}

		for _, filter := range result.Filters {
			if len(filter.Options) == 0 {
				continue
			}
			filter.Multi = isFiltersMulti(filter.ID)

			if result.dictionary != nil {
				filter.Label, _ = result.dictionary.Translate(filter.Label, nil, nil)
				if needTranslateOptions, ok := filtersIdTranslateOptions[filter.ID]; needTranslateOptions && ok {
					for i := range filter.Options {
						filter.Options[i].Label, _ = result.dictionary.Translate(filter.Options[i].Label, nil, nil)
					}
				}
			}

			// GO-12698: Remove nested filters in category filters if them nested level greater 3 and pm_version < 9
			if filter.ID == search_opts.FilterIDCategory && !rules.CheckRule("version_category_filter_full", result.platform, result.appVersion, "", "") {
				for _, option := range filter.Options {
					removeChildrenFromCategoryFilter(1, option)
				}
			}

			resultFilters = append(resultFilters, filter)
		}
		result.Filters = resultFilters
	}

	result.FillSelectedFilters(result.opts.ParseFilters())
	if rules.CheckRule("version_alphabetize_brand_filter", result.platform, result.appVersion, "", "") {
		result.AlphabetizeBrandFilters()
	}
	result.SortFiltersBySelectedOptions()
}

func (result *SearchResult) isTaobaoHighlightExist() bool {
	for _, filter := range result.Filters {
		if filter.ID != search_opts.FilterIDHighlight {
			continue
		}
		for i := range filter.Options {
			if filter.Options[i].Label == search_opts.TaobaoHighlightLabel {
				return true
			}
		}
		return false
	}
	return false
}

func (result *SearchResult) getTaobaoFilter() search.OneFilter {
	return search.OneFilter{
		ID:    search_opts.FilterIDTaobao,
		Label: search_opts.TaobaoHighlightLabel,
		Multi: isFiltersMulti(search_opts.FilterIDTaobao),
		Options: []*search.FilterOption{
			&search.FilterOption{
				Label: search_opts.TaobaoHighlightLabel,
				Val:   search_opts.FilterValueTaobao,
			},
		},
	}
}

func isFiltersMulti(filterID string) bool {
	if isMulti, exist := filtersMulti[filterID]; exist {
		return isMulti
	}
	return false
}
