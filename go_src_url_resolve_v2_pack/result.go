package urlv2

import (
	"net/url"
	"strings"

	"content_api/services/search_helper/tools"
)

const (
	DEFAULT_REDIRECT_HTTP_CODE = 302
)

var typePriority = []string{
	TYPE_CATEGORY,
	TYPE_BRAND,
	TYPE_SUPPLIER,
	TYPE_HIGHLIGHT,
	TYPE_STATIC_PAGE,
}

//easyjson:json
type UrlResolve struct {
	Redirect   *Target     `json:"redirect"`
	UrlKey     []Model     `json:"keys"`
	StaticPage *StaticPage `json:"static"`
	State      []StateItem `json:"state"`
}

type Target struct {
	Target   string `json:"target,omitempty"`
	Type     string `json:"type,omitempty"`
	HTTPCode int    `json:"code,omitempty"`
	MobAPI   bool   `json:"mobapi,omitempty"`
}

type StaticPage struct {
	Key  string   `json:"url,omitempty"`
	Lang []string `json:"lang,omitempty"`
}

func (resolver *Resolver) Result() UrlResolve {
	state := resolver.getValidState()
	if state == nil {
		return UrlResolve{}
	}

	models := state.getResolvedModels()
	result := UrlResolve{
		State:  resolver.State,
		UrlKey: make([]Model, 0, len(models)),
	}
	redirectTypes := make(map[string]bool, len(models))
	pathParts := make([]string, 0, len(models))
	for _, model := range models {
		redirectTypes[model.Type] = true
		if model.URLKey != "" {
			pathParts = append(pathParts, tools.CleanURL(model.URLKey))
		}
		if model.Type == TYPE_STATIC_PAGE {
			result.StaticPage = &StaticPage{
				Key:  model.URLKey,
				Lang: []string{resolver.lang},
			}
		} else {
			result.UrlKey = append(result.UrlKey, model)
		}
	}

	target := resolver.getTarter(pathParts, state)
	if resolver.isRedirectDetected(target) {
		result.Redirect = &Target{
			Target:   target,
			Type:     calcRedirectType(redirectTypes),
			HTTPCode: resolver.getRedirectCode(),
			MobAPI:   true,
		}
	}

	return result
}

func (resolver *Resolver) getTarter(pathParts []string, state *StateItem) string {
	target := ""
	if len(pathParts) > 0 {
		target = "/" + strings.Join(pathParts, "/")
	}
	query, err := url.ParseQuery(state.Query)
	if err != nil {
		query = url.Values{}
	}
	if resolver.getFirstSearch() != "" {
		query.Set("searchredirect", resolver.getFirstSearch())
	}
	if target == "" && state.Search != "" {
		query.Set("q", state.Search)
	}
	target += "/"
	if len(query) > 0 {
		target += "?" + query.Encode()
	}
	return target
}

func (resolver *Resolver) getValidState() *StateItem {
	if len(resolver.State) == 0 {
		return nil
	}
	for i := len(resolver.State) - 1; i >= 0; i-- {
		if resolver.State[i].isValid() {
			return &resolver.State[i]
		}
	}
	return nil
}

func (resolver *Resolver) isRedirectDetected(target string) bool {
	if len(resolver.State) == 0 {
		return false
	}

	if target != "" && resolver.State[0].Path != "" {

		u, err := url.Parse(strings.Trim(resolver.State[0].Path, "/"))
		if err == nil {
			u.Path = "/" + u.Path + "/"
			u.RawQuery = resolver.State[0].Query

			if ut, err := url.Parse(target); err == nil {
				q := ut.Query()
				q.Del("searchredirect")
				ut.RawQuery = q.Encode()
				return u.String() != ut.String()
			}
		}
	}
	countOfValidStates := 0
	for i := range resolver.State {
		if i > 0 && resolver.State[i].isValid() {
			return true
		} else if resolver.State[i].Search != "" && len(resolver.State[i].getResolvedModels()) > 0 {
			return true
		} else if resolver.State[i].isValid() {
			countOfValidStates++
			if countOfValidStates > 1 {
				return true
			}
		}
	}
	return false
}

func (resolver *Resolver) getRedirectCode() int {
	for i := range resolver.State {
		for _, model := range resolver.State[i].Models {
			if model.Type == TYPE_URL_MAPPING && model.Code != 0 {
				return model.Code
			}
		}
	}
	return DEFAULT_REDIRECT_HTTP_CODE
}

func (resolver *Resolver) getFirstSearch() string {
	if len(resolver.State) > 0 && resolver.State[0].Search != "" {
		return resolver.State[0].Search
	}
	return ""
}

func calcRedirectType(types map[string]bool) string {
	if len(types) == 0 {
		return TYPE_KEYWORD
	}
	categoryExists := false
	for _, t := range typePriority {
		if _, find := types[t]; !find {
			continue
		}
		if t == TYPE_CATEGORY {
			categoryExists = true
		} else if categoryExists {
			if t == TYPE_BRAND {
				return TYPE_BRAND_CATEGORY
			}
			if t == TYPE_SUPPLIER {
				return TYPE_SUPPLIER_CATEGORY
			}
			return TYPE_CATEGORY
		} else {
			return t
		}
	}
	return TYPE_CATEGORY
}
