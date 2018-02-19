package search_service

import (
	"testing"

	"github.com/stretchr/testify/assert"

	"mobile_search_api/api/ext_services/content_api/gentlejson"
	"mobile_search_api/api/ext_services/content_api/model/search"
)

func TestFindCategoryIDInRequestDump(t *testing.T) {
	result := findCategoryIDInRequestDump(&search.RequestDump{
		Facets: map[string][]string{
			"category": []string{"123", "231"},
		},
	})
	assert.Equal(t, result, "123,231")
}

func TestConvertHitsToAuctions(t *testing.T) {
	specialPrice2 := gentlejson.Price(158.77)
	specialPrice3 := gentlejson.Price(258.77)
	auctions := convertHitsToAuctions([]search.Product{
		{
			SKU:             "sku1",
			PrimaryCategory: gentlejson.Int(123),
			Meta: search.ProductMeta{
				Price: gentlejson.Price(199.9),
			},
		},
		{
			SKU:             "sku2",
			PrimaryCategory: gentlejson.Int(231),
			Meta: search.ProductMeta{
				Price:        gentlejson.Price(177.9),
				SpecialPrice: &specialPrice2,
			},
		},
		{
			SKU:             "sku3",
			PrimaryCategory: gentlejson.Int(213),
			Meta: search.ProductMeta{
				Price:        gentlejson.Price(200),
				SpecialPrice: &specialPrice3,
			},
		},
	})

	assert.Equal(t, auctions, "sku1:199.90:123,sku2:158.77:231,sku3:200.00:213")
}

func TestFindApp(t *testing.T) {
	assert.Equal(t, findApp("Lazada/5.0 (iPhone; iOS 9.3.2; Scale/3.00)"), "iphone")
	assert.Equal(t, findApp("Lazada/5.0 (iPod touch; iOS 7.1.2; Scale/2.00)"), "ipod")
	assert.Equal(t, findApp("Dalvik/1.6.0 (Linux; U; Android 4.0.3; MediaPad 7 Lite Build/HuaweiMediaPad)"), "android")
	assert.Equal(t, findApp("Dalvik/1.6a (Linux; U; Android 4.0.3; Next7P12 Build/IML74K)"), "android")
	assert.Equal(t, findApp("iOS"), "ios")
	assert.Equal(t, findApp("test"), "test")
	assert.Equal(t, findApp("Mozilla/5.0 (Linux; U; Android 4.1.1; en-us; ALCATEL ONE TOUCH 6033X Build/JRO03C) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.1 Mobile Safari/534.30"), "android")
	assert.Equal(t, findApp("okhttp/2.5.0"), "android")
}
