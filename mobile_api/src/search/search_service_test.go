package search_service

import (
	"testing"

	"github.com/blang/semver"
	"github.com/stretchr/testify/assert"
	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/search_opts"
)

func TestGetTaobaoFilter(t *testing.T) {
	result := &SearchResult{}
	taobaoFilter := result.getTaobaoFilter()

	assert.Equal(t, taobaoFilter.ID, search_opts.FilterIDTaobao, "")
	assert.Equal(t, taobaoFilter.Label, search_opts.TaobaoHighlightLabel, "")
	assert.Equal(t, len(taobaoFilter.Options), 1, "")
	assert.Equal(t, taobaoFilter.Options[0].Label, search_opts.TaobaoHighlightLabel, "")
	assert.Equal(t, taobaoFilter.Options[0].Val, search_opts.FilterValueTaobao, "")
}

func TestIsTaobaoHighlightNotExist(t *testing.T) {
	result := &SearchResult{
		Filters: []search.OneFilter{
			{
				ID:    search_opts.FilterIDCategory,
				Label: "Category",
				Options: []*search.FilterOption{
					{Label: "Mobiles"},
				},
			},
			{
				ID:    search_opts.FilterIDHighlight,
				Label: "Highlights",
				Options: []*search.FilterOption{
					{Label: "Special promotions"},
					{Label: "New arrivals"},
				},
			},
		},
	}
	assert.Equal(t, result.isTaobaoHighlightExist(), false, "")
}

func TestIsTaobaoHighlightExist(t *testing.T) {
	result := &SearchResult{
		Filters: []search.OneFilter{
			{
				ID:    search_opts.FilterIDCategory,
				Label: "Category",
				Options: []*search.FilterOption{
					{Label: "Mobiles"},
				},
			},
			{
				ID:    search_opts.FilterIDHighlight,
				Label: "Highlights",
				Options: []*search.FilterOption{
					{Label: "Special promotions"},
					{Label: search_opts.TaobaoHighlightLabel},
					{Label: "New arrivals"},
				},
			},
		},
	}
	assert.Equal(t, result.isTaobaoHighlightExist(), true, "")
}

func TestProcessFiltersAddTaobaoFilter(t *testing.T) {
	result := &SearchResult{
		platform:   search_opts.PlatformAndroid,
		appVersion: semver.Version{Major: 5, Minor: 15},
		opts: &search_opts.SearchOpts{
			Model: search_opts.ModelCategory,
			Lang:  "en",
		},
		Filters: []search.OneFilter{
			{
				ID:    search_opts.FilterIDCategory,
				Label: "Category",
				Options: []*search.FilterOption{
					{Label: "Mobiles"},
				},
			},
			{
				ID:    search_opts.FilterIDBrand,
				Label: "Brands",
				Options: []*search.FilterOption{
					{Label: "Apple"},
					{Label: "Samsung"},
				},
			},
			{
				ID:    search_opts.FilterIDHighlight,
				Label: "Highlights",
				Options: []*search.FilterOption{
					{Label: "Special promotions"},
					{Label: search_opts.TaobaoHighlightLabel},
					{Label: "New arrivals"},
				},
			},
		},
	}
	assert.Equal(t, len(result.Filters), 3, "")
	result.ProcessFilters()
	if assert.Equal(t, len(result.Filters), 4, "") {
		assert.Equal(t, result.Filters[0].ID, search_opts.FilterIDTaobao, "")
		assert.Equal(t, len(result.Filters[0].Options), 1, "")
		assert.Equal(t, result.Filters[1].ID, search_opts.FilterIDCategory, "")
		assert.Equal(t, len(result.Filters[1].Options), 1, "")
		assert.Equal(t, result.Filters[2].ID, search_opts.FilterIDBrand, "")
		assert.Equal(t, len(result.Filters[2].Options), 2, "")
		assert.Equal(t, result.Filters[3].ID, search_opts.FilterIDHighlight, "")
		assert.Equal(t, len(result.Filters[3].Options), 3, "")
	}
}

func TestProcessFiltersNotSkipHighlightFilter(t *testing.T) {
	result := &SearchResult{
		platform:   search_opts.PlatformAndroid,
		appVersion: semver.Version{Major: 5, Minor: 15},
		opts: &search_opts.SearchOpts{
			Model: search_opts.ModelHighlight,
			Lang:  "en",
		},
		Filters: []search.OneFilter{
			{
				ID:    search_opts.FilterIDCategory,
				Label: "Category",
				Options: []*search.FilterOption{
					{Label: "Mobiles"},
				},
			},
			{
				ID:    search_opts.FilterIDBrand,
				Label: "Brands",
				Options: []*search.FilterOption{
					{Label: "Apple"},
					{Label: "Samsung"},
				},
			},
			{
				ID:    search_opts.FilterIDHighlight,
				Label: "Highlights",
				Options: []*search.FilterOption{
					{Label: "Special promotions"},
					{Label: search_opts.TaobaoHighlightLabel},
					{Label: "New arrivals"},
				},
			},
		},
	}
	assert.Equal(t, len(result.Filters), 3, "")
	result.ProcessFilters()
	if assert.Equal(t, len(result.Filters), 3, "") {
		assert.Equal(t, result.Filters[0].ID, search_opts.FilterIDCategory, "")
		assert.Equal(t, len(result.Filters[0].Options), 1, "")
		assert.Equal(t, result.Filters[1].ID, search_opts.FilterIDBrand, "")
		assert.Equal(t, len(result.Filters[1].Options), 2, "")
		assert.Equal(t, result.Filters[2].ID, search_opts.FilterIDHighlight, "")
		assert.Equal(t, len(result.Filters[2].Options), 3, "")
	}
}

func TestFillSelectedFilters(t *testing.T) {
	min := uint64(1)
	max := uint64(1)
	searchResult := &SearchResult{
		Filters: []search.OneFilter{
			{
				ID:    search_opts.FilterIDCategory,
				Label: "Category",
				Options: []*search.FilterOption{
					{
						Val:   "99",
						Label: "Mobiles",
						Children: []*search.FilterOption{
							{Val: "199", Label: "Mobiles Child"},
						},
					},
				},
			},
			{
				ID:    search_opts.FilterIDBrand,
				Label: "Brands",
				Options: []*search.FilterOption{
					{Val: "5", Label: "Apple"},
					{Val: "6", Label: "Samsung"},
				},
			},
			{
				ID:    search_opts.FilterIDHighlight,
				Label: "Highlights",
				Options: []*search.FilterOption{
					{Val: "55", Label: "Special promotions"},
					{Val: "56", Label: search_opts.TaobaoHighlightLabel},
					{Val: "57", Label: "New arrivals"},
				},
			},
			{
				ID:              search_opts.FilterIDPrice,
				SelectedOptions: []*search.FilterOption{},
				Options: []*search.FilterOption{
					{
						Min: &min,
						Max: &max,
					},
				},
			},
		},
	}
	var selectedFilters map[string][]string

	selectedFilters = map[string][]string{
		search_opts.FilterIDCategory: []string{"99"},
		search_opts.FilterIDPrice:    []string{"1-50"},
	}
	searchResult.FillSelectedFilters(selectedFilters)
	if assert.NotNil(t, searchResult, "") && assert.Equal(t, len(searchResult.Filters), 4) {
		if assert.Equal(t, len(searchResult.Filters[0].SelectedOptions), 1) {
			assert.Equal(t, searchResult.Filters[0].SelectedOptions[0].Label, "Mobiles")
		}
		if assert.Equal(t, len(searchResult.Filters[3].SelectedOptions), 1) {
			assert.Equal(t, *searchResult.Filters[3].SelectedOptions[0].Min, uint64(1))
			assert.Equal(t, *searchResult.Filters[3].SelectedOptions[0].Max, uint64(50))
		}
	}

	selectedFilters = map[string][]string{
		search_opts.FilterIDBrand:    []string{"6"},
		search_opts.FilterIDCategory: []string{"199"},
	}
	searchResult.FillSelectedFilters(selectedFilters)
	if assert.NotNil(t, searchResult, "") && assert.Equal(t, len(searchResult.Filters), 4) {
		if assert.Equal(t, len(searchResult.Filters[0].SelectedOptions), 1) {
			assert.Equal(t, searchResult.Filters[0].SelectedOptions[0].Label, "Mobiles Child")
		}
		if assert.Equal(t, len(searchResult.Filters[1].SelectedOptions), 1) {
			assert.Equal(t, searchResult.Filters[1].SelectedOptions[0].Label, "Samsung")
		}
	}

	selectedFilters = map[string][]string{
		search_opts.FilterIDHighlight: []string{"57"},
		search_opts.FilterIDBrand:     []string{"5", "6"},
	}
	searchResult.FillSelectedFilters(selectedFilters)
	if assert.NotNil(t, searchResult, "") && assert.Equal(t, len(searchResult.Filters), 4) {
		if assert.Equal(t, len(searchResult.Filters[1].SelectedOptions), 2) {
			assert.Equal(t, searchResult.Filters[1].SelectedOptions[0].Label, "Apple")
			assert.Equal(t, searchResult.Filters[1].SelectedOptions[1].Label, "Samsung")
		}
		if assert.Equal(t, len(searchResult.Filters[2].SelectedOptions), 1) {
			assert.Equal(t, searchResult.Filters[2].SelectedOptions[0].Label, "New arrivals")
		}
	}
}

func TestIsHasTopSticker(t *testing.T) {
	var searchResult *SearchResult
	var result bool
	var prodCount int

	// Success
	prodCount = 5
	searchResult = &SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDTaobao},
			{ID: search_opts.FilterIDBrand},
			{
				ID: search_opts.FilterIDProductSticker,
				Options: []*search.FilterOption{
					{
						Val:       "Option",
						ProdCount: &prodCount,
					},
				},
			},
		},
		TotalProducts: 5,
	}
	result = isHasTopSticker(searchResult)
	assert.Equal(t, result, true)
	assert.Equal(t, len(searchResult.Filters), 2)

	// Wrong prodCount
	prodCount = 54
	searchResult = &SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDTaobao},
			{ID: search_opts.FilterIDBrand},
			{
				ID: search_opts.FilterIDProductSticker,
				Options: []*search.FilterOption{
					{
						Val:       "Option",
						ProdCount: &prodCount,
					},
				},
			},
		},
		TotalProducts: 5,
	}
	result = isHasTopSticker(searchResult)
	assert.Equal(t, result, false)
	assert.Equal(t, len(searchResult.Filters), 3)

	// Wrong FilterIDProductSticker Options count
	prodCount = 5
	searchResult = &SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDTaobao},
			{ID: search_opts.FilterIDBrand},
			{
				ID: search_opts.FilterIDProductSticker,
				Options: []*search.FilterOption{
					{
						Val:       "Option",
						ProdCount: &prodCount,
					},
					{
						Val:       "Option2",
						ProdCount: &prodCount,
					},
				},
			},
		},
		TotalProducts: 5,
	}
	result = isHasTopSticker(searchResult)
	assert.Equal(t, result, false)
	assert.Equal(t, len(searchResult.Filters), 3)

	// Filter FilterIDProductSticker does not exist
	searchResult = &SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDTaobao},
			{ID: search_opts.FilterIDBrand},
		},
		TotalProducts: 5,
	}
	result = isHasTopSticker(searchResult)
	assert.Equal(t, result, false)
	assert.Equal(t, len(searchResult.Filters), 2)
}

func TestRemoveChildrenFromCategoryFilterWithEmptyFilters(t *testing.T) {
	removeChildrenFromCategoryFilter(1, nil)
}

func TestRemoveChildrenFromCategoryFilterWith5thLevels(t *testing.T) {
	filters := search.OneFilter{
		Options: []*search.FilterOption{
			{Label: "Level#1", Children: []*search.FilterOption{
				{Label: "Level#2", Children: []*search.FilterOption{
					{Label: "Level#3", Children: []*search.FilterOption{
						{Label: "Level#4", Children: []*search.FilterOption{
							{Label: "Level#5", Children: []*search.FilterOption{}}}}}}}}}}}}
	for _, option := range filters.Options {
		removeChildrenFromCategoryFilter(1, option)
	}

	if assert.Equal(t, len(filters.Options), 1) &&
		assert.Equal(t, len(filters.Options[0].Children), 1) &&
		assert.Equal(t, len(filters.Options[0].Children[0].Children), 1) {

		// Level#3 is last level
		assert.Equal(t, filters.Options[0].Children[0].Children[0].Label, "Level#3")
		assert.Equal(t, len(filters.Options[0].Children[0].Children[0].Children), 0)
	}
}

func TestRemoveChildrenFromCategoryFilterWith4thLevels(t *testing.T) {
	filters := search.OneFilter{
		Options: []*search.FilterOption{
			{
				Label: "Level#1",
				Children: []*search.FilterOption{
					{
						Label: "Level #2.1",
						Children: []*search.FilterOption{
							{
								Label:    "Level #3.1",
								Children: []*search.FilterOption{{Label: "Level #4.1"}},
							},
							{Label: "Level #3.2"},
						},
					},
					{
						Label: "Level #2.2",
						Children: []*search.FilterOption{
							{Label: "Level #3.3"},
							{Label: "Level #3.4",
								Children: []*search.FilterOption{{Label: "Level #4.2"}}},
							{Label: "Level #3.5"},
						},
					},
					{Label: "Level #2.3", Children: []*search.FilterOption{}},
				},
			},
		},
	}
	for _, option := range filters.Options {
		removeChildrenFromCategoryFilter(1, option)
	}
	if assert.Equal(t, len(filters.Options), 1) &&
		assert.Equal(t, len(filters.Options[0].Children), 3) {

		assert.Equal(t, filters.Options[0].Children[0].Label, "Level #2.1")
		if assert.Equal(t, len(filters.Options[0].Children[0].Children), 2) {
			assert.Equal(t, filters.Options[0].Children[0].Children[0].Label, "Level #3.1")
			assert.Equal(t, filters.Options[0].Children[0].Children[1].Label, "Level #3.2")
			// Level #4 was removed
			assert.Equal(t, len(filters.Options[0].Children[0].Children[0].Children), 0)
		}

		assert.Equal(t, filters.Options[0].Children[1].Label, "Level #2.2")
		if assert.Equal(t, len(filters.Options[0].Children[1].Children), 3) {
			assert.Equal(t, filters.Options[0].Children[1].Children[0].Label, "Level #3.3")
			assert.Equal(t, filters.Options[0].Children[1].Children[1].Label, "Level #3.4")
			assert.Equal(t, filters.Options[0].Children[1].Children[2].Label, "Level #3.5")
			// Level #4 was removed
			assert.Equal(t, len(filters.Options[0].Children[1].Children[1].Children), 0)
		}

		assert.Equal(t, filters.Options[0].Children[2].Label, "Level #2.3")
		assert.Equal(t, len(filters.Options[0].Children[2].Children), 0)
	}
}
