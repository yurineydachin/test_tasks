package urlv2

import (
	"net/url"
	"sort"
	"strings"

	"content_api/model/url/exclusions"
	"content_api/services/search_helper/tools"
)

const (
	MAX_LOOPS          = 5
	URL_KEY_DELIMITER  = "/"
	URL_KEY_DELIMITER2 = "--"
)

var ignoredPath = map[string]bool{
	"catalog": true,
}

func (resolver *Resolver) Resolve(path, search string, keys []string) *Resolver {
	if resolver.source == nil || len(resolver.source) == 0 {
		return resolver
	}

	_ = resolver.resolveParams(path, search, "", keys)

	for i := 0; i < MAX_LOOPS; i++ {
		isNeedResolve, newPath, newSearch, newQuery := resolver.newResolveParams()
		if !isNeedResolve {
			return resolver
		}
		_ = resolver.resolveParams(newPath, newSearch, newQuery, nil)
	}
	return resolver
}

func (resolver *Resolver) addState(path, search, query string, keys []string, models []Model) {
	resolver.State = append(resolver.State, StateItem{
		Path:   path,
		Search: search,
		Query:  query,
		Keys:   keys,
		Models: models,
	})
	if search != "" {
		resolver.processedSearch[search] = true
	}
	if path != "" {
		resolver.processedPaths[path] = true
	}
	for _, key := range keys {
		resolver.processedKeys[key] = true
	}
}

func (resolver *Resolver) newResolveParams() (bool, string, string, string) { // isNeedResolve, path, search, rawQuery
	if len(resolver.State) == 0 {
		return false, "", "", ""
	}
	models := resolver.State[len(resolver.State)-1].Models
	for m := range models {
		if models[m].Type != TYPE_URL_MAPPING && models[m].Type != TYPE_KEYWORD {
			continue
		}
		validPath := tools.CleanURL(models[m].URLKey)
		if validPath == "" {
			continue
		}
		query := ""
		isNeedResolve := false
		newPath, newKeys, newQuery := parsePath(validPath)
		if newPath != "" && !resolver.isPathProcessed(newPath) {
			isNeedResolve = true
		}
		if notProcessedKeys := resolver.getKeysToProcess(newKeys); len(notProcessedKeys) > 0 {
			isNeedResolve = true
		}
		newSearch := newQuery.Get("q")
		if newSearch != "" && !resolver.isSearchProcessed(newSearch) {
			isNeedResolve = true
			newQuery.Del("q")
		}
		if isNeedResolve {
			if len(newQuery) > 0 {
				query = newQuery.Encode()
			}
			return isNeedResolve, newPath, newSearch, query
		}
	}
	return false, "", "", ""
}

func (resolver *Resolver) isPathProcessed(path string) bool {
	_, find := resolver.processedPaths[path]
	return find
}

func (resolver *Resolver) getKeysToProcess(keys []string) []string {
	result := make([]string, 0, len(keys))
	for _, key := range keys {
		if _, find := resolver.processedKeys[key]; !find && key != "" {
			result = append(result, key)
		}
	}
	return result
}

func (resolver *Resolver) isSearchProcessed(search string) bool {
	_, find := resolver.processedSearch[search]
	return find
}

func (resolver *Resolver) resolveParams(path, search, query string, keys []string) []Model {
	var models []Model
	if validPath := tools.CleanURL(path); validPath != "" {
		cleanPath, newKeys, pathQuery := parsePath(validPath)
		path = cleanPath
		if len(query) == 0 {
			query = pathQuery.Encode()
		}
		keys = append(keys, newKeys...)
		models = resolver.checkRedirectsByPath(path)
		resolver.processedKeys[path] = true
	}

	keys = getUniqKeys(keys)
	models = append(models, resolver.checkKeys(keys)...)

	resultModels := make([]Model, 0, len(models))
	isRedirectByURLMapping := false
	isCategoryURLKey := false
	for i := range models {
		if models[i].Type == TYPE_URL_MAPPING {
			isRedirectByURLMapping = true
		} else if models[i].Type == TYPE_CATEGORY {
			isCategoryURLKey = true
		}
	}

	if !isRedirectByURLMapping {
		modelsSearch := resolver.checkRedirectsBySearch(search)
		if resolver.redirectEnabled {
			models = append(models, modelsSearch...)
		} else {
			for i := range modelsSearch {
				if exclusions.IsRedirectExclusion(modelsSearch[i].URLKey) {
					models = append(models, modelsSearch[i])
				}
			}
		}
	}

	for i := range models {
		// We should not redirect with search keywords if category was resolved (search suggestions case)
		if models[i].Type != TYPE_KEYWORD || !isCategoryURLKey {
			resultModels = append(resultModels, models[i])
		}
	}

	for i := range keys {
		keys[i] = strings.ToLower(keys[i])
	}
	resolver.addState(strings.ToLower(path), strings.ToLower(search), query, keys, resultModels)
	return resultModels
}

func (resolver *Resolver) checkRedirectsByPath(path string) []Model {
	if path == "" {
		return nil
	}
	var models []Model
	if modelURLs := resolver.source[TYPE_STATIC_PAGE].RedirectByPath(path); len(modelURLs) > 0 {
		models = modelURLs
	}
	if modelURLs := resolver.source[TYPE_URL_MAPPING].RedirectByPath(path); len(modelURLs) > 0 {
		if resolver.redirectEnabled {
			models = append(models, modelURLs...)
		} else {
			for i := range modelURLs {
				if exclusions.IsRedirectExclusion(modelURLs[i].URLKey) {
					models = append(models, modelURLs[i])
				}
			}
		}
	}
	return models
}

func (resolver *Resolver) checkRedirectsBySearch(search string) []Model {
	if search == "" {
		return nil
	}
	if modelPhrase := resolver.source[TYPE_CATEGORY].RedirectBySearch(search); len(modelPhrase) > 0 {
		return modelPhrase
	}
	if modelPhrase := resolver.source[TYPE_BRAND].RedirectBySearch(search); len(modelPhrase) > 0 {
		return modelPhrase
	}
	if modelPhrase := resolver.source[TYPE_SUPPLIER].RedirectBySearch(search); len(modelPhrase) > 0 {
		return modelPhrase
	}
	if modelPhrase := resolver.source[TYPE_HIGHLIGHT].RedirectBySearch(search); len(modelPhrase) > 0 {
		return modelPhrase
	}
	if modelPhrase := resolver.source[TYPE_KEYWORD].RedirectBySearch(search); len(modelPhrase) > 0 {
		return modelPhrase
	}
	return nil
}

func (resolver *Resolver) checkKeys(keys []string) []Model {
	if len(keys) == 0 {
		return nil
	}
	models := make([]Model, 0, len(keys))
	if modelKeys := resolver.source[TYPE_CATEGORY].ResolveKeys(keys); len(modelKeys) > 0 {
		models = append(models, modelKeys...)
	}
	if modelKeys := resolver.source[TYPE_BRAND].ResolveKeys(keys); len(modelKeys) > 0 {
		models = append(models, modelKeys...)
	}
	if modelKeys := resolver.source[TYPE_SUPPLIER].ResolveKeys(keys); len(modelKeys) > 0 {
		models = append(models, modelKeys...)
	}
	if modelKeys := resolver.source[TYPE_HIGHLIGHT].ResolveKeys(keys); len(modelKeys) > 0 {
		models = append(models, modelKeys...)
	}
	return models
}

func parsePath(path string) (string, []string, url.Values) { // path, keys, query
	u, err := url.Parse(path)
	if err != nil {
		return path, nil, nil
	}
	keys := []string{}
	clearPath := strings.ToLower(strings.Trim(strings.Replace(u.Path, URL_KEY_DELIMITER2, URL_KEY_DELIMITER, -1), URL_KEY_DELIMITER))
	if clearPath != "" {
		valueParts := strings.Split(clearPath, URL_KEY_DELIMITER)
		for _, part := range valueParts {
			if _, ignore := ignoredPath[part]; part != "" && !ignore {
				keys = append(keys, part)
			}
		}
	}
	return clearPath, keys, u.Query()
}

func getUniqKeys(keys []string) []string {
	if len(keys) == 0 {
		return nil
	}
	uniq := make(map[string]bool, len(keys))
	for _, key := range keys {
		uniq[key] = true
	}
	result := make([]string, 0, len(uniq))
	for key := range uniq {
		result = append(result, key)
	}
	sort.Strings(result)
	return result
}
