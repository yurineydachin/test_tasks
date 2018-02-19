package urlv2

import (
	keywords "content_api/model/search_redirect_keywords"
)

type KeywordResolver struct {
	lang  string
	cache *keywords.KeywordsCache
}

func GetKeywordResolver(c *keywords.KeywordsCache, lang string) SourceResolver {
	return &KeywordResolver{
		cache: c,
		lang:  lang,
	}
}

func (resolver *KeywordResolver) RedirectByPath(path string) []Model {
	return nil
}

func (resolver *KeywordResolver) ResolveKeys(keys []string) []Model {
	return nil
}

func (resolver *KeywordResolver) RedirectBySearch(search string) []Model {
	rows := resolver.cache.GetURLByKey([]string{search})
	if rows == nil || len(rows) == 0 {
		return nil
	}
	result := make([]Model, 0, len(rows))
	for word, url := range rows {
		result = append(result, Model{
			Type:   TYPE_KEYWORD,
			Name:   word,
			URLKey: url,
		})
	}
	return result
}
