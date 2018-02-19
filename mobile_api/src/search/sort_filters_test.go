package search_service

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/search_opts"
)

func TestAlphabetizeBrandFiltersEmpty(t *testing.T) {
	searchResult := SearchResult{}
	searchResult.AlphabetizeBrandFilters()

	assert.Equal(t, len(searchResult.Filters), 0)
}

func TestAlphabetizeBrandFiltersWithOutBrandFilter(t *testing.T) {
	searchResult := SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDCategory},
			{ID: search_opts.FilterIDHighlight},
		},
	}
	searchResult.AlphabetizeBrandFilters()
	assert.Equal(t, len(searchResult.Filters), 2)
}

func TestAlphabetizeBrandFiltersWithEmptyBrandFilter(t *testing.T) {
	searchResult := SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDHighlight},
			{ID: search_opts.FilterIDBrand},
			{ID: search_opts.FilterIDCategory},
		},
	}
	searchResult.AlphabetizeBrandFilters()
	if assert.Equal(t, len(searchResult.Filters), 3) {
		assert.Equal(t, len(searchResult.Filters[1].Options), 0)
	}
}

func TestAlphabetizeBrandFiltersWithOutSelected(t *testing.T) {
	searchResult := SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDHighlight},
			{
				ID: search_opts.FilterIDBrand,
				Options: []*search.FilterOption{
					{Label: "Aardman"},
					{Label: "2B"},
					{Label: "Merrithew"},
					{Label: "Apple"},
				},
			},
			{ID: search_opts.FilterIDCategory},
		},
	}
	searchResult.AlphabetizeBrandFilters()
	if assert.Equal(t, len(searchResult.Filters), 3) && assert.Equal(t, len(searchResult.Filters[1].Options), 4) {
		assert.Equal(t, searchResult.Filters[1].Options[0].Label, "2B")
		assert.Equal(t, searchResult.Filters[1].Options[1].Label, "Aardman")
		assert.Equal(t, searchResult.Filters[1].Options[2].Label, "Apple")
		assert.Equal(t, searchResult.Filters[1].Options[3].Label, "Merrithew")
	}
}

func TestAlphabetizeBrandFiltersWithBoosted(t *testing.T) {
	searchResult := SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDHighlight},
			{
				ID: search_opts.FilterIDBrand,
				SelectedOptions: []*search.FilterOption{
					{Label: "NORTH"},
					{Label: "Apple"},
					{Label: "Penny"},
				},
				Options: []*search.FilterOption{
					{Label: "Aardman"},
					{Label: "2B"},
					{Label: "Penny"},
					{Label: "Apple"},
					{Label: "NORTH"},
					{Label: "Merrithew"},
				},
			},
			{ID: search_opts.FilterIDCategory},
		},
	}
	searchResult.AlphabetizeBrandFilters()
	if assert.Equal(t, len(searchResult.Filters), 3) && assert.Equal(t, len(searchResult.Filters[1].Options), 6) {
		assert.Equal(t, searchResult.Filters[1].Options[0].Label, "Apple")
		assert.Equal(t, searchResult.Filters[1].Options[1].Label, "NORTH")
		assert.Equal(t, searchResult.Filters[1].Options[2].Label, "Penny")
		assert.Equal(t, searchResult.Filters[1].Options[3].Label, "2B")
		assert.Equal(t, searchResult.Filters[1].Options[4].Label, "Aardman")
		assert.Equal(t, searchResult.Filters[1].Options[5].Label, "Merrithew")
	}
}

func TestAlphabetizeBrandFiltersWithBoostedAndDeboosted(t *testing.T) {
	searchResult := SearchResult{
		Filters: []search.OneFilter{
			{ID: search_opts.FilterIDHighlight},
			{
				ID: search_opts.FilterIDBrand,
				SelectedOptions: []*search.FilterOption{
					{Label: "NORTH"},
					{Label: "Apple"},
					{Label: "Penny"},
				},
				Options: []*search.FilterOption{
					{Label: "Unbranded"}, //Deboosted
					{Label: "Aardman"},
					{Label: "2B"},
					{Label: "Penny"},
					{Label: "No"}, //Deboosted
					{Label: "Apple"},
					{Label: "Generic"}, //Deboosted
					{Label: "NORTH"},
					{Label: "Merrithew"},
				},
			},
			{ID: search_opts.FilterIDCategory},
		},
	}
	searchResult.AlphabetizeBrandFilters()
	if assert.Equal(t, len(searchResult.Filters), 3) && assert.Equal(t, len(searchResult.Filters[1].Options), 9) {
		assert.Equal(t, searchResult.Filters[1].Options[0].Label, "Apple")
		assert.Equal(t, searchResult.Filters[1].Options[1].Label, "NORTH")
		assert.Equal(t, searchResult.Filters[1].Options[2].Label, "Penny")
		assert.Equal(t, searchResult.Filters[1].Options[3].Label, "2B")
		assert.Equal(t, searchResult.Filters[1].Options[4].Label, "Aardman")
		assert.Equal(t, searchResult.Filters[1].Options[5].Label, "Merrithew")
		assert.Equal(t, searchResult.Filters[1].Options[6].Label, "Generic")   //Deboosted
		assert.Equal(t, searchResult.Filters[1].Options[7].Label, "No")        //Deboosted
		assert.Equal(t, searchResult.Filters[1].Options[8].Label, "Unbranded") //Deboosted
	}
}

func TestSortFiltersBySelectedOptions(t *testing.T) {
	var searchResult *SearchResult
	searchResult = &SearchResult{
		Filters: []search.OneFilter{
			{
				ID:              search_opts.FilterIDCategory,
				SelectedOptions: []*search.FilterOption{},
				Options: []*search.FilterOption{
					{
						Val: "99",
						Children: []*search.FilterOption{
							{Val: "199"},
						},
					},
				},
			},
			{
				ID: search_opts.FilterIDBrand,
				SelectedOptions: []*search.FilterOption{
					{Val: "5"},
				},
				Options: []*search.FilterOption{
					{Val: "5"},
				},
			},
			{
				ID:              search_opts.FilterIDHighlight,
				SelectedOptions: []*search.FilterOption{},
				Options: []*search.FilterOption{
					{Val: "55"},
				},
			},
		},
	}
	searchResult.SortFiltersBySelectedOptions()

	if assert.NotNil(t, searchResult, "") && assert.Equal(t, len(searchResult.Filters), 3) {
		assert.Equal(t, searchResult.Filters[0].ID, search_opts.FilterIDBrand)
		assert.Equal(t, searchResult.Filters[1].ID, search_opts.FilterIDCategory)
		assert.Equal(t, searchResult.Filters[2].ID, search_opts.FilterIDHighlight)

		assert.Equal(t, len(searchResult.Filters[0].SelectedOptions), 1)
		assert.Equal(t, len(searchResult.Filters[1].SelectedOptions), 0)
		assert.Equal(t, len(searchResult.Filters[2].SelectedOptions), 0)
	}

	searchResult = &SearchResult{
		Filters: []search.OneFilter{
			{
				ID:              search_opts.FilterIDTaobao,
				SelectedOptions: []*search.FilterOption{},
				Options:         []*search.FilterOption{},
			},
			{
				ID: search_opts.FilterIDCategory,
				SelectedOptions: []*search.FilterOption{
					{Val: "99"},
					{Val: "199"},
				},
				Options: []*search.FilterOption{
					{
						Val: "99",
						Children: []*search.FilterOption{
							{Val: "199"},
						},
					},
				},
			},
			{
				ID:              search_opts.FilterIDBrand,
				SelectedOptions: []*search.FilterOption{},
				Options: []*search.FilterOption{
					{Val: "5"},
				},
			},
			{
				ID: search_opts.FilterIDHighlight,
				SelectedOptions: []*search.FilterOption{
					{Val: "55"},
				},
				Options: []*search.FilterOption{
					{Val: "55"},
				},
			},
		},
	}
	searchResult.SortFiltersBySelectedOptions()

	if assert.NotNil(t, searchResult, "") && assert.Equal(t, len(searchResult.Filters), 4) {
		assert.Equal(t, searchResult.Filters[0].ID, search_opts.FilterIDCategory)
		assert.Equal(t, searchResult.Filters[1].ID, search_opts.FilterIDHighlight)
		assert.Equal(t, searchResult.Filters[2].ID, search_opts.FilterIDTaobao)
		assert.Equal(t, searchResult.Filters[3].ID, search_opts.FilterIDBrand)

		assert.Equal(t, len(searchResult.Filters[0].SelectedOptions), 2)
		assert.Equal(t, len(searchResult.Filters[1].SelectedOptions), 1)
		assert.Equal(t, len(searchResult.Filters[2].SelectedOptions), 0)
		assert.Equal(t, len(searchResult.Filters[3].SelectedOptions), 0)
	}
}
