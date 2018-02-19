package urlv2

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func getTestResolverResult() *Resolver {
	return &Resolver{
		lang:            "en",
		source:          map[string]SourceResolver{},
		State:           []StateItem{},
		processedPaths:  map[string]bool{},
		processedKeys:   map[string]bool{},
		processedSearch: map[string]bool{},
	}
}

func TestCreateTestResolverResult(t *testing.T) {
	r := getTestResolverResult()
	assert.NotNil(t, r, "")
	assert.Equal(t, len(r.source), 0, "")
	assert.Equal(t, len(r.State), 0, "")
}

func TestGetFirstSearch(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "blabla",
		},
		StateItem{
			Search: "hahaha",
		},
	}
	assert.Equal(t, r.getFirstSearch(), "blabla", "")
}

func TestGetFirstSearchEmpty(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{},
		StateItem{
			Search: "hahaha",
		},
	}
	assert.Equal(t, r.getFirstSearch(), "", "")
}

func TestGetValidStateFirst(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "blabla",
			Models: []Model{
				Model{
					Type: TYPE_KEYWORD,
				},
			},
		},
		StateItem{
			Models: []Model{
				Model{
					Type: TYPE_URL_MAPPING,
				},
			},
		},
	}
	state := r.getValidState()
	if assert.NotNil(t, state, "") {
		assert.Equal(t, state.Search, "blabla", "")
	}
}

func TestGetValidStateSecond(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "blabla",
			Models: []Model{
				Model{
					Type: TYPE_KEYWORD,
				},
			},
		},
		StateItem{
			Search: "some keyword",
			Models: []Model{
				Model{
					Type: TYPE_URL_MAPPING,
				},
			},
		},
	}
	state := r.getValidState()
	if assert.NotNil(t, state, "") {
		assert.Equal(t, state.Search, "some keyword", "")
	}
}

func TestGetValidState(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "first keyword",
			Models: []Model{
				Model{
					Type: TYPE_KEYWORD,
				},
			},
		},
		StateItem{
			Search: "second keyword",
			Models: []Model{
				Model{
					Type: TYPE_URL_MAPPING,
				},
				Model{
					Type: TYPE_CATEGORY,
				},
			},
		},
		StateItem{
			Models: []Model{
				Model{
					Type: TYPE_URL_MAPPING,
				},
			},
		},
	}
	state := r.getValidState()
	if assert.NotNil(t, state, "") {
		assert.Equal(t, state.Search, "second keyword", "")
		models := state.getResolvedModels()
		if assert.Equal(t, len(models), 1, "") {
			assert.Equal(t, models[0].Type, TYPE_CATEGORY, "")
		}
	}
}

func TestResultStaticPage(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path: "helpcenter",
			Keys: []string{"helpcenter"},
			Models: []Model{
				Model{
					Type:   TYPE_STATIC_PAGE,
					URLKey: "helpcenter",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 1, "")
	assert.Nil(t, result.Redirect, "")
	assert.Equal(t, result.UrlKey, []Model{}, "")
	if assert.NotNil(t, result.StaticPage, "") {
		assert.Equal(t, result.StaticPage.Key, "helpcenter", "")
		assert.Equal(t, result.StaticPage.Lang, []string{"en"}, "")
	}
}

func TestResultCategory(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path: "shop-category1",
			Keys: []string{"shop-category1"},
			Models: []Model{
				Model{
					Type:        TYPE_CATEGORY,
					ID:          1,
					Name:        "Category NameEn1",
					URLKey:      "shop-category1",
					FilterValue: "1",
					RegionalKey: "01000001",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 1, "")
	assert.Nil(t, result.Redirect, "")
	if assert.Equal(t, len(result.UrlKey), 1, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "shop-category1", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultCategoryWithFilters(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path:  "shop-category1",
			Keys:  []string{"shop-category1"},
			Query: "price=100-200",
			Models: []Model{
				Model{
					Type:        TYPE_CATEGORY,
					ID:          1,
					Name:        "Category NameEn1",
					URLKey:      "shop-category1",
					FilterValue: "1",
					RegionalKey: "01000001",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 1, "")
	assert.Nil(t, result.Redirect, "")
	if assert.Equal(t, len(result.UrlKey), 1, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "shop-category1", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultCategoryBySearch(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "category name1",
			Models: []Model{
				Model{
					Type:        TYPE_CATEGORY,
					ID:          1,
					Name:        "Category NameEn1",
					URLKey:      "shop-category1",
					FilterValue: "1",
					RegionalKey: "01000001",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 1, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category1/?searchredirect=category+name1", "")
		assert.Equal(t, result.Redirect.Type, TYPE_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 1, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "shop-category1", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectByKeywordToCategoryWithFilters(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "category1 name",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "category1 name",
					URLKey: "/shop-category1/?price=100-200&sort=brand",
				},
			},
		},
		StateItem{
			Path:  "shop-category1",
			Keys:  []string{"shop-category1"},
			Query: "price=100-200&sort=brand",
			Models: []Model{
				Model{
					Type:        TYPE_CATEGORY,
					ID:          1,
					Name:        "Category NameEn1",
					URLKey:      "shop-category1",
					FilterValue: "1",
					RegionalKey: "01000001",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category1/?price=100-200&searchredirect=category1+name&sort=brand", "")
		assert.Equal(t, result.Redirect.Type, TYPE_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 1, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "shop-category1", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectByKeywordToNilPage(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "some phrase",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "some phrase",
					URLKey: "/some-url/?price=100-200&sort=brand",
				},
			},
		},
		StateItem{
			Path:   "some-url",
			Keys:   []string{"some-url"},
			Query:  "price=100-200&sort=brand",
			Models: []Model{},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	assert.Nil(t, result.Redirect, "")
	assert.Equal(t, len(result.UrlKey), 0, "")
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectByKeywordToKeyword(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "some phrase",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "some phrase",
					URLKey: "/some-url/?price=100-200&sort=brand&q=other+phrase",
				},
			},
		},
		StateItem{
			Path:   "some-url",
			Keys:   []string{"some-url"},
			Search: "other phrase",
			Query:  "price=100-200&sort=brand",
			Models: []Model{},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/?price=100-200&q=other+phrase&searchredirect=some+phrase&sort=brand", "")
		assert.Equal(t, result.Redirect.Type, TYPE_KEYWORD, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	assert.Equal(t, len(result.UrlKey), 0, "")
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectByKeywordToStaticPage(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "help",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "help",
					URLKey: "/helpcenter/",
				},
			},
		},
		StateItem{
			Path: "helpcenter",
			Keys: []string{"helpcenter"},
			Models: []Model{
				Model{
					Type:   TYPE_STATIC_PAGE,
					URLKey: "/helpcenter/",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/helpcenter/?searchredirect=help", "")
		assert.Equal(t, result.Redirect.Type, TYPE_STATIC_PAGE, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	assert.Equal(t, len(result.UrlKey), 0, "")
	if assert.NotNil(t, result.StaticPage, "") {
		assert.Equal(t, result.StaticPage.Key, "/helpcenter/", "")
		assert.Equal(t, result.StaticPage.Lang, []string{"en"}, "")
	}
}

func TestResultRedirectURLToStaticPage(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path: "help",
			Keys: []string{"help"},
			Models: []Model{
				Model{
					Type:   TYPE_URL_MAPPING,
					Name:   "help",
					URLKey: "/helpcenter/",
					Code:   302,
				},
			},
		},
		StateItem{
			Path: "helpcenter",
			Keys: []string{"helpcenter"},
			Models: []Model{
				Model{
					Type:   TYPE_STATIC_PAGE,
					URLKey: "/helpcenter/",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/helpcenter/", "")
		assert.Equal(t, result.Redirect.Type, TYPE_STATIC_PAGE, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	assert.Equal(t, len(result.UrlKey), 0, "")
	if assert.NotNil(t, result.StaticPage, "") {
		assert.Equal(t, result.StaticPage.Key, "/helpcenter/", "")
		assert.Equal(t, result.StaticPage.Lang, []string{"en"}, "")
	}
}

func TestResultRedirectURLToStaticPage301(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path: "help",
			Keys: []string{"help"},
			Models: []Model{
				Model{
					Type:   TYPE_URL_MAPPING,
					Name:   "help",
					URLKey: "/helpcenter/",
					Code:   301,
				},
			},
		},
		StateItem{
			Path: "helpcenter",
			Keys: []string{"helpcenter"},
			Models: []Model{
				Model{
					Type:   TYPE_STATIC_PAGE,
					URLKey: "/helpcenter/",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/helpcenter/", "")
		assert.Equal(t, result.Redirect.Type, TYPE_STATIC_PAGE, "")
		assert.Equal(t, result.Redirect.HTTPCode, 301, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	assert.Equal(t, len(result.UrlKey), 0, "")
	if assert.NotNil(t, result.StaticPage, "") {
		assert.Equal(t, result.StaticPage.Key, "/helpcenter/", "")
		assert.Equal(t, result.StaticPage.Lang, []string{"en"}, "")
	}
}

func TestResultRedirectByKeywordToCategoryAndBrand(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "brand and category",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "help",
					URLKey: "/shop-category1/url-key-brand1/?price=100-300",
				},
			},
		},
		StateItem{
			Path:  "shop-category1/url-key-brand1",
			Keys:  []string{"shop-category1", "url-key-brand1"},
			Query: "price=100-300",
			Models: []Model{
				Model{
					Type:   TYPE_CATEGORY,
					URLKey: "shop-category1",
				},
				Model{
					Type:   TYPE_BRAND,
					URLKey: "url-key-brand1",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category1/url-key-brand1/?price=100-300&searchredirect=brand+and+category", "")
		assert.Equal(t, result.Redirect.Type, TYPE_BRAND_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 2, "") {
		assert.Equal(t, result.UrlKey[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, result.UrlKey[1].Type, TYPE_BRAND, "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectByKeywordToCategoryAndSupplier(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "supplier and category",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "supplier and category",
					URLKey: "/shop-category1/url-key-supplier1/",
				},
			},
		},
		StateItem{
			Path: "shop-category1/url-key-supplier1",
			Keys: []string{"shop-category1", "url-key-supplier1"},
			Models: []Model{
				Model{
					Type:   TYPE_CATEGORY,
					URLKey: "shop-category1",
				},
				Model{
					Type:   TYPE_SUPPLIER,
					URLKey: "url-key-supplier1",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category1/url-key-supplier1/?searchredirect=supplier+and+category", "")
		assert.Equal(t, result.Redirect.Type, TYPE_SUPPLIER_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 2, "") {
		assert.Equal(t, result.UrlKey[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, result.UrlKey[1].Type, TYPE_SUPPLIER, "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectByKeywordToCategoryAndHighligt(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Search: "highlight and category",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					Name:   "highlight and category",
					URLKey: "/shop-category1/url-key-highlight/1",
				},
			},
		},
		StateItem{
			Path: "shop-category1/url-key-highlight",
			Keys: []string{"shop-category1", "url-key-highlight"},
			Models: []Model{
				Model{
					Type:   TYPE_CATEGORY,
					URLKey: "shop-category1",
				},
				Model{
					Type:   TYPE_HIGHLIGHT,
					URLKey: "url-key-highlight",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category1/url-key-highlight/?searchredirect=highlight+and+category", "")
		assert.Equal(t, result.Redirect.Type, TYPE_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 2, "") {
		assert.Equal(t, result.UrlKey[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, result.UrlKey[1].Type, TYPE_HIGHLIGHT, "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectSwapURL(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path: "/brand-url-key1/shop-category1/",
			Keys: []string{"brand-url-key1", "shop-category1"},
			Models: []Model{
				Model{
					Type:   TYPE_CATEGORY,
					URLKey: "shop-category1",
				},
				Model{
					Type:   TYPE_BRAND,
					URLKey: "brand-url-key1",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 1, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category1/brand-url-key1/", "")
		assert.Equal(t, result.Redirect.Type, TYPE_BRAND_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 2, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "shop-category1", "")
		assert.Equal(t, result.UrlKey[1].URLKey, "brand-url-key1", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultRedirectSwapURLWithRedirect(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path: "/brand-url-key1/shop-category1/",
			Keys: []string{"brand-url-key1", "shop-category1"},
			Models: []Model{
				Model{
					Type:   TYPE_URL_MAPPING,
					Name:   "brand-url-key1/shop-category1",
					URLKey: "/brand-url-key2/shop-category2/",
					Code:   301,
				},
				Model{
					Type:   TYPE_CATEGORY,
					URLKey: "shop-category1",
				},
				Model{
					Type:   TYPE_BRAND,
					URLKey: "brand-url-key1",
				},
			},
		},
		StateItem{
			Path: "/brand-url-key2/shop-category2/",
			Keys: []string{"brand-url-key2", "shop-category2"},
			Models: []Model{
				Model{
					Type:   TYPE_CATEGORY,
					URLKey: "shop-category2",
				},
				Model{
					Type:   TYPE_BRAND,
					URLKey: "brand-url-key2",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/shop-category2/brand-url-key2/", "")
		assert.Equal(t, result.Redirect.Type, TYPE_BRAND_CATEGORY, "")
		assert.Equal(t, result.Redirect.HTTPCode, 301, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 2, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "shop-category2", "")
		assert.Equal(t, result.UrlKey[1].URLKey, "brand-url-key2", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultKeywordToBrandWithSameKeyword1(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path:   "",
			Keys:   []string{""},
			Search: "s8",
			Models: []Model{
				Model{
					Type:   TYPE_KEYWORD,
					URLKey: "/samsung/?q=s8",
				},
			},
		},
		StateItem{
			Path:   "samsung",
			Query:  "q=s8",
			Search: "s8",
			Keys:   []string{"samsung"},
			Models: []Model{
				Model{
					Type:   TYPE_BRAND,
					URLKey: "samsung",
				},
				Model{
					Type:   TYPE_KEYWORD,
					URLKey: "/samsung/?q=s8",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 2, "")
	if assert.NotNil(t, result.Redirect, "") {
		assert.Equal(t, result.Redirect.Target, "/samsung/?q=s8&searchredirect=s8", "")
		assert.Equal(t, result.Redirect.Type, TYPE_BRAND, "")
		assert.Equal(t, result.Redirect.HTTPCode, 302, "")
		assert.Equal(t, result.Redirect.MobAPI, true, "")
	}
	if assert.Equal(t, len(result.UrlKey), 1, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "samsung", "")
	}
	assert.Nil(t, result.StaticPage, "")
}

func TestResultKeywordToBrandWithSameKeyword2(t *testing.T) {
	r := getTestResolverResult()
	r.State = []StateItem{
		StateItem{
			Path:   "samsung",
			Search: "s8",
			Keys:   []string{"samsung"},
			Models: []Model{
				Model{
					Type:   TYPE_BRAND,
					URLKey: "samsung",
				},
				Model{
					Type:   TYPE_KEYWORD,
					URLKey: "/samsung/?q=s8",
				},
			},
		},
	}
	result := r.Result()
	assert.Equal(t, len(result.State), 1, "")
	assert.Nil(t, result.Redirect, "")
	if assert.Equal(t, len(result.UrlKey), 1, "") {
		assert.Equal(t, result.UrlKey[0].URLKey, "samsung", "")
	}
	assert.Nil(t, result.StaticPage, "")
}
