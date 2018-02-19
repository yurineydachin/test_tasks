package model_service

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"mobile_search_api/srv/search_opts"
)

func TestInitContextCacheWithEmptyCtx(t *testing.T) {
	assert.Nil(t, getContextCache(nil), "Error parsing taobao")
	assert.Nil(t, getContextCache(context.Background()), "")
}

func TestInitContextCacheNil(t *testing.T) {
	ctx := InitContextCache(nil)
	list := getContextCache(ctx)
	if assert.NotNil(t, list, "") {
		assert.Equal(t, len(list.data), 0, "")
	}
}

func TestInitContextCacheBackground(t *testing.T) {
	ctx := InitContextCache(context.Background())
	list := getContextCache(ctx)
	if assert.NotNil(t, list, "") {
		assert.Equal(t, len(list.data), 0, "")
	}
}

func TestAddModelToContextAfterGetContextCache(t *testing.T) {
	ctx := InitContextCache(context.Background())
	list := getContextCache(ctx)
	model := &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-category1",
		ID:     1,
		Label:  "Name category1",
	}
	addModelToContext(ctx, model)
	if assert.NotNil(t, list, "") {
		assert.Equal(t, len(list.data), 4, "")
		assert.Equal(t, list.data[model.GetCacheKeyURLKey()], model, "")
		assert.Equal(t, list.data[model.GetCacheKeyURLKeyCommon()], model, "")
		assert.Equal(t, list.data[model.GetCacheKeyID()], model, "")
		assert.Equal(t, list.data[model.GetCacheKeyName()], model, "")
	}
}

func TestAddModelToContextBeforeGetContextCache(t *testing.T) {
	ctx := InitContextCache(context.Background())
	model := &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-category1",
		ID:     1,
		Label:  "Name category1",
	}
	addModelToContext(ctx, model)
	list := getContextCache(ctx)
	if assert.NotNil(t, list, "") {
		assert.Equal(t, len(list.data), 4, "")
		assert.Equal(t, list.data[model.GetCacheKeyURLKey()], model, "")
		assert.Equal(t, list.data[model.GetCacheKeyURLKeyCommon()], model, "")
		assert.Equal(t, list.data[model.GetCacheKeyID()], model, "")
		assert.Equal(t, list.data[model.GetCacheKeyName()], model, "")
	}
}

func TestLoadModelFromContext(t *testing.T) {
	ctx := InitContextCache(context.Background())
	addModelToContext(ctx, &ModelData{
		Model:  search_opts.ModelCategory,
		URLKey: "shop-category1",
		ID:     1,
		Label:  "Name category1",
	})
	model := loadModelFromContext(ctx, "category_urlkey:shop-category1")

	if assert.NotNil(t, model, "") {
		assert.Equal(t, model.Model, search_opts.ModelCategory, "")
		assert.Equal(t, model.ID, int64(1), "")
	}
}
