package url_resolve_service_v2

import (
	"context"
	"net/url"
	"regexp"
	"sort"
	"strings"

	"github.com/sergei-svistunov/gorpc/transport/cache"
	"mobile_search_api/api/ext_services"
	"mobile_search_api/srv/model"
	"mobile_search_api/srv/search_opts"
)

const (
	defaultSearchModel = search_opts.ModelSearchQ
)

var modelResolveMap = map[string]string{
	"category":  search_opts.ModelCategory,
	"brand":     search_opts.ModelBrand,
	"highlight": search_opts.ModelHighlight,
	"supplier":  search_opts.ModelSeller,
}

var typePriority = []string{
	"category",
	"brand",
	"highlight",
	"supplier",
}

var ignoredFilterParams = []string{
	//"sort",
	//"dir",
	//"q",
	"ref",
	"searchredirect",
	"skus",
	"skus[]",
}

type ResolveService struct {
	contentAPI IContentAPI
}

func NewResolveService(contentAPI IContentAPI) *ResolveService {
	return &ResolveService{
		contentAPI: contentAPI,
	}
}

func (service *ResolveService) Resolve(ctx context.Context, opts *search_opts.SearchOpts) {
	if opts.Model == search_opts.ModelSKUs || opts.Model == search_opts.ModelLandingMenu {
		return
	}
	resolve, err := service.contentAPI.GetURLResolveV2(cache.NewContextWithTransportCache(ctx), opts.Path, opts.Query, opts.Lang, opts.SearchContext == "" && opts.Filters == "", opts.GetConfigOverlay())
	if err != nil {
		return
	}

	processUrlResolveV2(ctx, resolve, opts)
}

func processUrlResolveV2(ctx context.Context, resolve *ext_services.URLResolve, opts *search_opts.SearchOpts) {
	if resolve == nil {
		return
	}

	if resolve.StaticPage != nil {
		opts.Model = search_opts.ModelStaticPage
		key := strings.Trim(resolve.StaticPage.Key, "/")
		opts.Key = key
		opts.Path = key
		opts.Query = ""
		opts.Filters = ""
		return
	}

	params := &params{}

	if resolve.Redirect != nil && resolve.Redirect.Target != "" {
		opts.Path = ""
		opts.Query = ""
		processPath(resolve.Redirect.Target, opts, params)
	} else {
		if opts.Path != "" {
			processPath(opts.Path, opts, params)
		}
	}

	if len(resolve.URLKeys) == 0 {
		opts.Path = ""
	} else {
		addModels(ctx, resolve.URLKeys)
	}

	opts.Model = calcModel(resolve.URLKeys)
	if opts.Model == "" && opts.Query != "" {
		opts.Model = defaultSearchModel
	}

	opts.Filters = converFilters(mergeFilters(parseFilters(opts.Filters), params.filters))
	if opts.Sort == "" && params.sort != "" {
		opts.Sort = params.sort
	}
	if opts.Direction == "" && params.dir != "" {
		opts.Direction = params.dir
	}
	opts.Key = calcKey(opts, params)
}

func processPath(path string, opts *search_opts.SearchOpts, params *params) {
	u, err := url.Parse(path)
	if err != nil {
		return
	}

	opts.Path = strings.Trim(u.Path, "/")
	query := u.Query()

	for _, ignoredParam := range ignoredFilterParams {
		query.Del(ignoredParam)
	}

	if search := query.Get("q"); search != "" {
		query.Del("q")
		opts.Query = search
		params.query = search
	} else if opts.Query != "" {
		params.query = opts.Query
	}

	params.processQuery(query)
}

func calcModel(keys []ext_services.URLKey) string {
	for _, t := range typePriority {
		for _, key := range keys {
			if key.Type == t {
				return typeToModel(t)
			}
		}
	}
	return ""
}

func typeToModel(t string) string {
	if res, exists := modelResolveMap[t]; exists {
		return res
	}
	return ""
}

func calcKey(opts *search_opts.SearchOpts, params *params) string {
	if opts.Model == search_opts.ModelSearchQ {
		return opts.Query
	}

	if len(params.filters) > 0 || params.sort != "" || params.query != "" {
		return opts.Path + "?" + params.Encode()
	}
	return opts.Path
}

type params struct {
	filters url.Values
	sort    string
	dir     string
	query   string
}

func (p *params) processQuery(query url.Values) {
	p.sort = query.Get("sort")
	p.dir = query.Get("dir")
	query.Del("sort")
	query.Del("dir")
	p.filters = query
}

func (p *params) Encode() string {
	query := url.Values{}
	if p.query != "" {
		query.Add("q", p.query)
	}
	if p.sort != "" {
		query.Add("sort", p.sort)
		if p.dir != "" {
			query.Add("dir", p.dir)
		}
	}
	for k, v := range p.filters {
		query[k] = v
	}
	return query.Encode()
}

var rf = regexp.MustCompile("&|@|~|=")

func parseFilters(filters string) url.Values {
	result := url.Values{}
	if len(filters) == 0 {
		return result
	}
	extraFilters := rf.Split(filters, -1)
	if len(extraFilters) > 0 && len(extraFilters)%2 == 0 {
		key := ""
		for i, v := range extraFilters {
			if i%2 == 0 {
				key = v
			} else {
				if _, exists := result[key]; !exists {
					result.Add(key, v)
				}
			}
		}
	}
	return result
}

func mergeFilters(query1, query2 url.Values) url.Values {
	result := url.Values{}
	for k, v := range query1 {
		result[k] = v
	}
	for k, v := range query2 {
		if _, exists := result[k]; !exists {
			result[k] = v
		}
	}
	return result
}

func converFilters(query url.Values) string {
	filters := make([]string, 0, len(query))
	for key, value := range query {
		if len(value) > 0 {
			filters = append(filters, key+"~"+value[0])
		}
	}
	sort.Sort(SortByAlphabet(filters))
	return strings.Join(filters, "@")
}

type SortByAlphabet []string

func (items SortByAlphabet) Len() int {
	return len(items)
}

func (items SortByAlphabet) Swap(i, j int) {
	items[i], items[j] = items[j], items[i]
}

func (items SortByAlphabet) Less(i, j int) bool {
	return strings.Compare(items[i], items[j]) < 0
}

func addModels(ctx context.Context, keys []ext_services.URLKey) {
	for _, key := range keys {
		model_service.AddModel(ctx, &model_service.ModelData{
			Model:       typeToModel(key.Type),
			FilterID:    search_opts.FilterIDByModel[key.Type],
			Label:       key.Name,
			Value:       key.FilterValue,
			ID:          int64(key.ID),
			IsSis:       key.IsSis,
			URLKey:      key.Key,
			RegionalKey: key.RegionalKey,
		})
	}
}
