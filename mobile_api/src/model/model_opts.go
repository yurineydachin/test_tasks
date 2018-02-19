package model_service

import (
	"regexp"
	"strconv"
	"strings"

	"mobile_search_api/srv/search_opts"
)

const (
	FilterValuePattern = "[0-9A-Za-z\\s]+"

	FieldID     = "id"
	FieldURLKey = "urlkey"
	FieldName   = "name"
)

var allowedModels = map[string]bool{
	search_opts.ModelCategory:  true,
	search_opts.ModelHighlight: true,
	search_opts.FilterIDTaobao: true,
	search_opts.ModelSeller:    true,
	search_opts.ModelBrand:     true,
}

type ModelOpts struct {
	Lang    string
	Model   string
	Key     string
	Filters string
	Field   string
}

func GetModelOpts(opts *search_opts.SearchOpts) *ModelOpts {
	result := &ModelOpts{
		Lang:    opts.Lang,
		Model:   opts.Model,
		Key:     strings.ToLower(opts.Path),
		Filters: opts.Filters,
		Field:   FieldURLKey,
	}
	if _, err := strconv.ParseUint(opts.Key, 10, 64); err == nil {
		result.Field = FieldID
	}

	if result.Model == search_opts.ModelCategory {
		if categoryID := result.findFilterValueString(search_opts.FilterIDCategory); categoryID != "" {
			result.Key = categoryID
			result.Field = FieldID
		}
		return result
	}
	if result.Model != search_opts.ModelSearchQ {
		return result
	}

	if strings.Index(result.Filters, search_opts.FilterIDTaobao+"~"+search_opts.FilterValueTaobao) > -1 {
		result.Model = search_opts.FilterIDTaobao
		result.Key = search_opts.TaobaoHighlightLabel
		result.Field = FieldName
	} else if categoryID := result.findFilterValueString(search_opts.FilterIDCategory); categoryID != "" {
		result.Model = search_opts.ModelCategory
		result.Key = categoryID
		result.Field = FieldID
	} else if highlightName := result.findFilterValueString(search_opts.FilterIDHighlight); highlightName != "" {
		result.Model = search_opts.ModelHighlight
		result.Key = highlightName
		result.Field = FieldName
	} else if brandID := result.findFilterValueString(search_opts.FilterIDBrand); brandID != "" {
		result.Model = search_opts.ModelBrand
		result.Key = brandID
		result.Field = FieldID
	} else if suplierID := result.findFilterValueString(search_opts.FilterIDSeller); suplierID != "" {
		result.Model = search_opts.ModelSeller
		result.Key = suplierID
		result.Field = FieldID
	}
	return result
}

func (opts *ModelOpts) IsModelAllowed() bool {
	_, find := allowedModels[opts.Model]
	return find
}

var rf = map[string]*regexp.Regexp{
	search_opts.FilterIDCategory:  regexp.MustCompile(search_opts.FilterIDCategory + "~(" + FilterValuePattern + ")"),
	search_opts.FilterIDHighlight: regexp.MustCompile(search_opts.FilterIDHighlight + "~(" + FilterValuePattern + ")"),
	search_opts.FilterIDBrand:     regexp.MustCompile(search_opts.FilterIDBrand + "~(" + FilterValuePattern + ")"),
	search_opts.FilterIDSeller:    regexp.MustCompile(search_opts.FilterIDSeller + "~(" + FilterValuePattern + ")"),
}

func (opts *ModelOpts) findFilterValueString(filterID string) string {
	if opts.Filters != "" {
		if _, find := rf[filterID]; !find {
			rf[filterID] = regexp.MustCompile(filterID + "~(" + FilterValuePattern + ")")
		}
		found := rf[filterID].FindStringSubmatch(opts.Filters)
		if len(found) >= 2 {
			return found[1]
		}
	}
	return ""
}
