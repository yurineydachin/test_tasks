package urlv2

import (
	"content_api/model/lazada_highlights"
)

type HighlightResolver struct {
	lang  string
	cache *lazada_highlights.LazadaHighlightsCache
}

func GetHighlightResolver(c *lazada_highlights.LazadaHighlightsCache, lang string) SourceResolver {
	return &HighlightResolver{
		cache: c,
		lang:  lang,
	}
}

func (resolver *HighlightResolver) RedirectByPath(path string) []Model {
	return nil
}

func (resolver *HighlightResolver) ResolveKeys(keys []string) []Model {
	return convertHighlights(resolver.cache.GetByURLs(keys))
}

func (resolver *HighlightResolver) RedirectBySearch(search string) []Model {
	return convertHighlights(resolver.cache.GetByNames([]string{search}, true))
}

func convertHighlights(rows []*lazada_highlights.LazadaHighlightsRow) []Model {
	if rows == nil || len(rows) == 0 {
		return nil
	}
	result := make([]Model, len(rows))
	for i := range rows {
		result[i] = Model{
			Type:        TYPE_HIGHLIGHT,
			ID:          rows[i].IDCatalogHighlight,
			Name:        rows[i].NameDisplay,
			URLKey:      rows[i].UrlKey,
			FilterValue: rows[i].Name,
		}
		if result[i].FilterValue == "" {
			result[i].FilterValue = result[i].Name
		}
	}
	return result
}
