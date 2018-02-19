package urlv2

import (
	"testing"

	"content_api/model/suppliers"
	"content_api/services/search_helper/tools"

	"github.com/stretchr/testify/assert"
)

var s1 = suppliers.SupplierRow{
	SupplierID:     1,
	SellercenterID: "1",
	Name:           "Supplier Name1",
	NameEn:         "Supplier NameEn1",
	RegionalKey:    "1110001",
	SearchRedirect: 1,
	UrlKey:         "url-key-supplier1",
}
var s2 = suppliers.SupplierRow{
	SupplierID:     2,
	SellercenterID: "2",
	Name:           "Supplier Name2",
	NameEn:         "Supplier NameEn2",
	RegionalKey:    "1110002",
	SearchRedirect: 1,
	UrlKey:         "url-key-supplier2",
}
var s3 = suppliers.SupplierRow{
	SupplierID:     3,
	SellercenterID: "3",
	Name:           "Supplier Name3",
	NameEn:         "Supplier NameEn3",
	RegionalKey:    "1110003",
	SearchRedirect: 1,
	UrlKey:         "url-key-supplier3",
}
var s4 = suppliers.SupplierRow{
	SupplierID:     4,
	SellercenterID: "4",
	Name:           "Supplier Name4",
	NameEn:         "Supplier NameEn4",
	RegionalKey:    "1110004",
	SearchRedirect: 0,
	UrlKey:         "url-key-supplier4",
}

// This supplier should not collide with s1 on prepared names (w/ and w/o whitespace).
var s11 = suppliers.SupplierRow{
	SupplierID:     11,
	SellercenterID: "11",
	Name:           "SupplierName1",
	NameEn:         "SupplierNameEn1",
	RegionalKey:    "1110011",
	SearchRedirect: 1,
	UrlKey:         "url-key-supplier11",
}

var sCache = &suppliers.SuppliersCache{
	Suppliers: []suppliers.SupplierRow{s1, s2, s3},
	SuppliersByID: map[string][]suppliers.SupplierRow{
		tools.PrepareCleanId("1"):  []suppliers.SupplierRow{s1},
		tools.PrepareCleanId("2"):  []suppliers.SupplierRow{s2},
		tools.PrepareCleanId("3"):  []suppliers.SupplierRow{s3},
		tools.PrepareCleanId("4"):  []suppliers.SupplierRow{s4},
		tools.PrepareCleanId("11"): []suppliers.SupplierRow{s11},
	},
	SuppliersByName: map[string][]suppliers.SupplierRow{
		tools.PrepareCleanId(s1.Name):  []suppliers.SupplierRow{s1},
		tools.PrepareCleanId(s2.Name):  []suppliers.SupplierRow{s2},
		tools.PrepareCleanId(s3.Name):  []suppliers.SupplierRow{s3},
		tools.PrepareCleanId(s4.Name):  []suppliers.SupplierRow{s4},
		tools.PrepareCleanId(s11.Name): []suppliers.SupplierRow{s11},
	},
	SuppliersByNameEn: map[string][]suppliers.SupplierRow{
		tools.PrepareCleanId(s1.NameEn):  []suppliers.SupplierRow{s1},
		tools.PrepareCleanId(s2.NameEn):  []suppliers.SupplierRow{s2},
		tools.PrepareCleanId(s3.NameEn):  []suppliers.SupplierRow{s3},
		tools.PrepareCleanId(s4.NameEn):  []suppliers.SupplierRow{s4},
		tools.PrepareCleanId(s11.NameEn): []suppliers.SupplierRow{s11},
	},
	SuppliersByURL: map[string][]suppliers.SupplierRow{
		tools.PrepareCleanId(s1.UrlKey):  []suppliers.SupplierRow{s1},
		tools.PrepareCleanId(s2.UrlKey):  []suppliers.SupplierRow{s2},
		tools.PrepareCleanId(s3.UrlKey):  []suppliers.SupplierRow{s3},
		tools.PrepareCleanId(s4.UrlKey):  []suppliers.SupplierRow{s4},
		tools.PrepareCleanId(s11.UrlKey): []suppliers.SupplierRow{s11},
	},
}

func TestSupplierRedirectByPath(t *testing.T) {
	assert.Nil(t, GetSupplierResolver(sCache, "en").RedirectByPath("url-key-supplier1"), "")
}

func TestSupplierResolveKeys(t *testing.T) {
	keys := GetSupplierResolver(sCache, "en").ResolveKeys([]string{"url-key-supplier2"})
	if assert.NotNil(t, keys, "") {
		assert.Equal(t, len(keys), 1, "")
		assert.Equal(t, int(keys[0].ID), 2, "")
	}
}

func TestSupplierResolveKeysUnknown(t *testing.T) {
	keys := GetSupplierResolver(sCache, "en").ResolveKeys([]string{"shop-supplier2333", "url-key-brand1111"})
	assert.Nil(t, keys, "")
}

func TestSupplierRedirectBySearch(t *testing.T) {
	keys := GetSupplierResolver(sCache, "en").RedirectBySearch("Supplier Name1")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, int(keys[0].ID), 1, "")
		assert.Equal(t, keys[0].Type, TYPE_SUPPLIER, "")
	}
}

func TestSupplierRedirectBySearchRedirectDisable(t *testing.T) {
	assert.Nil(t, GetSupplierResolver(sCache, "en").RedirectBySearch("Supplier Name4"), "")
}

func TestSupplierRedirectBySearchNamesAreNotCollide(t *testing.T) {
	assert := assert.New(t)
	// Supplier Name1
	{
		keys := GetSupplierResolver(sCache, "en").RedirectBySearch("Supplier Name1")
		assert.Len(keys, 1)
		assert.Equal(int(keys[0].ID), 1)
		assert.Equal(keys[0].Type, TYPE_SUPPLIER)
	}
	// SupplierName1
	{
		keys := GetSupplierResolver(sCache, "en").RedirectBySearch("SupplierName1")
		assert.Len(keys, 1)
		assert.Equal(int(keys[0].ID), 11)
		assert.Equal(keys[0].Type, TYPE_SUPPLIER)
	}
}
