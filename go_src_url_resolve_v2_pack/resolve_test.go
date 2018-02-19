package urlv2

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func getTestResolver(lang string, isMobapi, redirectEnabled bool) *Resolver {
	return &Resolver{
		redirectEnabled: redirectEnabled,
		source: map[string]SourceResolver{
			TYPE_URL_MAPPING: GetURLMappingResolver(uCache, lang, isMobapi),
			TYPE_STATIC_PAGE: GetStaticPageResolver(spCache, pmCache, isMobapi, lang),
			TYPE_CATEGORY:    GetCategoryResolver(catCache, lang),
			TYPE_HIGHLIGHT:   GetHighlightResolver(hCache, lang),
			TYPE_BRAND:       GetBrandResolver(bCache, lang),
			TYPE_SUPPLIER:    GetSupplierResolver(sCache, lang),
			TYPE_KEYWORD:     GetKeywordResolver(kCache, lang),
		},
		State:           []StateItem{},
		processedPaths:  map[string]bool{},
		processedKeys:   map[string]bool{},
		processedSearch: map[string]bool{},
	}
}

func TestCreateTestResolver(t *testing.T) {
	r := getTestResolver("en", false, true)
	assert.NotNil(t, r, "")
	assert.Equal(t, len(r.source), 7, "")
}

func TestCheckRedirectsBySearch(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.checkRedirectsBySearch("Brand Name1")
	if assert.Equal(t, len(models), 1, "") {
		assert.Equal(t, models[0].Type, TYPE_BRAND, "")
		assert.Equal(t, models[0].Name, "Brand Name1", "")
	}
}

func TestCheckRedirectsByPath(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.checkRedirectsByPath("/url-key-brand3/")
	if assert.Equal(t, len(models), 1, "") {
		assert.Equal(t, models[0].Type, TYPE_URL_MAPPING, "")
		assert.Equal(t, models[0].URLKey, "/url-key-supplier3/", "")
	}
}

func TestCheckKeys(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.checkKeys([]string{"url-key-brand1", "shop-category1", "url-key-highlight1", "url-key-supplier1"})
	if assert.Equal(t, len(models), 4, "") {
		assert.Equal(t, models[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, models[0].Name, "Category NameEn1", "")
		assert.Equal(t, models[1].Type, TYPE_BRAND, "")
		assert.Equal(t, models[1].Name, "Brand Name1", "")
		assert.Equal(t, models[2].Type, TYPE_SUPPLIER, "")
		assert.Equal(t, models[2].Name, "Supplier NameEn1", "")
		assert.Equal(t, models[3].Type, TYPE_HIGHLIGHT, "")
		assert.Equal(t, models[3].Name, "Highlight NameDisplay1", "")
	}
}

func TestResolveParamsSomeParts(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.resolveParams("/shop-category3/url-key-brand3/", "", "", []string{})
	if assert.Equal(t, len(models), 3, "") {
		assert.Equal(t, models[0].Type, TYPE_URL_MAPPING, "")
		assert.Equal(t, models[0].URLKey, "/url-key-supplier3/url-key-highlight3/", "")
		assert.Equal(t, models[1].Type, TYPE_CATEGORY, "")
		assert.Equal(t, models[1].Name, "Category NameEn3", "")
		assert.Equal(t, models[2].Type, TYPE_BRAND, "")
		assert.Equal(t, models[2].Name, "Brand Name3", "")
	}
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category3/url-key-brand3", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category3", "url-key-brand3"}, "")
		assert.Equal(t, len(r.State[0].Models), 3, "")
	}
}

func TestResolveParamsSomePartsWithExtraDelimiter(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.resolveParams("/shop-category1/url-key-brand1--url-key-brand2/", "", "", []string{})
	if assert.Equal(t, len(models), 3, "") {
		assert.Equal(t, models[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, models[0].Name, "Category NameEn1", "")
		assert.Equal(t, models[1].Type, TYPE_BRAND, "")
		assert.Equal(t, models[1].Name, "Brand Name1", "")
		assert.Equal(t, models[2].Type, TYPE_BRAND, "")
		assert.Equal(t, models[2].Name, "Brand Name2", "")
	}
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category1/url-key-brand1/url-key-brand2", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1", "url-key-brand1", "url-key-brand2"}, "")
		assert.Equal(t, len(r.State[0].Models), 3, "")
	}
}

func TestResolveParamsNoCheckSearch(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.resolveParams("/url-key-brand3/", "Category Name1", "", []string{})
	if assert.Equal(t, len(models), 2, "") {
		assert.Equal(t, models[0].Type, TYPE_URL_MAPPING, "")
		assert.Equal(t, models[0].URLKey, "/url-key-supplier3/", "")
		assert.Equal(t, models[1].Type, TYPE_BRAND, "")
		assert.Equal(t, models[1].Name, "Brand Name3", "")
	}
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "url-key-brand3", "")
		assert.Equal(t, r.State[0].Search, "category name1", "")
		assert.Equal(t, r.State[0].Keys, []string{"url-key-brand3"}, "")
		assert.Equal(t, len(r.State[0].Models), 2, "")
	}
}

// We should not redirect with search keywords if category was resolved (search suggestions case)
func TestResolveParamsNoKeywordRedirectWithCategoryKey(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.resolveParams("", "category3 brand3", "", []string{"shop-category1"})
	if assert.Equal(t, len(models), 1, "") {
		assert.Equal(t, models[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, models[0].Name, "Category NameEn1", "")
	}
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "category3 brand3", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		assert.Equal(t, len(r.State[0].Models), 1, "")
	}
}

// We should not redirect with search keywords if category was resolved (search suggestions case)
func TestResolveParamsNoKeywordRedirectWithCategoryPath(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.resolveParams("/shop-category1/", "category3 brand3", "", []string{})
	if assert.Equal(t, len(models), 1, "") {
		assert.Equal(t, models[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, models[0].Name, "Category NameEn1", "")
	}
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category1", "")
		assert.Equal(t, r.State[0].Search, "category3 brand3", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		assert.Equal(t, len(r.State[0].Models), 1, "")
	}
}

func TestResolveParamsCategoryWithFilter(t *testing.T) {
	r := getTestResolver("en", false, true)
	models := r.resolveParams("/shop-category1/?price=100-200", "", "", []string{})
	if assert.Equal(t, len(models), 1, "") {
		assert.Equal(t, models[0].Type, TYPE_CATEGORY, "")
		assert.Equal(t, models[0].Name, "Category NameEn1", "")
	}
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category1", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Query, "price=100-200", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		assert.Equal(t, len(r.State[0].Models), 1, "")
	}
}

func TestIsPathProcessed(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.processedPaths["shop-category1/url-key-brand1"] = true
	r.processedPaths["shop-category1"] = true
	r.processedPaths["url-key-brand1"] = true
	assert.Equal(t, r.isPathProcessed("shop-category1"), true, "")
	assert.Equal(t, r.isPathProcessed("shop-category2"), false, "")
	assert.Equal(t, r.isPathProcessed("url-key-brand1"), true, "")
	assert.Equal(t, r.isPathProcessed("url-key-brand2"), false, "")
	assert.Equal(t, r.isPathProcessed("shop-category1/url-key-brand1"), true, "")
	assert.Equal(t, r.isPathProcessed("shop-category1/url-key-brand2"), false, "")
}

func TestGetKeysToProcess(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.processedKeys["shop-category1"] = true
	r.processedKeys["url-key-brand1"] = true
	assert.Equal(t, r.getKeysToProcess([]string{"shop-category1"}), []string{}, "")
	assert.Equal(t, r.getKeysToProcess([]string{"shop-category2"}), []string{"shop-category2"}, "")
	assert.Equal(t, r.getKeysToProcess([]string{"url-key-brand1"}), []string{}, "")
	assert.Equal(t, r.getKeysToProcess([]string{"url-key-brand2"}), []string{"url-key-brand2"}, "")
}

func TestIsSearchProcessed(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.processedSearch["apple"] = true
	r.processedSearch["samsung"] = true
	r.processedSearch["samsung s8"] = true
	assert.Equal(t, r.isSearchProcessed("apple"), true, "")
	assert.Equal(t, r.isSearchProcessed("samsung white"), false, "")
	assert.Equal(t, r.isSearchProcessed("samsung s8"), true, "")
	assert.Equal(t, r.isSearchProcessed("apple 8 plus"), false, "")
}

func TestNewResolveParamsOneStepToBrand(t *testing.T) {
	r := getTestResolver("en", false, true)
	_ = r.resolveParams("", "Brand Name1", "", []string{})
	isNeedResolve, path, search, query := r.newResolveParams()
	assert.Equal(t, isNeedResolve, false, "")
	assert.Equal(t, path, "", "")
	assert.Equal(t, search, "", "")
	assert.Equal(t, query, "", "")
}

func TestResolveOneStepToBrand(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "Brand Name1", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "brand name1", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_BRAND, "")
			assert.Equal(t, r.State[0].Models[0].Name, "Brand Name1", "")
		}
	}
}

func TestNewResolveParamsKeywordToCategory(t *testing.T) {
	r := getTestResolver("en", false, true)
	_ = r.resolveParams("", "some other name of category1", "", []string{})
	isNeedResolve, path, search, query := r.newResolveParams()
	assert.Equal(t, isNeedResolve, true, "")
	assert.Equal(t, path, "shop-category1", "")
	assert.Equal(t, search, "", "")
	assert.Equal(t, query, "", "")
	//	assert.Equal(t, keys, []string{"shop-category1"}, "")

	_ = r.resolveParams("shop-category1", "", "", []string{"shop-category1"})
	isNeedResolve, path, search, query = r.newResolveParams()
	assert.Equal(t, isNeedResolve, false, "")
	assert.Equal(t, path, "", "")
	assert.Equal(t, search, "", "")
	assert.Equal(t, query, "", "")
}

func TestResolveKeywordToCategory(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "some other name of category1", []string{})
	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "some other name of category1", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/shop-category1/", "")
		}
		assert.Equal(t, r.State[1].Path, "shop-category1", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[1].Models), 1, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[1].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestNewResolveParamsKeywordToKeyword(t *testing.T) {
	r := getTestResolver("en", false, true)
	_ = r.resolveParams("", "keyword to keyword", "", []string{})
	isNeedResolve, path, search, query := r.newResolveParams()
	assert.Equal(t, isNeedResolve, true, "")
	assert.Equal(t, path, "", "")
	assert.Equal(t, search, "new keyword", "")
	assert.Equal(t, query, "", "")

	_ = r.resolveParams("", "new keyword", "", []string{})
	isNeedResolve, path, search, query = r.newResolveParams()
	assert.Equal(t, isNeedResolve, false, "")
}

func TestResolveKeywordToKeyword(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "keyword to keyword", []string{})
	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "keyword to keyword", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "?q=new keyword", "")
		}
		assert.Equal(t, r.State[1].Path, "", "")
		assert.Equal(t, r.State[1].Search, "new keyword", "")
		assert.Nil(t, r.State[1].Keys, "")
		assert.Equal(t, len(r.State[1].Models), 0, "")
	}
}

func TestNewResolveParamsKeywordToCategoryAndKeyword(t *testing.T) {
	r := getTestResolver("en", false, true)
	_ = r.resolveParams("", "keyword to category and keyword", "", []string{})
	isNeedResolve, path, search, query := r.newResolveParams()
	assert.Equal(t, isNeedResolve, true, "")
	assert.Equal(t, path, "shop-category1", "")
	assert.Equal(t, search, "new keyword", "")
	assert.Equal(t, query, "", "")

	_ = r.resolveParams("shop-category1", "new keyword", "", []string{"shop-category1"})
	isNeedResolve, path, search, query = r.newResolveParams()
	assert.Equal(t, isNeedResolve, false, "")
}

func TestResolveKeywordToCategoryAndKeyword(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "keyword to category and keyword", []string{})
	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "keyword to category and keyword", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/shop-category1?q=new keyword", "")
		}
		assert.Equal(t, r.State[1].Path, "shop-category1", "")
		assert.Equal(t, r.State[1].Search, "new keyword", "")
		assert.Equal(t, r.State[1].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[1].Models), 1, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[1].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestResolveKeywordToCategoryAndFilter(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "keyword to category and filters", []string{})
	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "keyword to category and filters", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/shop-category1?price=100-200&color_family=Black", "")
		}
		assert.Equal(t, r.State[1].Path, "shop-category1", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Query, "color_family=Black&price=100-200", "")
		assert.Equal(t, r.State[1].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[1].Models), 1, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[1].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestNewResolveParamsKeywordToSomeURLAndThenToUnresolvedPage(t *testing.T) {
	r := getTestResolver("en", false, true)
	_ = r.resolveParams("", "some phrase", "", []string{})
	isNeedResolve, path, search, query := r.newResolveParams()
	assert.Equal(t, isNeedResolve, true, "")
	assert.Equal(t, path, "some-url", "")
	assert.Equal(t, search, "", "")
	assert.Equal(t, query, "", "")

	_ = r.resolveParams("some-url", "", "", []string{"some-url"})
	isNeedResolve, path, search, query = r.newResolveParams()
	assert.Equal(t, isNeedResolve, true, "")
	assert.Equal(t, path, "url-to-not-found-page", "")
	assert.Equal(t, search, "", "")
	assert.Equal(t, query, "", "")

	models := r.resolveParams("url-to-not-found-page", "", "", []string{"url-to-not-found-page"})
	isNeedResolve, path, search, query = r.newResolveParams()
	assert.Equal(t, isNeedResolve, false, "")
	assert.Equal(t, len(models), 0, "")
}

func TestResolveKeywordToSomeURLAndThenToUnresolvedPage(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "some phrase", []string{})
	if assert.Equal(t, len(r.State), 3, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "some phrase", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/some-url/", "")
		}
		assert.Equal(t, r.State[1].Path, "some-url", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Keys, []string{"some-url"}, "")
		if assert.Equal(t, len(r.State[1].Models), 1, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_URL_MAPPING, "")
			assert.Equal(t, r.State[1].Models[0].URLKey, "/url-to-not-found-page/", "")
		}
		assert.Equal(t, r.State[2].Path, "url-to-not-found-page", "")
		assert.Equal(t, r.State[2].Search, "", "")
		assert.Equal(t, r.State[2].Keys, []string{"url-to-not-found-page"}, "")
		assert.Equal(t, len(r.State[2].Models), 0, "")
	}
}

func TestResolveCategoryByPath(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("shop-category1/", "", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category1", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[0].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestResolveCategoryByKey(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "", []string{"shop-category1"})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[0].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestResolveCategoryBySearch(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "Category Name1", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "category name1", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[0].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestResolveCategoryByPathAndSupplierBySearch(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("shop-category1/", "Supplier Name2", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category1", "")
		assert.Equal(t, r.State[0].Search, "supplier name2", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[0].Models), 2, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[0].Models[0].Name, "Category NameEn1", "")
			assert.Equal(t, r.State[0].Models[1].Type, TYPE_SUPPLIER, "")
			assert.Equal(t, r.State[0].Models[1].Name, "Supplier NameEn2", "")
		}
	}
}

func TestResolveCategoryAndKeywordRedirectDisable(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("shop-category1/", "some phras", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "shop-category1", "")
		assert.Equal(t, r.State[0].Search, "some phras", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category1"}, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[0].Models[0].Name, "Category NameEn1", "")
		}
	}
}

func TestResolveCategoryAndBrandRedirect(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("/shop-category3/url-key-brand3/", "", []string{})
	if assert.Equal(t, len(r.State), 3, "") {
		assert.Equal(t, r.State[0].Path, "shop-category3/url-key-brand3", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"shop-category3", "url-key-brand3"}, "")
		if assert.Equal(t, len(r.State[0].Models), 3, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_URL_MAPPING, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/url-key-supplier3/url-key-highlight3/", "")
			assert.Equal(t, r.State[0].Models[1].Type, TYPE_CATEGORY, "")
			assert.Equal(t, r.State[0].Models[1].Name, "Category NameEn3", "")
			assert.Equal(t, r.State[0].Models[2].Type, TYPE_BRAND, "")
			assert.Equal(t, r.State[0].Models[2].Name, "Brand Name3", "")
		}
		assert.Equal(t, r.State[1].Path, "url-key-supplier3/url-key-highlight3", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Query, "", "")
		assert.Equal(t, r.State[1].Keys, []string{"url-key-highlight3", "url-key-supplier3"}, "")
		if assert.Equal(t, len(r.State[1].Models), 3, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_URL_MAPPING, "")
			assert.Equal(t, r.State[1].Models[0].URLKey, "/url-key-highlight3/url-key-highlight3/", "")
			assert.Equal(t, r.State[1].Models[1].Type, TYPE_SUPPLIER, "")
			assert.Equal(t, r.State[1].Models[1].Name, "Supplier NameEn3", "")
			assert.Equal(t, r.State[1].Models[2].Type, TYPE_HIGHLIGHT, "")
			assert.Equal(t, r.State[1].Models[2].Name, "Highlight NameDisplay3", "")
		}
		assert.Equal(t, r.State[2].Path, "url-key-highlight3/url-key-highlight3", "")
		assert.Equal(t, r.State[2].Search, "", "")
		assert.Equal(t, r.State[2].Query, "", "")
		assert.Equal(t, r.State[2].Keys, []string{"url-key-highlight3"}, "")
		if assert.Equal(t, len(r.State[2].Models), 1, "") {
			assert.Equal(t, r.State[2].Models[0].Type, TYPE_HIGHLIGHT, "")
			assert.Equal(t, r.State[2].Models[0].Name, "Highlight NameDisplay3", "")
		}
	}
}

func TestResolveLoopKeywordAndURLMapping(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "loop redirect", []string{})
	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "loop redirect", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/loop-redirect/", "")
		}
		assert.Equal(t, r.State[1].Path, "loop-redirect", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Query, "", "")
		assert.Equal(t, r.State[1].Keys, []string{"loop-redirect"}, "")
		if assert.Equal(t, len(r.State[1].Models), 1, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_URL_MAPPING, "")
			assert.Equal(t, r.State[1].Models[0].URLKey, "?q=loop redirect", "")
		}
	}
}

func TestResolveRedirectURLToURLMapToURLMapOfStaticPage(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("/key1/key2/", "", []string{})
	if assert.Equal(t, len(r.State), 3, "") {
		assert.Equal(t, r.State[0].Path, "key1/key2", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"key1", "key2"}, "")
		if assert.Equal(t, len(r.State[0].Models), 3, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_STATIC_PAGE, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/key1/key2/", "")
			assert.Equal(t, r.State[0].Models[1].Type, TYPE_URL_MAPPING, "")
			assert.Equal(t, r.State[0].Models[1].URLKey, "/key3/key4/", "")
			assert.Equal(t, r.State[0].Models[2].Type, TYPE_HIGHLIGHT, "")
			assert.Equal(t, r.State[0].Models[2].URLKey, "key2", "")
		}
		assert.Equal(t, r.State[1].Path, "key3/key4", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Query, "", "")
		assert.Equal(t, r.State[1].Keys, []string{"key3", "key4"}, "")
		if assert.Equal(t, len(r.State[1].Models), 2, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_STATIC_PAGE, "")
			assert.Equal(t, r.State[1].Models[0].URLKey, "/key3/key4/", "")
			assert.Equal(t, r.State[1].Models[1].Type, TYPE_URL_MAPPING, "")
			assert.Equal(t, r.State[1].Models[1].URLKey, "/key3#anchor", "")
		}
		assert.Equal(t, r.State[2].Path, "key3", "")
		assert.Equal(t, r.State[2].Search, "", "")
		assert.Equal(t, r.State[2].Query, "", "")
		assert.Equal(t, r.State[2].Keys, []string{"key3"}, "")
		if assert.Equal(t, len(r.State[2].Models), 1, "") {
			assert.Equal(t, r.State[2].Models[0].Type, TYPE_STATIC_PAGE, "")
			assert.Equal(t, r.State[2].Models[0].URLKey, "/key3/", "")
		}
	}
}

func TestResolveRedirectURLToURLMapToURLMapOfStaticPageRedirectNotAllowed(t *testing.T) {
	r := getTestResolver("en", false, false)
	r.Resolve("/key1/key2/", "", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "key1/key2", "")
		assert.Equal(t, r.State[0].Search, "", "")
		assert.Equal(t, r.State[0].Keys, []string{"key1", "key2"}, "")
		if assert.Equal(t, len(r.State[0].Models), 2, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_STATIC_PAGE, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/key1/key2/", "")
			assert.Equal(t, r.State[0].Models[1].Type, TYPE_HIGHLIGHT, "")
			assert.Equal(t, r.State[0].Models[1].URLKey, "key2", "")
		}
	}
}

func TestResolveLoopKeywordAndURLMappingRedirectNotAllowed(t *testing.T) {
	r := getTestResolver("en", false, false)
	r.Resolve("", "loop redirect", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "loop redirect", "")
		assert.Nil(t, r.State[0].Keys, "")
		assert.Equal(t, len(r.State[0].Models), 0, "")
	}
}

func TestResolveKeywordAndRedirectNotAllowedButIsRedirectExclusion(t *testing.T) {
	r := getTestResolver("en", false, false)
	r.Resolve("", "top up", []string{})
	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "top up", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/mobilerecharge/", "")
		}
		assert.Equal(t, r.State[1].Path, "mobilerecharge", "")
		assert.Equal(t, r.State[1].Search, "", "")
		assert.Equal(t, r.State[1].Keys, []string{"mobilerecharge"})
		assert.Equal(t, len(r.State[1].Models), 0, "")
	}
}

func TestResolveLoopKeywordToBrandWithSameKeyword1(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("", "s8", []string{})

	if assert.Equal(t, len(r.State), 2, "") {
		assert.Equal(t, r.State[0].Path, "", "")
		assert.Equal(t, r.State[0].Search, "s8", "")
		assert.Nil(t, r.State[0].Keys, "")
		if assert.Equal(t, len(r.State[0].Models), 1, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "/samsung/?q=s8", "")
		}
		assert.Equal(t, r.State[1].Path, "samsung", "")
		assert.Equal(t, r.State[1].Query, "q=s8", "")
		assert.Equal(t, r.State[1].Search, "s8", "")
		assert.Equal(t, r.State[1].Keys, []string{"samsung"})
		if assert.Equal(t, len(r.State[1].Models), 2, "") {
			assert.Equal(t, r.State[1].Models[0].Type, TYPE_BRAND, "")
			assert.Equal(t, r.State[1].Models[0].URLKey, "samsung", "")

			assert.Equal(t, r.State[1].Models[1].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[1].Models[1].URLKey, "/samsung/?q=s8", "")
		}
	}
}

func TestResolveLoopKeywordToBrandWithSameKeyword2(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("samsung", "s8", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "samsung", "")
		assert.Equal(t, r.State[0].Query, "", "")
		assert.Equal(t, r.State[0].Search, "s8", "")
		assert.Equal(t, r.State[0].Keys, []string{"samsung"})
		if assert.Equal(t, len(r.State[0].Models), 2, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_BRAND, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "samsung", "")

			assert.Equal(t, r.State[0].Models[1].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[1].URLKey, "/samsung/?q=s8", "")
		}
	}
}

func TestResolveLoopKeywordToBrandWithSameKeywordAndUpcase(t *testing.T) {
	r := getTestResolver("en", false, true)
	r.Resolve("samsung", "S8", []string{})
	if assert.Equal(t, len(r.State), 1, "") {
		assert.Equal(t, r.State[0].Path, "samsung", "")
		assert.Equal(t, r.State[0].Query, "", "")
		assert.Equal(t, r.State[0].Search, "s8", "")
		assert.Equal(t, r.State[0].Keys, []string{"samsung"})
		if assert.Equal(t, len(r.State[0].Models), 2, "") {
			assert.Equal(t, r.State[0].Models[0].Type, TYPE_BRAND, "")
			assert.Equal(t, r.State[0].Models[0].URLKey, "samsung", "")

			assert.Equal(t, r.State[0].Models[1].Type, TYPE_KEYWORD, "")
			assert.Equal(t, r.State[0].Models[1].URLKey, "/samsung/?q=s8", "")
		}
	}
}
