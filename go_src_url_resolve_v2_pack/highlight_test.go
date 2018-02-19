package urlv2

import (
	"testing"

	"content_api/model/lazada_highlights"
	"content_api/services/search_helper/tools"
	"github.com/stretchr/testify/assert"
)

var h1 = &lazada_highlights.LazadaHighlightsRow{
	IDCatalogHighlight: 1,
	Name:               "Highlight Name1",
	NameEn:             "Highlight NameEn1",
	NameDisplay:        "Highlight NameDisplay1",
	UrlKey:             "url-key-highlight1",
}
var h2 = &lazada_highlights.LazadaHighlightsRow{
	IDCatalogHighlight: 2,
	Name:               "Highlight Name2",
	NameEn:             "Highlight NameEn2",
	NameDisplay:        "Highlight NameDisplay2",
	UrlKey:             "url-key-highlight2",
}
var h3 = &lazada_highlights.LazadaHighlightsRow{
	IDCatalogHighlight: 3,
	Name:               "Highlight Name3",
	NameEn:             "Highlight NameEn3",
	NameDisplay:        "Highlight NameDisplay3",
	UrlKey:             "url-key-highlight3",
}
var h4 = &lazada_highlights.LazadaHighlightsRow{
	IDCatalogHighlight: 4,
	Name:               "Highlight Name4",
	NameEn:             "Highlight NameEn4",
	NameDisplay:        "Highlight NameDisplay4",
	UrlKey:             "key2",
}

var hCache = &lazada_highlights.LazadaHighlightsCache{
	Highlights: []*lazada_highlights.LazadaHighlightsRow{h1, h2, h3, h4},
	HighlightsByID: map[uint64]*lazada_highlights.LazadaHighlightsRow{
		h1.IDCatalogHighlight: h1,
		h2.IDCatalogHighlight: h2,
		h3.IDCatalogHighlight: h3,
		h4.IDCatalogHighlight: h4,
	},
	HighlightsByName: map[string]*lazada_highlights.LazadaHighlightsRow{
		tools.PrepareCleanId(h1.Name): h1,
		tools.PrepareCleanId(h2.Name): h2,
		tools.PrepareCleanId(h3.Name): h3,
		tools.PrepareCleanId(h4.Name): h4,
	},
	HighlightsByNameEn: map[string]*lazada_highlights.LazadaHighlightsRow{
		tools.PrepareCleanId(h1.NameEn): h1,
		tools.PrepareCleanId(h2.NameEn): h2,
		tools.PrepareCleanId(h3.NameEn): h3,
		tools.PrepareCleanId(h4.NameEn): h4,
	},
	HighlightsByURL: map[string][]*lazada_highlights.LazadaHighlightsRow{
		tools.PrepareCleanId(h1.UrlKey): []*lazada_highlights.LazadaHighlightsRow{h1},
		tools.PrepareCleanId(h2.UrlKey): []*lazada_highlights.LazadaHighlightsRow{h2},
		tools.PrepareCleanId(h3.UrlKey): []*lazada_highlights.LazadaHighlightsRow{h3},
		tools.PrepareCleanId(h4.UrlKey): []*lazada_highlights.LazadaHighlightsRow{h4},
	},
}

func TestHighlightRedirectByPath(t *testing.T) {
	assert.Nil(t, GetHighlightResolver(hCache, "en").RedirectByPath("url-key-highlight3"), "")
}

func TestHighlightResolveKeys(t *testing.T) {
	keys := GetHighlightResolver(hCache, "en").ResolveKeys([]string{"url-key-highlight1"})
	if assert.NotNil(t, keys, "") {
		assert.Equal(t, len(keys), 1, "")
		assert.Equal(t, int(keys[0].ID), 1, "")
	}
}

func TestHighlightResolveKeysUnknown(t *testing.T) {
	keys := GetHighlightResolver(hCache, "en").ResolveKeys([]string{"shop-category100", "url-key-brand1111"})
	assert.Nil(t, keys, "")
}

func TestHighlightRedirectBySearch(t *testing.T) {
	keys := GetHighlightResolver(hCache, "en").RedirectBySearch("Highlight Name2")
	if assert.NotNil(t, keys, "") && assert.Equal(t, len(keys), 1, "") {
		assert.Equal(t, int(keys[0].ID), 2, "")
		assert.Equal(t, keys[0].Type, TYPE_HIGHLIGHT, "")
	}
}
