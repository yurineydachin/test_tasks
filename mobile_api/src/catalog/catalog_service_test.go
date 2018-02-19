package catalog_service

import (
	"testing"

	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/search"
	"mobile_search_api/srv/search_opts"

	"github.com/stretchr/testify/assert"
	i18n "godep.lzd.co/go-i18n"
)

func TestFillSearchInfoWithSearchContextIgnored(t *testing.T) {

	catalogService := &CatalogService{
		i18nManager: i18n.NewManagerMock("sg"),
	}

	result := &CatalogResult{}
	searchResponse := &search_service.SearchResult{
		TotalProducts:        5,
		SearchContextIgnored: true,
	}
	catalogService.fillSearchInfo(result, &search_opts.SearchOpts{}, searchResponse, "")

	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.Title, "") {
		assert.Equal(t, result.SearchInfo.Title.Text, "We didn’t find results for “%s” in %s")
		assert.Equal(t, result.SearchInfo.Title.Highlight, "We didn’t find results for “%s” in %s")
	}
	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.SubTitle, "") {
		assert.Equal(t, result.SearchInfo.SubTitle.Text, "Here are 5 results found from All Departments")
		assert.Equal(t, result.SearchInfo.SubTitle.Highlight, "")
	}
}

func TestFillSearchInfoWithSearchContextIgnoredQuery(t *testing.T) {

	catalogService := &CatalogService{
		i18nManager: i18n.NewManagerMock("sg"),
	}

	result := &CatalogResult{}
	searchResponse := &search_service.SearchResult{
		TotalProducts:        5,
		SearchContextIgnored: true,
	}
	searchOpts := &search_opts.SearchOpts{
		Query: "sony",
	}
	catalogService.fillSearchInfo(result, searchOpts, searchResponse, "main_page")

	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.Title, "") {
		assert.Equal(t, result.SearchInfo.Title.Text, "We didn’t find results for “sony” in main_page")
		assert.Equal(t, result.SearchInfo.Title.Highlight, "We didn’t find results for “sony” in main_page")
	}
	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.SubTitle, "") {
		assert.Equal(t, result.SearchInfo.SubTitle.Text, "Here are 5 results found from All Departments")
		assert.Equal(t, result.SearchInfo.SubTitle.Highlight, "")
	}
}

func TestFillSearchInfoWithSearchContext(t *testing.T) {

	catalogService := &CatalogService{
		i18nManager: i18n.NewManagerMock("sg"),
	}

	result := &CatalogResult{}
	searchResponse := &search_service.SearchResult{
		TotalProducts:        5,
		SearchContextIgnored: false,
	}
	searchOpts := &search_opts.SearchOpts{
		Query:         "sony",
		SearchContext: "context",
	}
	catalogService.fillSearchInfo(result, searchOpts, searchResponse, "main_page")

	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.Title, "") {
		assert.Equal(t, result.SearchInfo.Title.Text, "5 results found in main_page")
		assert.Equal(t, result.SearchInfo.Title.Highlight, "main_page")
	}
	assert.Nil(t, result.SearchInfo.SubTitle, "")
}

func TestFillSearchInfoWithFilters(t *testing.T) {

	catalogService := &CatalogService{
		i18nManager: i18n.NewManagerMock("sg"),
	}

	result := &CatalogResult{}
	searchResponse := &search_service.SearchResult{
		TotalProducts:        5,
		SearchContextIgnored: false,
	}
	searchOpts := &search_opts.SearchOpts{
		Query:   "sony",
		Filters: "category~123",
	}
	catalogService.fillSearchInfo(result, searchOpts, searchResponse, "main_page")

	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.Title, "") {
		assert.Equal(t, result.SearchInfo.Title.Text, "5 results found in main_page")
		assert.Equal(t, result.SearchInfo.Title.Highlight, "main_page")
	}
	assert.Nil(t, result.SearchInfo.SubTitle, "")
}

func TestFillSearchInfoWithQuery(t *testing.T) {

	catalogService := &CatalogService{
		i18nManager: i18n.NewManagerMock("sg"),
	}

	result := &CatalogResult{}
	searchResponse := &search_service.SearchResult{
		TotalProducts:        5,
		SearchContextIgnored: false,
	}
	searchOpts := &search_opts.SearchOpts{
		Query: "sony",
	}
	catalogService.fillSearchInfo(result, searchOpts, searchResponse, "")

	if assert.NotNil(t, result.SearchInfo, "") && assert.NotNil(t, result.SearchInfo.Title, "") {
		assert.Equal(t, result.SearchInfo.Title.Text, "5 results found")
		assert.Equal(t, result.SearchInfo.Title.Highlight, "")
	}
	assert.Nil(t, result.SearchInfo.SubTitle, "")
}

func TestFillSearchInfoEmpty(t *testing.T) {

	catalogService := &CatalogService{
		i18nManager: i18n.NewManagerMock("sg"),
	}

	result := &CatalogResult{}
	searchResponse := &search_service.SearchResult{
		SearchContextIgnored: false,
	}
	catalogService.fillSearchInfo(result, &search_opts.SearchOpts{}, searchResponse, "")

	assert.Nil(t, result.SearchInfo, "")
}

func TestConvertDidYouMeanEmpty(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Query: "query",
	}
	suggestions := convertDidYouMean(searchOpts, &search_service.SearchResult{})
	assert.Nil(t, suggestions, "")
}

func TestConvertDidYouMean(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Query: "query",
	}
	searchResponse := &search_service.SearchResult{
		DidYouMean: []search.DidYouMeanCollation{
			search.DidYouMeanCollation{
				Text: "1",
			},
			search.DidYouMeanCollation{
				Text: "2",
			},
			search.DidYouMeanCollation{
				Text: "3",
			},
			search.DidYouMeanCollation{
				Text: "4",
			},
		},
		TotalProducts: 4,
	}
	suggestions := convertDidYouMean(searchOpts, searchResponse)

	expectedCountKeywords := searchResponse.TotalProducts
	if searchResponse.TotalProducts > maxCountSearchSuggestionKeywords {
		expectedCountKeywords = maxCountSearchSuggestionKeywords
	}

	if assert.NotNil(t, suggestions, "") {
		if assert.NotNil(t, suggestions.Key, "") {
			assert.Equal(t, suggestions.Key, "query")
		}
		if assert.NotNil(t, suggestions.Suggestions, "") {
			assert.NotNil(t, len(suggestions.Suggestions), expectedCountKeywords)
		}
	}
}
