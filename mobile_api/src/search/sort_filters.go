package search_service

import (
	"sort"
	"strings"

	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/search_opts"
)

var deboostedBrands map[string]bool = map[string]bool{
	"other brands":      true,
	"unbranded/generic": true,
	"unbrand":           true,
	"branded":           true,
	"new brand":         true,
	"oem":               true,
	"others":            true,
	"unbranded":         true,
	"generic":           true,
	"no brand":          true,
	"none":              true,
	"not specified":     true,
	"other":             true,
	"no":                true,
}

type SortEasyFilterOptionByLabel struct {
	BoostedValues   map[string]bool // may be nil. This values will be moved to the begin of list during sorting
	DeboostedValues map[string]bool // may be nil. This values will be moved to the end of list during sorting
	Values          []*search.FilterOption
}

func (a SortEasyFilterOptionByLabel) Len() int { return len(a.Values) }
func (a SortEasyFilterOptionByLabel) Swap(i, j int) {
	a.Values[i], a.Values[j] = a.Values[j], a.Values[i]
}
func (a SortEasyFilterOptionByLabel) Less(i, j int) bool {
	var (
		i_boosted   bool
		j_boosted   bool
		i_deboosted bool
		j_deboosted bool
	)
	iLabel := strings.ToLower(a.Values[i].Label)
	jLabel := strings.ToLower(a.Values[j].Label)

	if a.BoostedValues != nil {
		_, i_boosted = a.BoostedValues[iLabel]
		_, j_boosted = a.BoostedValues[jLabel]
	}
	if a.DeboostedValues != nil {
		_, i_deboosted = a.DeboostedValues[iLabel]
		_, j_deboosted = a.DeboostedValues[jLabel]
	}

	switch {
	case i_boosted && j_boosted:
		return iLabel < jLabel
	case i_boosted:
		return true
	case j_boosted:
		return false
	case i_deboosted && j_deboosted:
		return iLabel < jLabel
	case i_deboosted:
		return false
	case j_deboosted:
		return true
	default:
		return iLabel < jLabel
	}
}

func (result *SearchResult) AlphabetizeBrandFilters() {
	var brandFilter *search.OneFilter
	for i := range result.Filters {
		if result.Filters[i].ID == search_opts.FilterIDBrand {
			brandFilter = &result.Filters[i]
			break
		}
	}
	if brandFilter == nil {
		return
	}

	options := SortEasyFilterOptionByLabel{
		DeboostedValues: deboostedBrands,
		Values:          make([]*search.FilterOption, 0, len(brandFilter.Options)),
	}

	for _, opt := range brandFilter.Options {
		options.Values = append(options.Values, opt)
	}

	if len(brandFilter.SelectedOptions) > 0 {
		options.BoostedValues = make(map[string]bool, len(brandFilter.SelectedOptions))
		for _, opt := range brandFilter.SelectedOptions {
			options.BoostedValues[strings.ToLower(opt.Label)] = true
		}
	}
	sort.Sort(options)
	brandFilter.Options = options.Values
}

func (result *SearchResult) SortFiltersBySelectedOptions() {
	filters := make([]search.OneFilter, 0, len(result.Filters))
	for _, filter := range result.Filters {
		if len(filter.SelectedOptions) > 0 {
			filters = append(filters, filter)
		}
	}
	for _, filter := range result.Filters {
		if len(filter.SelectedOptions) == 0 {
			filters = append(filters, filter)
		}
	}
	result.Filters = filters
}
