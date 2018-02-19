package urlv2

import (
	autoCache "content_api/services/search_helper/cache"
)

const (
	TYPE_KEYWORD           = "keyword"
	TYPE_URL_MAPPING       = "urlmap"
	TYPE_CATEGORY          = "category"
	TYPE_BRAND             = "brand"
	TYPE_BRAND_CATEGORY    = "brand-category"
	TYPE_SUPPLIER          = "supplier"
	TYPE_SUPPLIER_CATEGORY = "supplier-category"
	TYPE_HIGHLIGHT         = "highlight"
	TYPE_STATIC_PAGE       = "staticpage"
)

type Model struct {
	ID          uint64 `json:"id,omitempty"`
	URLKey      string `json:"key,omitempty"`
	Type        string `json:"type,omitempty"`
	Name        string `json:"name,omitempty"`
	FilterValue string `json:"filter_value,omitempty"`
	RegionalKey string `json:"regional_key,omitempty"`
	Code        int    `json:"code,omitempty"`
}

type Resolver struct {
	lang            string
	redirectEnabled bool
	source          map[string]SourceResolver
	State           []StateItem
	processedPaths  map[string]bool
	processedKeys   map[string]bool
	processedSearch map[string]bool
}

type SourceResolver interface {
	RedirectByPath(path string) []Model
	ResolveKeys(keys []string) []Model
	RedirectBySearch(search string) []Model
}

type StateItem struct {
	Path   string   `json:"path,omitempty"`
	Search string   `json:"search,omitempty"`
	Query  string   `json:"query,omitempty"`
	Keys   []string `json:"keys,omitempty"`
	Models []Model  `json:"items"`
}

func (state StateItem) getResolvedModels() []Model {
	result := make([]Model, 0, len(state.Models))
	for _, model := range state.Models {
		if model.Type != TYPE_KEYWORD && model.Type != TYPE_URL_MAPPING {
			result = append(result, model)
		}
	}
	return result
}

func (state StateItem) isValid() bool {
	return state.Search != "" || len(state.getResolvedModels()) > 0
}

func New(lang string, isMobapi, redirectEnabled bool) *Resolver {
	return &Resolver{
		lang:            lang,
		redirectEnabled: redirectEnabled,
		source:          getSourceResolvers(lang, isMobapi),
		State:           []StateItem{},
		processedPaths:  map[string]bool{},
		processedKeys:   map[string]bool{},
		processedSearch: map[string]bool{},
	}
}

func getSourceResolvers(lang string, isMobapi bool) map[string]SourceResolver {
	nilresolver := GetNilResolver()
	result := map[string]SourceResolver{
		TYPE_URL_MAPPING: nilresolver,
		TYPE_KEYWORD:     nilresolver,
		TYPE_CATEGORY:    nilresolver,
		TYPE_BRAND:       nilresolver,
		TYPE_SUPPLIER:    nilresolver,
		TYPE_HIGHLIGHT:   nilresolver,
		TYPE_STATIC_PAGE: nilresolver,
	}

	if c, err := autoCache.GetUrlMappingCache(); err == nil {
		result[TYPE_URL_MAPPING] = GetURLMappingResolver(c, lang, isMobapi)
	}

	if c, err := autoCache.GetCmsFolderCache(); err == nil {
		if isMobapi {
			if cPM, err := autoCache.GetPageListCache(); err == nil {
				result[TYPE_STATIC_PAGE] = GetStaticPageResolver(c, cPM, isMobapi, lang)
			}
		} else {
			result[TYPE_STATIC_PAGE] = GetStaticPageResolver(c, nil, false, lang)
		}
	}

	if c, err := autoCache.CatalogCategory(); err == nil {
		result[TYPE_CATEGORY] = GetCategoryResolver(c, lang)
	}

	if c, err := autoCache.GetLazadaHighlightsCache(); err == nil {
		result[TYPE_HIGHLIGHT] = GetHighlightResolver(c, lang)
	}

	if c, err := autoCache.GetCatalogBrandCache(); err == nil {
		result[TYPE_BRAND] = GetBrandResolver(c, lang)
	}

	if c, err := autoCache.GetSuppliersCache(); err == nil {
		result[TYPE_SUPPLIER] = GetSupplierResolver(c, lang)
	}

	if c, err := autoCache.GetRedirectKeywordsCache(); err == nil {
		result[TYPE_KEYWORD] = GetKeywordResolver(c, lang)
	}

	return result
}
