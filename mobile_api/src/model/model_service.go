package model_service

import (
	"context"
	"errors"
	"sort"
	"strconv"
	"strings"

	"github.com/sergei-svistunov/gorpc/transport/cache"
	"godep.lzd.co/go-i18n"
	"mobile_search_api/srv/search_opts"
)

var modelPriority = []string{
	search_opts.ModelCategory,
	search_opts.ModelBrand,
	search_opts.ModelHighlight,
	search_opts.ModelSeller,
}

type ModelService struct {
	catalogAPI  ICatalogAPI
	i18nManager *i18n.Manager
}

type ModelData struct {
	Model         string
	FilterID      string
	Label         string
	OriginalLabel string
	Value         string
	ID            int64
	IsSis         bool
	URLKey        string
	RegionalKey   string
}

func (model *ModelData) GetCacheKeyURLKey() string {
	return model.Model + "_" + FieldURLKey + ":" + model.URLKey
}

func (model *ModelData) GetCacheKeyURLKeyCommon() string {
	return FieldURLKey + ":" + model.URLKey
}

func (model *ModelData) GetCacheKeyID() string {
	return model.Model + "_" + FieldID + ":" + strconv.FormatInt(model.ID, 10)
}

func (model *ModelData) GetCacheKeyName() string {
	return model.Model + "_" + FieldName + ":" + model.URLKey
}

func NewModelService(catalogAPI ICatalogAPI, i18nManager *i18n.Manager) *ModelService {
	return &ModelService{
		catalogAPI:  catalogAPI,
		i18nManager: i18nManager,
	}
}

func (service *ModelService) GetResultModels(ctx context.Context, searchOpts *search_opts.SearchOpts) ([]*ModelData, error) {
	if searchOpts == nil {
		return nil, errors.New("No opts")
	}

	var searchOptsList []*search_opts.SearchOpts
	if searchOpts.IsPathMulti() {
		keys := strings.Split(strings.Trim(searchOpts.Path, "/"), "/")
		searchOptsList = make([]*search_opts.SearchOpts, len(keys))
		for i, URLKey := range keys {
			searchOptsList[i] = &search_opts.SearchOpts{
				Lang:  searchOpts.Lang,
				Model: searchOpts.Model,
				Path:  URLKey,
			}
			if i == 0 {
				searchOptsList[i].Filters = searchOpts.Filters
			}
		}
	} else {
		searchOptsList = []*search_opts.SearchOpts{searchOpts}
	}

	var err error
	result := make([]*ModelData, 0, len(searchOptsList))
	for _, sOpts := range searchOptsList {
		opts := GetModelOpts(sOpts)
		if !opts.IsModelAllowed() {
			err = errors.New("Error no model or model not allowed")
			continue
		}
		model := getModel(ctx, opts)
		if model != nil {
			result = append(result, model)
			continue
		}
		model, err = service.loadModel(cache.NewContextWithTransportCache(ctx), opts)
		if err != nil || model == nil {
			continue
		}
		AddModel(ctx, model)
		result = append(result, model)
	}

	sortedResult := make([]*ModelData, 0, len(result))
	for _, m := range modelPriority {
		for _, model := range result {
			if model.Model == m {
				sortedResult = append(sortedResult, model)
			}
		}
	}

	return sortedResult, err
}

func (service *ModelService) GetResultModel(ctx context.Context, searchOpts *search_opts.SearchOpts) (*ModelData, error) {
	models, err := service.GetResultModels(ctx, searchOpts)
	if len(models) == 0 {
		return nil, err
	}
	return models[0], nil
}

func (service *ModelService) loadModel(ctx context.Context, opts *ModelOpts) (*ModelData, error) {
	if opts.Model == search_opts.FilterIDTaobao { // tmp model for dedecated filter
		return service.loadModelHighlightTaobao(ctx, opts)
	} else if opts.Model == search_opts.ModelHighlight {
		return service.loadModelHighlight(ctx, opts)
	} else if opts.Model == search_opts.ModelSeller {
		return service.loadModelSupplier(ctx, opts)
	} else if opts.Model == search_opts.ModelCategory {
		return service.loadModelCategory(ctx, opts)
	} else if opts.Model == search_opts.ModelBrand {
		return service.loadModelBrand(ctx, opts)
	}
	return nil, errors.New("Model not supported")
}

func (service *ModelService) loadModelHighlight(ctx context.Context, opts *ModelOpts) (*ModelData, error) {
	items, err := service.catalogAPI.GetHighlights(ctx, []string{opts.Key}, opts.Field)
	if err != nil {
		return nil, err
	}
	if len(items) == 0 {
		return nil, errors.New("Highlight was not loaded")
	}
	result := &ModelData{
		Model:    search_opts.ModelHighlight,
		FilterID: search_opts.FilterIDHighlight,
		IsSis:    items[0].PageType != nil && *items[0].PageType == search_opts.HighlightSIS,
	}
	if items[0].NameDisplay != nil && *items[0].NameDisplay != "" {
		result.Label = *items[0].NameDisplay
	}
	result.OriginalLabel = result.Label
	if items[0].Name != nil && *items[0].Name != "" {
		result.Value = *items[0].Name
	} else {
		return nil, errors.New("Hihglight value is empty")
	}
	if result.Label == "" {
		result.Value = result.Label
	}
	if opts.Lang != "" && service.i18nManager != nil {
		dictionary := service.i18nManager.GetDictionary(opts.Lang)
		result.Label, _ = dictionary.Translate(result.Label, nil, nil)
	}

	if items[0].IDCatalogAttributeOptionGlobalLazadaHighlights != nil {
		result.ID = int64(*items[0].IDCatalogAttributeOptionGlobalLazadaHighlights)
	}
	if items[0].URLKey != nil {
		result.URLKey = *items[0].URLKey
	}
	return result, nil
}

func (service *ModelService) loadModelHighlightTaobao(ctx context.Context, opts *ModelOpts) (*ModelData, error) {
	result, err := service.loadModelHighlight(ctx, opts)
	if err != nil {
		return nil, err
	}
	result.FilterID = search_opts.FilterIDTaobao
	result.Value = search_opts.FilterValueTaobao
	return result, nil
}

func (service *ModelService) loadModelSupplier(ctx context.Context, opts *ModelOpts) (*ModelData, error) {
	items, err := service.catalogAPI.GetSuppliers(ctx, []string{opts.Key}, opts.Field)
	if err != nil {
		return nil, err
	}
	if len(items) == 0 {
		return nil, errors.New("Supplier was not loaded")
	}
	result := &ModelData{
		Model:    search_opts.ModelSeller,
		FilterID: search_opts.FilterIDSeller,
		Label:    items[0].Name,
		Value:    strconv.FormatUint(items[0].IDSupplier, 10),
		ID:       int64(items[0].IDSupplier),
		URLKey:   items[0].UrlKey,
	}
	if opts.Lang == "en" && items[0].NameEn != nil && *items[0].NameEn != "" {
		result.Label = *items[0].NameEn
	}
	result.OriginalLabel = items[0].Name
	return result, nil
}

func (service *ModelService) loadModelCategory(ctx context.Context, opts *ModelOpts) (*ModelData, error) {
	items, err := service.catalogAPI.GetCategories(ctx, []string{opts.Key}, opts.Field)
	if err != nil {
		return nil, err
	}
	if len(items) == 0 {
		return nil, errors.New("Category was not loaded")
	}
	result := &ModelData{
		Model:    search_opts.ModelCategory,
		FilterID: search_opts.FilterIDCategory,
		Label:    items[0].Name,
		Value:    strconv.FormatUint(items[0].IDCatalogCategory, 10),
		ID:       int64(items[0].IDCatalogCategory),
		URLKey:   items[0].URLKey,
	}
	if opts.Lang != "en" && items[0].NameLocal != nil && *items[0].NameLocal != "" {
		result.Label = *items[0].NameLocal
	} else if opts.Lang == "en" && items[0].NameEn != nil && *items[0].NameEn != "" {
		result.Label = *items[0].NameEn
	}
	if items[0].RegionalKey != nil {
		result.RegionalKey = *items[0].RegionalKey
	}
	result.OriginalLabel = result.Label
	return result, nil
}

func (service *ModelService) loadModelBrand(ctx context.Context, opts *ModelOpts) (*ModelData, error) {
	items, err := service.catalogAPI.GetBrands(ctx, []string{opts.Key}, opts.Field)
	if err != nil {
		return nil, err
	}
	if len(items) == 0 {
		return nil, errors.New("Brand was not loaded")
	}
	return &ModelData{
		Model:         search_opts.ModelBrand,
		FilterID:      search_opts.FilterIDBrand,
		Label:         items[0].Name,
		OriginalLabel: items[0].Name,
		Value:         strconv.FormatUint(items[0].IDCatalogBrand, 10),
		ID:            int64(items[0].IDCatalogBrand),
		URLKey:        items[0].URLKey,
	}, nil
}

func getModel(ctx context.Context, opts *ModelOpts) *ModelData {
	if opts == nil {
		return nil
	}

	cacheKey := opts.Model + "_" + opts.Field + ":" + opts.Key
	result := loadModelFromContext(ctx, cacheKey)
	if result != nil {
		return result
	}
	cacheKey = opts.Field + ":" + opts.Key
	return loadModelFromContext(ctx, cacheKey)
}

func AddModel(ctx context.Context, model *ModelData) {
	if model == nil {
		return
	}
	addModelToContext(ctx, model)
}

func GetConcatedModelLabels(models []*ModelData) string {
	modelLabels := make(map[string][]string, len(models))
	for _, model := range models {
		if _, ok := modelLabels[model.Model]; !ok {
			modelLabels[model.Model] = []string{model.Label}
		} else {
			modelLabels[model.Model] = append(modelLabels[model.Model], model.Label)
		}
	}
	result := make([]string, 0, len(modelPriority))
	for _, t := range modelPriority {
		if labels, ok := modelLabels[t]; ok && len(labels) > 0 {
			sort.Strings(labels)
			result = append(result, strings.Join(modelLabels[t], ", "))
		}
	}
	return strings.Join(result, " / ")
}
