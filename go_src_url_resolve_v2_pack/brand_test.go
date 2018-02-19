package urlv2

import (
	"testing"

	"content_api/model/catalog/brand"
	"content_api/services/search_helper/tools"
	"github.com/stretchr/testify/assert"
)

var b1 = &brand.CatalogBrandRow{
	IDCatalogBrand: 1,
	RegionalKey:    "00111001",
	Name:           "Brand Name1",
	NameEn:         "Brand NameEn1",
	UrlKey:         "url-key-brand1",
	BrandRedirect:  1,
}
var b2 = &brand.CatalogBrandRow{
	IDCatalogBrand: 2,
	RegionalKey:    "00111002",
	Name:           "Brand Name2",
	NameEn:         "Brand NameEn2",
	UrlKey:         "url-key-brand2",
	BrandRedirect:  1,
}
var b3 = &brand.CatalogBrandRow{
	IDCatalogBrand: 3,
	RegionalKey:    "00111003",
	Name:           "Brand Name3",
	NameEn:         "Brand NameEn3",
	UrlKey:         "url-key-brand3",
	BrandRedirect:  1,
}
var b4 = &brand.CatalogBrandRow{
	IDCatalogBrand: 4,
	RegionalKey:    "00111004",
	Name:           "Brand Name4",
	NameEn:         "Brand NameEn4",
	UrlKey:         "url-key-brand4",
	BrandRedirect:  0,
}

var b5 = &brand.CatalogBrandRow{
	IDCatalogBrand: 4,
	RegionalKey:    "00111004",
	Name:           "Samsung",
	NameEn:         "Samsung",
	UrlKey:         "samsung",
	BrandRedirect:  0,
}

var bCache = &brand.CatalogBrandCache{
	Brands: []*brand.CatalogBrandRow{b1, b2, b3, b4, b5},
	BrandsByID: map[uint64]*brand.CatalogBrandRow{
		b1.IDCatalogBrand: b1,
		b3.IDCatalogBrand: b3,
		b2.IDCatalogBrand: b2,
		b4.IDCatalogBrand: b4,
		b5.IDCatalogBrand: b5,
	},
	BrandsByName: map[string]*brand.CatalogBrandRow{
		tools.PrepareCleanId(b1.Name): b1,
		tools.PrepareCleanId(b2.Name): b2,
		tools.PrepareCleanId(b3.Name): b3,
		tools.PrepareCleanId(b4.Name): b4,
		tools.PrepareCleanId(b5.Name): b5,
	},
	BrandsByNameEN: map[string]*brand.CatalogBrandRow{
		tools.PrepareCleanId(b1.NameEn): b1,
		tools.PrepareCleanId(b2.NameEn): b2,
		tools.PrepareCleanId(b3.NameEn): b3,
		tools.PrepareCleanId(b4.NameEn): b4,
		tools.PrepareCleanId(b5.NameEn): b5,
	},
	BrandsByURL: map[string][]*brand.CatalogBrandRow{
		tools.PrepareCleanId(b1.UrlKey): []*brand.CatalogBrandRow{b1},
		tools.PrepareCleanId(b2.UrlKey): []*brand.CatalogBrandRow{b2},
		tools.PrepareCleanId(b3.UrlKey): []*brand.CatalogBrandRow{b3},
		tools.PrepareCleanId(b4.UrlKey): []*brand.CatalogBrandRow{b4},
		tools.PrepareCleanId(b5.UrlKey): []*brand.CatalogBrandRow{b5},
	},
}

func TestBrandRedirectByPath(t *testing.T) {
	assert.Nil(t, GetBrandResolver(bCache, "en").RedirectByPath("url-key-brand1"), "")
}

func TestBrandResolveKeys(t *testing.T) {
	keys := GetBrandResolver(bCache, "en").ResolveKeys([]string{"url-key-brand1"})
	if assert.NotNil(t, keys, "") {
		assert.Equal(t, len(keys), 1, "")
		assert.Equal(t, int(keys[0].ID), 1, "")
	}
}

func TestBrandResolveKeysUnknown(t *testing.T) {
	keys := GetBrandResolver(bCache, "en").ResolveKeys([]string{"shop-category100", "url-key-brand1111"})
	assert.Nil(t, keys, "")
}

func TestBrandRedirectBySearch(t *testing.T) {
	keys := GetBrandResolver(bCache, "en").RedirectBySearch("Brand Name2")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, int(keys[0].ID), 2, "")
		assert.Equal(t, keys[0].Type, TYPE_BRAND, "")
	}
}

func TestBrandRedirectBySearchRedirectDisabled(t *testing.T) {
	assert.Nil(t, GetBrandResolver(bCache, "en").RedirectBySearch("Brand Name4"), "")
}
