package model_service

import (
	//"fmt"
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"godep.lzd.co/catalog_api_client_go/transfer"
	"mobile_search_api/srv/search_opts"
)

type tmpCatalogAPI struct{}

func (c *tmpCatalogAPI) GetHighlights(ctx context.Context, ids []string, field string) (transfer.HighlightsByCriteriaV1Response, error) {
	if len(ids) > 0 && ids[0] == search_opts.TaobaoHighlightLabel && field == FieldName {
		id := uint64(222)
		urlKey := "taobao-collection"
		name := "Taobao"
		nameDisplay := "Taobao Collection"
		return transfer.HighlightsByCriteriaV1Response{
			transfer.HighlightsItem{
				IDCatalogAttributeOptionGlobalLazadaHighlights: &id,
				Name:        &name,
				NameDisplay: &nameDisplay,
				URLKey:      &urlKey,
			},
		}, nil
	}
	if len(ids) > 0 && ids[0] == "taobao-collection" && field == FieldURLKey {
		id := uint64(222)
		urlKey := "taobao-collection"
		name := "Taobao"
		nameDisplay := "Taobao Collection"
		return transfer.HighlightsByCriteriaV1Response{
			transfer.HighlightsItem{
				IDCatalogAttributeOptionGlobalLazadaHighlights: &id,
				Name:        &name,
				NameDisplay: &nameDisplay,
				URLKey:      &urlKey,
			},
		}, nil
	}
	return transfer.HighlightsByCriteriaV1Response{}, nil
}

func (c *tmpCatalogAPI) GetSuppliers(ctx context.Context, ids []string, field string) ([]transfer.SupplierSearchV2ResponseItem, error) {
	if len(ids) > 0 && ids[0] == "tb-collection" && field == FieldURLKey {
		return []transfer.SupplierSearchV2ResponseItem{
			transfer.SupplierSearchV2ResponseItem{
				IDSupplier: 111,
				Name:       "TB Collection",
				UrlKey:     "tb-collection",
			},
		}, nil
	}
	return []transfer.SupplierSearchV2ResponseItem{}, nil
}

func (c *tmpCatalogAPI) GetBrands(ctx context.Context, ids []string, field string) ([]transfer.BrandSearchV2ResponseItem, error) {
	if len(ids) > 0 && ids[0] == "apple" && field == FieldURLKey {
		return []transfer.BrandSearchV2ResponseItem{
			transfer.BrandSearchV2ResponseItem{
				IDCatalogBrand: 556,
				Name:           "Apple",
				URLKey:         "apple",
			},
		}, nil
	}
	return []transfer.BrandSearchV2ResponseItem{}, nil
}

func (c *tmpCatalogAPI) GetCategories(ctx context.Context, ids []string, field string) ([]transfer.CategorySearchV2ResponseItem, error) {
	if len(ids) > 0 && ids[0] == "shop-mobiles" && field == FieldURLKey {
		return []transfer.CategorySearchV2ResponseItem{
			transfer.CategorySearchV2ResponseItem{
				IDCatalogCategory: 123,
				Name:              "Mobiles",
				URLKey:            "shop-mobiles",
			},
		}, nil
	}
	if len(ids) > 0 && ids[0] == "1234" && field == FieldID {
		return []transfer.CategorySearchV2ResponseItem{
			transfer.CategorySearchV2ResponseItem{
				IDCatalogCategory: 1234,
				Name:              "Mobiles child",
				URLKey:            "shop-mobiles-child",
			},
		}, nil
	}
	return []transfer.CategorySearchV2ResponseItem{}, nil
}

func TestLoadModelByCategory(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelCategory,
		Path:  "shop-mobiles",
	}
	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	model, err := modelServiceNil.GetResultModel(context.Background(), opts)
	assert.Nil(t, model, "")
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Category was not loaded", "")
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	model, err = modelService.GetResultModel(context.Background(), opts)
	assert.Nil(t, err, "")
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.FilterID, search_opts.FilterIDCategory, "")
		assert.Equal(t, model.Label, "Mobiles", "")
		assert.Equal(t, model.ID, int64(123), "")
		assert.Equal(t, model.Value, "123", "")
		assert.Equal(t, model.URLKey, "shop-mobiles", "")
	}
}

func TestLoadModelByCategorySubCategory(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model:   search_opts.ModelCategory,
		Path:    "shop-mobiles",
		Filters: search_opts.FilterIDCategory + "~1234",
	}
	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	model, err := modelServiceNil.GetResultModel(context.Background(), opts)
	assert.Nil(t, model, "")
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Category was not loaded", "")
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	model, err = modelService.GetResultModel(context.Background(), opts)
	assert.Nil(t, err, "")
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.FilterID, search_opts.FilterIDCategory, "")
		assert.Equal(t, model.Label, "Mobiles child", "")
		assert.Equal(t, model.ID, int64(1234), "")
		assert.Equal(t, model.Value, "1234", "")
		assert.Equal(t, model.URLKey, "shop-mobiles-child", "")
	}
}

func TestLoadModelByBrand(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelBrand,
		Path:  "apple",
	}
	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	model, err := modelServiceNil.GetResultModel(context.Background(), opts)
	assert.Nil(t, model, "")
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Brand was not loaded", "")
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	model, err = modelService.GetResultModel(context.Background(), opts)
	assert.Nil(t, err, "")
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.FilterID, search_opts.FilterIDBrand, "")
		assert.Equal(t, model.Label, "Apple", "")
		assert.Equal(t, model.ID, int64(556), "")
		assert.Equal(t, model.Value, "556", "")
		assert.Equal(t, model.URLKey, "apple", "")
	}
}

func TestLoadModelBySeller(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelSeller,
		Path:  "tb-collection",
	}
	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	model, err := modelServiceNil.GetResultModel(context.Background(), opts)
	assert.Nil(t, model, "")
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Supplier was not loaded", "")
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	model, err = modelService.GetResultModel(context.Background(), opts)
	assert.Nil(t, err, "")
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.FilterID, search_opts.FilterIDSeller, "")
		assert.Equal(t, model.Label, "TB Collection", "")
		assert.Equal(t, model.ID, int64(111), "")
		assert.Equal(t, model.Value, "111", "")
		assert.Equal(t, model.URLKey, "tb-collection", "")
	}
}

func TestLoadModelByHighlight(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelHighlight,
		Path:  "taobao-collection",
	}
	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	model, err := modelServiceNil.GetResultModel(context.Background(), opts)
	assert.Nil(t, model, "")
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Highlight was not loaded", "")
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	model, err = modelService.GetResultModel(context.Background(), opts)
	assert.Nil(t, err, "")
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.FilterID, search_opts.FilterIDHighlight, "")
		assert.Equal(t, model.Label, search_opts.TaobaoHighlightLabel, "")
		assert.Equal(t, model.ID, int64(222), "")
		assert.Equal(t, model.Value, "Taobao", "")
		assert.Equal(t, model.URLKey, "taobao-collection", "")
	}
}

func TestLoadModelByTaobao(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model:   search_opts.ModelSearchQ,
		Query:   "milk",
		Filters: search_opts.FilterIDTaobao + "~" + search_opts.FilterValueTaobao,
	}
	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	model, err := modelServiceNil.GetResultModel(context.Background(), opts)
	assert.Nil(t, model, "")
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Highlight was not loaded", "")
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	model, err = modelService.GetResultModel(context.Background(), opts)
	assert.Nil(t, err, "")
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.FilterID, search_opts.FilterIDTaobao, "")
		assert.Equal(t, model.Label, search_opts.TaobaoHighlightLabel, "")
		assert.Equal(t, model.ID, int64(222), "")
		assert.Equal(t, model.Value, search_opts.FilterValueTaobao, "")
		assert.Equal(t, model.URLKey, "taobao-collection", "")
	}
}

type nilCatalogAPI struct{}

func (c *nilCatalogAPI) GetHighlights(ctx context.Context, ids []string, field string) (transfer.HighlightsByCriteriaV1Response, error) {
	return transfer.HighlightsByCriteriaV1Response{}, nil
}
func (c *nilCatalogAPI) GetSuppliers(ctx context.Context, ids []string, field string) ([]transfer.SupplierSearchV2ResponseItem, error) {
	return []transfer.SupplierSearchV2ResponseItem{}, nil
}
func (c *nilCatalogAPI) GetBrands(ctx context.Context, ids []string, field string) ([]transfer.BrandSearchV2ResponseItem, error) {
	return []transfer.BrandSearchV2ResponseItem{}, nil
}
func (c *nilCatalogAPI) GetCategories(ctx context.Context, ids []string, field string) ([]transfer.CategorySearchV2ResponseItem, error) {
	return []transfer.CategorySearchV2ResponseItem{}, nil
}

func TestLoadModelByCategoryAndBrandNotFound(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelCategory,
		Path:  "shop-mobiles/apple",
	}

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	models, err := modelService.GetResultModels(context.Background(), opts)
	if assert.NotNil(t, err, "") {
		assert.Equal(t, err.Error(), "Category was not loaded", "")
	}
	if assert.Equal(t, len(models), 1, "") {
		assert.Equal(t, models[0].FilterID, search_opts.FilterIDCategory, "")
		assert.Equal(t, models[0].Label, "Mobiles", "")
		assert.Equal(t, models[0].ID, int64(123), "")
		assert.Equal(t, models[0].Value, "123", "")
		assert.Equal(t, models[0].URLKey, "shop-mobiles", "")
	}
}

func TestGetModel(t *testing.T) {
	ctx := InitContextCache(context.Background())
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelBrand,
		URLKey: "apple",
		ID:     2,
		Label:  "Brand Apple",
	})
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-mobiles",
		ID:     123,
		Label:  "Mobiles",
	})
	model := getModel(ctx, &ModelOpts{
		Lang:  "en",
		Model: search_opts.ModelCategory,
		Key:   "shop-mobiles",
		Field: FieldURLKey,
	})

	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.Model, search_opts.ModelCategory, "")
		assert.Equal(t, model.Label, "Mobiles", "")
		assert.Equal(t, model.ID, int64(123), "")
		assert.Equal(t, model.URLKey, "shop-mobiles", "")
	}

	model = getModel(ctx, &ModelOpts{
		Lang:  "en",
		Model: search_opts.ModelCategory,
		Key:   "apple",
		Field: FieldURLKey,
	})
	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.Model, search_opts.ModelBrand, "")
		assert.Equal(t, model.Label, "Brand Apple", "")
		assert.Equal(t, model.ID, int64(2), "")
		assert.Equal(t, model.URLKey, "apple", "")
	}
}

func TestLoadModelByCategoryAndBrandInCache(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelCategory,
		Path:  "shop-mobiles/apple",
	}
	ctx := InitContextCache(context.Background())
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelBrand,
		URLKey: "apple",
		ID:     2,
		Label:  "Brand Apple",
	})

	modelService := NewModelService(&tmpCatalogAPI{}, nil)
	models, err := modelService.GetResultModels(ctx, opts)
	assert.Nil(t, err, "")
	if assert.Equal(t, len(models), 2, "") {
		assert.Equal(t, models[0].Model, search_opts.ModelCategory, "")
		assert.Equal(t, models[0].Label, "Mobiles", "")
		assert.Equal(t, models[0].ID, int64(123), "")
		assert.Equal(t, models[0].URLKey, "shop-mobiles", "")
		assert.Equal(t, models[1].Model, search_opts.ModelBrand, "")
		assert.Equal(t, models[1].Label, "Brand Apple", "")
		assert.Equal(t, models[1].ID, int64(2), "")
		assert.Equal(t, models[1].URLKey, "apple", "")
	}
}

func TestLoadModelByCategoryAndBrandInCacheWithNilCatalogAPI(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model: search_opts.ModelCategory,
		Path:  "shop-mobiles/apple",
	}
	ctx := InitContextCache(context.Background())
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelBrand,
		URLKey: "apple",
		ID:     2,
		Label:  "Brand Apple",
	})
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-mobiles",
		ID:     123,
		Label:  "Mobiles",
	})

	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	models, err := modelServiceNil.GetResultModels(ctx, opts)
	assert.Nil(t, err, "")
	if assert.Equal(t, len(models), 2, "") {
		assert.Equal(t, models[0].Model, search_opts.ModelCategory, "")
		assert.Equal(t, models[0].Label, "Mobiles", "")
		assert.Equal(t, models[0].ID, int64(123), "")
		assert.Equal(t, models[0].URLKey, "shop-mobiles", "")
		assert.Equal(t, models[1].Model, search_opts.ModelBrand, "")
		assert.Equal(t, models[1].Label, "Brand Apple", "")
		assert.Equal(t, models[1].ID, int64(2), "")
		assert.Equal(t, models[1].URLKey, "apple", "")
	}
}

func TestLoadModelBySubCategoryAndBrandInCacheWithNilCatalogAPI(t *testing.T) {
	opts := &search_opts.SearchOpts{
		Model:   search_opts.ModelCategory,
		Path:    "shop-mobiles/apple",
		Filters: "category~1234",
	}
	ctx := InitContextCache(context.Background())
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelBrand,
		URLKey: "apple",
		ID:     2,
		Label:  "Brand Apple",
	})
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-mobiles",
		ID:     123,
		Label:  "Mobiles",
	})
	AddModel(ctx, &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-mobiles-sub-category",
		ID:     1234,
		Label:  "Mobiles sub category name",
	})

	modelServiceNil := NewModelService(&nilCatalogAPI{}, nil)
	models, err := modelServiceNil.GetResultModels(ctx, opts)
	assert.Nil(t, err, "")
	if assert.Equal(t, len(models), 2, "") {
		assert.Equal(t, models[0].Model, search_opts.ModelCategory, "")
		assert.Equal(t, models[0].Label, "Mobiles sub category name", "")
		assert.Equal(t, models[0].ID, int64(1234), "")
		assert.Equal(t, models[0].URLKey, "shop-mobiles-sub-category", "")
		assert.Equal(t, models[1].Model, search_opts.ModelBrand, "")
		assert.Equal(t, models[1].Label, "Brand Apple", "")
		assert.Equal(t, models[1].ID, int64(2), "")
		assert.Equal(t, models[1].URLKey, "apple", "")
	}
}

func TestGetConcatedModelLabels(t *testing.T) {
	models := []*ModelData{
		&ModelData{
			Model: search_opts.ModelBrand,
			Label: "Apple",
		},
		&ModelData{
			Model: search_opts.ModelHighlight,
			Label: "Taobao Collection",
		},
		&ModelData{
			Model: search_opts.ModelCategory,
			Label: "Mobiles",
		},
		&ModelData{
			Model: search_opts.ModelSeller,
			Label: "Seller Name1",
		},
		&ModelData{
			Model: search_opts.ModelCategory,
			Label: "Laptops",
		},
		&ModelData{
			Model: search_opts.ModelBrand,
			Label: "Samsung",
		},
		&ModelData{
			Model: search_opts.ModelSeller,
			Label: "Seller NameEn2",
		},
		&ModelData{
			Model: search_opts.ModelHighlight,
			Label: "CampaignName",
		},
	}

	assert.Equal(t, GetConcatedModelLabels(models), "Laptops, Mobiles / Apple, Samsung / CampaignName, Taobao Collection / Seller Name1, Seller NameEn2", "")
}
