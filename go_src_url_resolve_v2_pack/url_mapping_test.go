package urlv2

import (
	"testing"

	"content_api/model/url_mapping"
	"content_api/services/search_helper/tools"
	"github.com/stretchr/testify/assert"
)

var u1 = &url_mapping.UrlMappingRow{
	RequestPath: "/shop-category3/",
	Target:      "/url-key-brand3/",
	HTTPCode:    302,
	MobAPI:      false,
}
var u2 = &url_mapping.UrlMappingRow{
	RequestPath: "/url-key-brand3/",
	Target:      "/url-key-supplier3/",
	HTTPCode:    302,
	MobAPI:      true,
}
var u3 = &url_mapping.UrlMappingRow{
	RequestPath: "/url-key-supplier3/",
	Target:      "/url-key-highlight3/",
	HTTPCode:    302,
	MobAPI:      true,
}
var u4 = &url_mapping.UrlMappingRow{
	RequestPath: "/shop-category3/url-key-brand3/",
	Target:      "/url-key-supplier3/url-key-highlight3/",
	HTTPCode:    302,
	MobAPI:      true,
}
var u5 = &url_mapping.UrlMappingRow{
	RequestPath: "/some-url/",
	Target:      "/url-to-not-found-page/",
	HTTPCode:    302,
	MobAPI:      true,
}
var u6 = &url_mapping.UrlMappingRow{
	RequestPath: "/loop-redirect/",
	Target:      "?q=loop redirect",
	HTTPCode:    302,
	MobAPI:      true,
}
var u7 = &url_mapping.UrlMappingRow{
	RequestPath: "/key1/key2/",
	Target:      "/key3/key4/",
	HTTPCode:    302,
	MobAPI:      true,
}
var u8 = &url_mapping.UrlMappingRow{
	RequestPath: "/key3/key4/",
	Target:      "/key3#anchor",
	HTTPCode:    302,
	MobAPI:      true,
}
var u9 = &url_mapping.UrlMappingRow{
	RequestPath: "/shop-category4/",
	Target:      "/shop-category1/url-key-brand4/?price=100-200&sort=deliverytime",
	HTTPCode:    302,
	MobAPI:      false,
}

var uCache = &url_mapping.UrlMappingCache{
	tools.CleanURL(u1.RequestPath): u1,
	tools.CleanURL(u2.RequestPath): u2,
	tools.CleanURL(u3.RequestPath): u3,
	tools.CleanURL(u4.RequestPath): u4,
	tools.CleanURL(u5.RequestPath): u5,
	tools.CleanURL(u6.RequestPath): u6,
	tools.CleanURL(u7.RequestPath): u7,
	tools.CleanURL(u8.RequestPath): u8,
	tools.CleanURL(u9.RequestPath): u9,
}

func TestURLMappingRedirectByPathLoopNotMobapi(t *testing.T) {
	keys := GetURLMappingResolver(uCache, "en", false).RedirectByPath("shop-category3/")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-brand3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}

	keys = GetURLMappingResolver(uCache, "en", false).RedirectByPath("/url-key-brand3")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-supplier3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}

	keys = GetURLMappingResolver(uCache, "en", false).RedirectByPath("/url-key-supplier3/")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-highlight3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}
}

func TestURLMappingRedirectByPathLoopForMobapi(t *testing.T) {
	assert.Nil(t, GetURLMappingResolver(uCache, "en", true).RedirectByPath("shop-category3"), "")

	keys := GetURLMappingResolver(uCache, "en", true).RedirectByPath("/url-key-brand3")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-supplier3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}

	keys = GetURLMappingResolver(uCache, "en", true).RedirectByPath("/url-key-supplier3/")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-highlight3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}
}

func TestURLMappingRedirectByPathTwoUrlKeys(t *testing.T) {
	keys := GetURLMappingResolver(uCache, "en", false).RedirectByPath("/shop-category3/url-key-brand3")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-supplier3/url-key-highlight3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}
}

func TestURLMappingRedirectByPathTwoDuplicatedUrlKeys(t *testing.T) {
	keys := GetURLMappingResolver(uCache, "en", false).RedirectByPath("/url-key-supplier3/url-key-highlight3")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-highlight3/url-key-highlight3/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}
}

func TestURLMappingRedirectByPathWithReplaceFirstPart(t *testing.T) {
	keys := GetURLMappingResolver(uCache, "en", false).RedirectByPath("/shop-category3/url-key-brand1")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-brand3/url-key-brand1/", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}
}

func TestURLMappingRedirectByPathWithReplaceFirstPartWithFilter(t *testing.T) {
	keys := GetURLMappingResolver(uCache, "en", false).RedirectByPath("/shop-category4/url-key-brand1")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/shop-category1/url-key-brand4/url-key-brand1/?price=100-200&sort=deliverytime", "")
		assert.Equal(t, keys[0].Type, TYPE_URL_MAPPING, "")
	}
}

func TestURLMappingResolveKeys(t *testing.T) {
	assert.Nil(t, GetURLMappingResolver(uCache, "en", true).ResolveKeys([]string{"url-key-brand1"}), "")
}

func TestURLMappingRedirectBySearch(t *testing.T) {
	assert.Nil(t, GetURLMappingResolver(uCache, "en", true).RedirectBySearch("Brand Name2"), "")
}
