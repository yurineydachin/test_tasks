package urlv2

import (
	"testing"

	keywords "content_api/model/search_redirect_keywords"
	"github.com/stretchr/testify/assert"
)

var kCache = &keywords.KeywordsCache{
	keywords.PrepareId("some other name of category1"):    "/shop-category1/",
	keywords.PrepareId("brand name2"):                     "/url-key-brand2/",
	keywords.PrepareId("supplier name3"):                  "/url-key-supplier3/",
	keywords.PrepareId("highlight nameen4"):               "/url-key-highlight4/",
	keywords.PrepareId("category3 brand3"):                "/shop-category3/url-key-brand3/",
	keywords.PrepareId("some phrase"):                     "/some-url/",
	keywords.PrepareId("some phrase with filters"):        "/some-url/?price=300-500&sort=deliverytime",
	keywords.PrepareId("some phrase to catalog"):          "/catalog",
	keywords.PrepareId("main page"):                       "/",
	keywords.PrepareId("keyword to keyword"):              "?q=new keyword",
	keywords.PrepareId("keyword to category and keyword"): "/shop-category1?q=new keyword",
	keywords.PrepareId("keyword to category and filters"): "/shop-category1?price=100-200&color_family=Black",
	keywords.PrepareId("loop redirect"):                   "/loop-redirect/",
	keywords.PrepareId("top up"):                          "/mobilerecharge/",
	keywords.PrepareId("digital account"):                 "/mobilerecharge/",
	keywords.PrepareId("s8"):                              "/samsung/?q=s8",
}

func TestKeywordRedirectByPath(t *testing.T) {
	assert.Nil(t, GetKeywordResolver(kCache, "en").RedirectByPath("url-key-brand1"), "")
}

func TestKeywordResolveKeys(t *testing.T) {
	assert.Nil(t, GetKeywordResolver(kCache, "en").ResolveKeys([]string{"url-key-brand1"}), "")
}

func TestKeywordRedirectBySearch(t *testing.T) {
	keys := GetKeywordResolver(kCache, "en").RedirectBySearch("Brand Name2")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/url-key-brand2/", "")
		assert.Equal(t, keys[0].Type, TYPE_KEYWORD, "")
	}
}

func TestKeywordRedirectBySearch2(t *testing.T) {
	keys := GetKeywordResolver(kCache, "en").RedirectBySearch("s8")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, keys[0].URLKey, "/samsung/?q=s8", "")
		assert.Equal(t, keys[0].Type, TYPE_KEYWORD, "")
	}
}
