package urlv2

import (
	"testing"

	"content_api/model/cms_folder"
	"content_api/model/page_manager/adapter"
	"content_api/model/page_manager/pagelist"
	"content_api/services/search_helper/tools"
	"github.com/stretchr/testify/assert"
)

var sp1 = &cms_folder.StaticPage{
	Key:  "/static-page1/",
	Lang: []string{"en"},
}
var sp2 = &cms_folder.StaticPage{
	Key:  "/static-page2/",
	Lang: []string{"en", "vi"},
}
var sp3 = &cms_folder.StaticPage{
	Key:  "/static-page3/",
	Lang: []string{"en", "ms"},
}
var sp4 = &cms_folder.StaticPage{
	Key:  "/key1/key2/",
	Lang: []string{"en"},
}
var sp5 = &cms_folder.StaticPage{
	Key:  "/key3/key4/",
	Lang: []string{"en"},
}
var sp6 = &cms_folder.StaticPage{
	Key:  "/key3/",
	Lang: []string{"en"},
}

var spCache = &cms_folder.CmsFolderCache{
	StaticPage: map[string]*cms_folder.StaticPage{
		cms_folder.PrepareId(sp1.Key): sp1,
		cms_folder.PrepareId(sp2.Key): sp2,
		cms_folder.PrepareId(sp3.Key): sp3,
		cms_folder.PrepareId(sp4.Key): sp4,
		cms_folder.PrepareId(sp5.Key): sp5,
		cms_folder.PrepareId(sp6.Key): sp6,
	},
}

var pmCache = &pagelist.PageListCache{
	Data: pagemanager_adapter.PageListV1Res{
		pagemanager_adapter.PagePlatform{
			Platform: pagemanager_adapter.PlatformMobileApp,
			Langs: []pagemanager_adapter.PageLang{
				pagemanager_adapter.PageLang{
					Lang: "en",
					Models: []pagemanager_adapter.PageModel{
						pagemanager_adapter.PageModel{
							Model: pagemanager_adapter.ModelStaticPage,
							Keys: []string{
								tools.CleanURL(sp1.Key),
								tools.CleanURL(sp2.Key),
							},
						},
					},
				},
				pagemanager_adapter.PageLang{
					Lang: "vi",
					Models: []pagemanager_adapter.PageModel{
						pagemanager_adapter.PageModel{
							Model: pagemanager_adapter.ModelStaticPage,
							Keys: []string{
								tools.CleanURL(sp2.Key),
							},
						},
					},
				},
			},
		},
	},
}

func TestStaticPageRedirectByPath(t *testing.T) {
	keys := GetStaticPageResolver(spCache, nil, false, "en").RedirectByPath("static-page1")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/static-page1/", "")
		assert.Equal(t, keys[0].Type, TYPE_STATIC_PAGE, "")
	}
}

func TestStaticPageRedirectByPathForMobapiLangEN(t *testing.T) {
	assert.Equal(t, pmCache.IsMobileAppStaticPageExist("static-page2", "en"), true, "")

	keys := GetStaticPageResolver(spCache, pmCache, true, "en").RedirectByPath("static-page2")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/static-page2/", "")
		assert.Equal(t, keys[0].Type, TYPE_STATIC_PAGE, "")
	}
}

func TestStaticPageRedirectByPathForMobapiLangVI(t *testing.T) {
	assert.Equal(t, pmCache.IsMobileAppStaticPageExist("static-page2", "vi"), true, "")

	keys := GetStaticPageResolver(spCache, pmCache, true, "vi").RedirectByPath("static-page2")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/static-page2/", "")
		assert.Equal(t, keys[0].Type, TYPE_STATIC_PAGE, "")
	}
}

func TestStaticPageRedirectByPathForMobapiFailLang(t *testing.T) {
	assert.Equal(t, pmCache.IsMobileAppStaticPageExist("static-page1", "vi"), false, "")
	assert.Nil(t, GetStaticPageResolver(spCache, pmCache, true, "vi").RedirectByPath("static-page1"), "")
}

func TestStaticPageRedirectByPathForMobapiFailPathLangEn(t *testing.T) {
	assert.Equal(t, pmCache.IsMobileAppStaticPageExist("static-page3", "en"), false, "")
	assert.Nil(t, GetStaticPageResolver(spCache, pmCache, true, "en").RedirectByPath("static-page3"), "")
}

func TestStaticPageRedirectByPathForMobapiFailPathLangMS(t *testing.T) {
	assert.Equal(t, pmCache.IsMobileAppStaticPageExist("static-page3", "ms"), false, "")
	assert.Nil(t, GetStaticPageResolver(spCache, pmCache, true, "ms").RedirectByPath("static-page3"), "")
}

func TestStaticPageRedirectByPathLangMS(t *testing.T) {
	keys := GetStaticPageResolver(spCache, nil, false, "ms").RedirectByPath("static-page3")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/static-page3/", "")
		assert.Equal(t, keys[0].Type, TYPE_STATIC_PAGE, "")
	}
}

func TestStaticPageRedirectByPathNoLang(t *testing.T) {
	assert.Nil(t, GetStaticPageResolver(spCache, nil, false, "vi").RedirectByPath("static-page1"), "")
}

func TestStaticPageResolveKeys(t *testing.T) {
	assert.Nil(t, GetStaticPageResolver(spCache, nil, false, "en").ResolveKeys([]string{"url-key-brand1"}), "")
}

func TestStaticPageRedirectBySearch(t *testing.T) {
	assert.Nil(t, GetStaticPageResolver(spCache, nil, false, "en").RedirectBySearch("Brand Name2"), "")
}
