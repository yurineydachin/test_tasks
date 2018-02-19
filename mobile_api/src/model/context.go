package model_service

import (
	"context"
)

type key int

var (
	keyContext key = 0
)

type LoadedModels struct {
	data map[string]*ModelData
}

func InitContextCache(ctx context.Context) context.Context {
	if ctx == nil {
		ctx = context.Background()
	}
	return context.WithValue(ctx, keyContext, &LoadedModels{data: map[string]*ModelData{}})
}

func getContextCache(ctx context.Context) *LoadedModels {
	if ctx == nil {
		return nil
	}
	val := ctx.Value(keyContext)
	if val == nil {
		return nil
	}
	list, ok := val.(*LoadedModels)
	if !ok {
		return nil
	}
	return list
}

func loadModelFromContext(ctx context.Context, cacheKey string) *ModelData {
	list := getContextCache(ctx)
	if list == nil {
		return nil
	}
	if result, ok := list.data[cacheKey]; ok {
		return result
	}
	return nil
}

func addModelToContext(ctx context.Context, model *ModelData) {
	list := getContextCache(ctx)
	if list == nil {
		return
	}
	if model.URLKey != "" {
		list.data[model.GetCacheKeyURLKey()] = model
		list.data[model.GetCacheKeyURLKeyCommon()] = model
	}
	if model.ID > 0 {
		list.data[model.GetCacheKeyID()] = model
	}
	if model.Label != "" {
		list.data[model.GetCacheKeyName()] = model
	}
}
