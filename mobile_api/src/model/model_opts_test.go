package model_service

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"mobile_search_api/srv/search_opts"
)

func TestFindFilterValueString(t *testing.T) {
	opts := &ModelOpts{
		Filters: "filterID~111@filterID2~name2@filterID3~33-44@taobao~ON",
	}
	assert.Equal(t, opts.findFilterValueString("filterID"), "111", "Error parsing filterID")
	assert.Equal(t, opts.findFilterValueString("filterID2"), "name2", "Error parsing filterID2")
	assert.Equal(t, opts.findFilterValueString("filterID3"), "33", "Error parsing filterID3")
	assert.Equal(t, opts.findFilterValueString("taobao"), "ON", "Error parsing taobao")
}

func TestResolveModelByFilterForModelSearchQNotResolved(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "mobiles",
		Filters: "filterID~111@filterID2~name2@filterID3~33-44",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), false, "")
	assert.Equal(t, opts.Model, search_opts.ModelSearchQ, "")
	assert.Equal(t, opts.Key, "", "")
	assert.Equal(t, opts.Field, FieldURLKey, "")
}

func TestResolveModelByFilterForModelSearchQToCategory(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "mobiles",
		Filters: search_opts.FilterIDCategory + "~111@" + search_opts.FilterIDHighlight + "~name2@" + search_opts.FilterIDBrand + "~33--44@",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Key, "111", "")
	assert.Equal(t, opts.Field, FieldID, "")
}

func TestResolveModelByFilterForModelSearchQToTaobao(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "mobiles",
		Filters: search_opts.FilterIDCategory + "~111@" + search_opts.FilterIDHighlight + "~name2@" + search_opts.FilterIDBrand + "~33--44@" + search_opts.FilterIDTaobao + "~" + search_opts.FilterValueTaobao,
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.FilterIDTaobao, "")
	assert.Equal(t, opts.Key, search_opts.TaobaoHighlightLabel, "")
	assert.Equal(t, opts.Field, FieldName, "")
}

func TestResolveModelByFilterForModelSearchQToHightlight(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "mobiles",
		Filters: search_opts.FilterIDHighlight + "~name2@" + search_opts.FilterIDBrand + "~33--44@" + search_opts.FilterIDSeller + "~123",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelHighlight, "")
	assert.Equal(t, opts.Key, "name2", "")
	assert.Equal(t, opts.Field, FieldName, "")
}

func TestResolveModelByFilterForModelSearchQToBrand(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "mobiles",
		Filters: search_opts.FilterIDBrand + "~33--44@" + search_opts.FilterIDSeller + "~123",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelBrand, "")
	assert.Equal(t, opts.Key, "33", "")
	assert.Equal(t, opts.Field, FieldID, "")
}

func TestResolveModelByFilterForModelSearchQToSeller(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "mobiles",
		Filters: search_opts.FilterIDSeller + "~123@filterID~111@filterID2~name2@filterID3~33-44",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelSeller, "")
	assert.Equal(t, opts.Key, "123", "")
	assert.Equal(t, opts.Field, FieldID, "")
}

func TestResolveModelByFilterForModelCategory(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelCategory,
		Path:    "shop-mobiles",
		Filters: "filterID~111@filterID2~name2@filterID3~33-44",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Key, "shop-mobiles", "")
	assert.Equal(t, opts.Field, FieldURLKey, "")
}

func TestResolveModelByFilterForModelCategorySubCategory(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelCategory,
		Path:    "shop-mobiles",
		Filters: "filterID~111@filterID2~name2@filterID3~33-44" + search_opts.ModelCategory + "~234",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelCategory, "")
	assert.Equal(t, opts.Key, "234", "")
	assert.Equal(t, opts.Field, FieldID, "")
}

func TestResolveModelByFilterForModelBrand(t *testing.T) {
	searchOpts := &search_opts.SearchOpts{
		Model:   search_opts.ModelBrand,
		Path:    "apple",
		Filters: "filterID~111@filterID2~name2@filterID3~33-44" + search_opts.ModelBrand + "~234",
	}
	opts := GetModelOpts(searchOpts)
	assert.Equal(t, opts.IsModelAllowed(), true, "")
	assert.Equal(t, opts.Model, search_opts.ModelBrand, "")
	assert.Equal(t, opts.Key, "apple", "")
	assert.Equal(t, opts.Field, FieldURLKey, "")
}
