package urlv2

import (
	"net/url"
	"strings"

	"content_api/model/url_mapping"
	"content_api/services/search_helper/tools"
)

type URLMappingResolver struct {
	isMobapi bool
	lang     string
	cache    *url_mapping.UrlMappingCache
}

func GetURLMappingResolver(c *url_mapping.UrlMappingCache, lang string, isMobapi bool) SourceResolver {
	return &URLMappingResolver{
		cache:    c,
		lang:     lang,
		isMobapi: isMobapi,
	}
}

func (resolver *URLMappingResolver) RedirectByPath(path string) []Model {
	validPath := tools.CleanURL(path)
	if validPath == "" {
		return nil
	}

	row := resolver.cache.GetUrlByPath(validPath)
	if row != nil && (row.MobAPI || !resolver.isMobapi) {
		return convertResult(row.Target, path, int(row.HTTPCode))
	}

	if !strings.Contains(validPath, "/") {
		return nil
	}
	u1, err := url.Parse(validPath)
	if err != nil {
		return nil
	}
	keys := strings.Split(u1.Path, "/")

	row = resolver.cache.GetUrlByPath(keys[0])
	if row == nil || (resolver.isMobapi && !row.MobAPI) {
		return nil
	}

	firstPathRedirect := tools.CleanURL(row.Target)
	if firstPathRedirect == "" {
		return nil
	}
	u2, err := url.Parse(firstPathRedirect)
	if err != nil {
		return nil
	}
	u1.Path = "/" + strings.Replace(u1.Path, keys[0], strings.Trim(u2.Path, "/"), 1) + "/"
	query := make(url.Values, len(u1.Query())+len(u2.Query()))
	for k, v := range u2.Query() {
		query[k] = v
	}
	for k, v := range u1.Query() {
		query[k] = v
	}
	u1.RawQuery = query.Encode()
	return convertResult(u1.String(), path, int(row.HTTPCode))
}

func convertResult(target, path string, HTTPCode int) []Model {
	return []Model{
		Model{
			Type:   TYPE_URL_MAPPING,
			Name:   path,
			URLKey: target,
			Code:   HTTPCode,
		},
	}
}

func (resolver *URLMappingResolver) ResolveKeys(keys []string) []Model {
	return nil
}

func (resolver *URLMappingResolver) RedirectBySearch(search string) []Model {
	return nil
}
