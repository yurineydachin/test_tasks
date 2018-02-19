package urlv2

import (
	"testing"

	"content_api/model/catalog/category"
	"content_api/services/search_helper/tools"
	"github.com/stretchr/testify/assert"
)

var cat1 = &category.CatalogCategoryRow{
	IdCatalogCategory: 1,
	RegionalKey:       "01000001",
	Name:              "Category Name1",
	NameEn:            "Category NameEn1",
	UrlKey:            "shop-category1",
	CategoryRedirect:  true,
}
var cat2 = &category.CatalogCategoryRow{
	IdCatalogCategory: 2,
	RegionalKey:       "02000002",
	Name:              "Category Name2",
	NameEn:            "Category NameEn2",
	UrlKey:            "shop-category2",
	CategoryRedirect:  true,
}
var cat3 = &category.CatalogCategoryRow{
	IdCatalogCategory: 3,
	RegionalKey:       "03000003",
	Name:              "Category Name3",
	NameEn:            "Category NameEn3",
	UrlKey:            "shop-category3",
	CategoryRedirect:  true,
}
var cat4 = &category.CatalogCategoryRow{
	IdCatalogCategory: 4,
	RegionalKey:       "03000004",
	Name:              "Category Name4",
	NameEn:            "Category NameEn4",
	UrlKey:            "shop-category4",
	CategoryRedirect:  false,
}

var catCache = &category.CatalogCategoryCache{
	ByID: map[uint64][]*category.CatalogCategoryRow{
		cat1.IdCatalogCategory: []*category.CatalogCategoryRow{cat1},
		cat2.IdCatalogCategory: []*category.CatalogCategoryRow{cat2},
		cat3.IdCatalogCategory: []*category.CatalogCategoryRow{cat3},
		cat4.IdCatalogCategory: []*category.CatalogCategoryRow{cat4},
	},
	Name: map[string][]*category.CatalogCategoryRow{
		tools.PrepareCleanId(cat1.Name): []*category.CatalogCategoryRow{cat1},
		tools.PrepareCleanId(cat2.Name): []*category.CatalogCategoryRow{cat2},
		tools.PrepareCleanId(cat3.Name): []*category.CatalogCategoryRow{cat3},
		tools.PrepareCleanId(cat4.Name): []*category.CatalogCategoryRow{cat4},
	},
	NameEn: map[string][]*category.CatalogCategoryRow{
		tools.PrepareCleanId(cat1.NameEn): []*category.CatalogCategoryRow{cat1},
		tools.PrepareCleanId(cat2.NameEn): []*category.CatalogCategoryRow{cat2},
		tools.PrepareCleanId(cat3.NameEn): []*category.CatalogCategoryRow{cat3},
		tools.PrepareCleanId(cat4.NameEn): []*category.CatalogCategoryRow{cat4},
	},
	Url: map[string][]*category.CatalogCategoryRow{
		tools.PrepareCleanId(cat1.UrlKey): []*category.CatalogCategoryRow{cat1},
		tools.PrepareCleanId(cat2.UrlKey): []*category.CatalogCategoryRow{cat2},
		tools.PrepareCleanId(cat3.UrlKey): []*category.CatalogCategoryRow{cat3},
		tools.PrepareCleanId(cat4.UrlKey): []*category.CatalogCategoryRow{cat4},
	},
}

func TestCategoryRedirectByPath(t *testing.T) {
	assert.Nil(t, GetCategoryResolver(catCache, "en").RedirectByPath("shop-category1"), "")
}

func TestCategoryResolveKeys(t *testing.T) {
	keys := GetCategoryResolver(catCache, "en").ResolveKeys([]string{"shop-category1"})
	if assert.NotNil(t, keys, "") {
		assert.Equal(t, len(keys), 1, "")
		assert.Equal(t, int(keys[0].ID), 1, "")
	}
}

func TestCategoryResolveKeysUnknown(t *testing.T) {
	keys := GetCategoryResolver(catCache, "en").ResolveKeys([]string{"shop-category100", "shop-category200"})
	assert.Nil(t, keys, "")
}

func TestCategoryRedirectBySearch(t *testing.T) {
	rows := catCache.GetByNames([]string{"Category Name2"}, true)
	if assert.NotNil(t, rows, "") && assert.Equal(t, len(rows), 1, "") {
		assert.Equal(t, int(rows[0].IdCatalogCategory), 2, "")
	}
	keys := GetCategoryResolver(catCache, "en").RedirectBySearch("Category Name2")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, int(keys[0].ID), 2, "")
		assert.Equal(t, keys[0].Type, TYPE_CATEGORY, "")
	}
}

func TestCategoryRedirectBySearchRedirectDisable(t *testing.T) {
	assert.Equal(t, len(catCache.GetByNames([]string{"Category Name4"}, true)), 0, "")
	rows := catCache.GetByNames([]string{"Category Name4"}, false)
	if assert.NotNil(t, rows, "") && assert.Equal(t, len(rows), 1, "") {
		assert.Equal(t, int(rows[0].IdCatalogCategory), 4, "")
	}
	assert.Nil(t, GetCategoryResolver(catCache, "en").RedirectBySearch("Category Name4"), "")
}
