package url_resolve_service_v2

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"mobile_search_api/api/ext_services"
	"mobile_search_api/srv/search_opts"
)

func TestProcessUrlResolveV2Category(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "shop-category1",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-category1",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category1", "")
	assert.Equal(t, opts.Key, "shop-category1", "")
}

func TestProcessUrlResolveV2RedirectToCategory(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "category name1",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/shop-category1/?searchredirect=category+name1",
			Type:         "category",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-category1",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category1", "")
	assert.Equal(t, opts.Key, "shop-category1", "")
}

func TestProcessUrlResolveV2RedirectToSellerCategory(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "category seller name1",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/shop-category1/ulr-key-supplier2/?searchredirect=category+seller+name1",
			Type:         "supplier-category",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-category1",
			},
			ext_services.URLKey{
				ID:   2,
				Type: "supplier",
				Key:  "url-key-supplier2",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category1/ulr-key-supplier2", "")
	assert.Equal(t, opts.Key, "shop-category1/ulr-key-supplier2", "")
}

func TestProcessUrlResolveV2RedirectToBrandCategoryWithFiltersAndSort(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "category seller name1 filters",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/shop-category1/ulr-key-brand2/?searchredirect=category+seller+name1&price=100-200&sort=name&dir=desc",
			Type:         "supplier-category",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-category1",
			},
			ext_services.URLKey{
				ID:   2,
				Type: "brand",
				Key:  "url-key-brand2",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category1/ulr-key-brand2", "")
	assert.Equal(t, opts.Key, "shop-category1/ulr-key-brand2?dir=desc&price=100-200&sort=name", "")
	assert.Equal(t, opts.Filters, "price~100-200", "")
	assert.Equal(t, opts.Sort, "name", "")
	assert.Equal(t, opts.Direction, "desc", "")
}

func TestProcessUrlResolveV2RedirectKeywordToNothing(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "keyword",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "keyword", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "keyword", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2RedirectKeywordToKeyword(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "keyword1",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/?searchredirect=keyword1&q=keyword2",
			Type:         "keyword",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "keyword2", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "keyword2", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2StaticPage(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "/some-page/",
	}
	resolve := &ext_services.URLResolve{
		StaticPage: &ext_services.StaticPage{
			Key:  "some-page",
			Lang: []string{"en"},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelStaticPage, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "some-page", "")
	assert.Equal(t, opts.Key, "some-page", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2RedirectToStaticPage(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "/some-page/",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/help-page/",
			Type:         "static_page",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{},
		StaticPage: &ext_services.StaticPage{
			Key:  "help-page",
			Lang: []string{"en"},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelStaticPage, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "help-page", "")
	assert.Equal(t, opts.Key, "help-page", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2NotResolvedPath(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "/unknown-page/",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, "", "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2NotResolvedQuery(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "some keywords",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "some keywords", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "some keywords", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2NotResolvedPathWithQuery(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "some keywords",
		Path:  "/some-page/",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "some keywords", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "some keywords", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2CategoryWithQuery(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "some keywords",
		Path:  "/shop-category1/",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-category1",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "some keywords", "")
	assert.Equal(t, opts.Path, "shop-category1", "")
	assert.Equal(t, opts.Key, "shop-category1?q=some+keywords", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveV2PathWithFilters(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "shop-category1/?price=100-200",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-category1",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category1", "")
	assert.Equal(t, opts.Key, "shop-category1?price=100-200", "")
	assert.Equal(t, opts.Filters, "price~100-200", "")
}

func TestProcessUrlResolveV2RedirectPathWithFilters(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "shop-category1/?price=100-200&color_family=Black",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/shop-category2/?price=50-500",
			Type:         "category",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   2,
				Type: "category",
				Key:  "shop-category2",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category2", "")
	assert.Equal(t, opts.Key, "shop-category2?price=50-500", "")
	assert.Equal(t, opts.Filters, "price~50-500", "")
}

func TestProcessUrlResolveV2RedirectSort(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path:      "shop-category1/?sort=price&dir=desc",
		Sort:      "brand",
		Direction: "asc",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/shop-category2/?sort=deliverytime&dir=",
			Type:         "category",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   2,
				Type: "category",
				Key:  "shop-category2",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category2", "")
	assert.Equal(t, opts.Key, "shop-category2?sort=deliverytime", "")
	assert.Equal(t, opts.Filters, "", "")
	assert.Equal(t, opts.Sort, "brand", "")
	assert.Equal(t, opts.Direction, "asc", "")
}

func TestProcessUrlResolveV2Sort(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path:      "shop-category2/?sort=brand&dir=asc",
		Sort:      "brand",
		Direction: "asc",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   2,
				Type: "category",
				Key:  "shop-category2",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-category2", "")
	assert.Equal(t, opts.Key, "shop-category2?dir=asc&sort=brand", "")
	assert.Equal(t, opts.Filters, "", "")
	assert.Equal(t, opts.Sort, "brand", "")
	assert.Equal(t, opts.Direction, "asc", "")
}

func TestProcessUrlResolveQueryKeyword(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "?q=keyword",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "keyword", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "keyword", "")
	assert.Equal(t, opts.Filters, "", "")
}

func TestProcessUrlResolveQueryKeywordWithFilters(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Path: "?q=keyword&price=100-200",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "keyword", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "keyword", "")
	assert.Equal(t, opts.Filters, "price~100-200", "")
}

func TestProcessUrlResolveV2RedirectWithParams1(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "lg 32",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/shop-televisions/lg/?searchredirect=lg+32&skus%5B%5D=LG612ELAA4K4RYANPH&skus%5B%5D=LG612ELAA3GLBKANPH",
			Type:         "brand-category",
			HTTPCode:     301,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "category",
				Key:  "shop-televisions",
			},
			ext_services.URLKey{
				ID:   1,
				Type: "brand",
				Key:  "lg",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Query, "", "")
	assert.Equal(t, opts.Path, "shop-televisions/lg", "")
	assert.Equal(t, opts.Key, "shop-televisions/lg", "")
}

func TestProcessUrlResolveV2RedirectWithParams2(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "s8",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/samsung/?q=s8&searchredirect=s8",
			Type:         "brand",
			HTTPCode:     302,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "brand",
				Key:  "samsung",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelBrand, "")
	assert.Equal(t, opts.Query, "s8", "")
	assert.Equal(t, opts.Path, "samsung", "")
	assert.Equal(t, opts.Key, "samsung?q=s8", "")
}

func TestProcessUrlResolveV2RedirectWithParams3(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "s8",
	}
	resolve := &ext_services.URLResolve{
		Redirect: &ext_services.RedirectTarget{
			Target:       "/?q=j7&searchredirect=s8",
			Type:         "keyword",
			HTTPCode:     302,
			MobileEnable: true,
		},
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "keyword",
				Key:  "?q=j7",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Query, "j7", "")
	assert.Equal(t, opts.Path, "", "")
	assert.Equal(t, opts.Key, "j7", "")
}

func TestProcessUrlResolveV2RedirectWithParams4(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Query: "s8",
		Path:  "samsung",
	}
	resolve := &ext_services.URLResolve{
		URLKeys: []ext_services.URLKey{
			ext_services.URLKey{
				ID:   1,
				Type: "brand",
				Key:  "samsung",
			},
		},
	}

	processUrlResolveV2(context.Background(), resolve, opts)

	assert.Equal(t, opts.Model, search_opts.ModelBrand, "")
	assert.Equal(t, opts.Query, "s8", "")
	assert.Equal(t, opts.Path, "samsung", "")
	assert.Equal(t, opts.Key, "samsung?q=s8", "")
}
