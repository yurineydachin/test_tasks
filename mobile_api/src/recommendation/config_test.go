package recommendation_service

import (
	"testing"

	"github.com/blang/semver"
	"github.com/stretchr/testify/assert"
)

func TestGetProviderByPlacement(t *testing.T) {
	opts := &RecommendationOpts{}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"home_page":      ProviderTB,
			"category_page":  ProviderRR,
			"brand_page":     ProviderDS,
			"none_page":      ProviderNone,
			"sponsored_page": ProviderSP,
		},
	}

	assert.Equal(t, config.GetProviderByPlacement(opts, "123456"), ProviderTBWidget, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderTBPlacement, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "error_page"), ProviderDefault, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "category_page"), ProviderRR, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "brand_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "none_page"), ProviderNone, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "sponsored_page"), ProviderSP, "")
}

func TestGetProviderByPlacementWithPreferred(t *testing.T) {
	opts := &RecommendationOpts{
		PreferredProvider: ProviderRR,
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"home_page": ProviderTB,
		},
	}

	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderRR, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "item_page"), ProviderRR, "")
}

func TestGetProviderByPlacementWithUnknownPreferred(t *testing.T) {
	opts := &RecommendationOpts{
		PreferredProvider: "blabla",
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"home_page": ProviderTB,
		},
	}

	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderTBPlacement, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "item_page"), ProviderDefault, "")
}

func TestGetProviderByPlacementPDP(t *testing.T) {
	opts := &RecommendationOpts{
		SKU:        "OE702ELAA8BD2SSGAMZ",
		Platform:   "android",
		AppVersion: semver.Version{Major: 5, Minor: 19},
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"home_page":      ProviderTB,
			"category_page":  ProviderRR,
			"brand_page":     ProviderDS,
			"none_page":      ProviderNone,
			"sponsored_page": ProviderSP,
		},
	}
	opts.IsTaobaoSKU = false
	assert.Equal(t, config.GetProviderByPlacement(opts, "123456"), ProviderTBWidget, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "error_page"), ProviderDefault, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "category_page"), ProviderRR, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "brand_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "none_page"), ProviderNone, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "sponsored_page"), ProviderSP, "")

	opts.IsTaobaoSKU = true
	assert.Equal(t, config.GetProviderByPlacement(opts, "123456"), ProviderTBWidget, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderTBPlacement, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "error_page"), ProviderDefault, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "category_page"), ProviderRR, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "brand_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "none_page"), ProviderNone, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "sponsored_page"), ProviderTBPlacement, "")

	opts = &RecommendationOpts{
		SKU:        "OE702ELAA8BD2SSGAMZ",
		Platform:   "ios",
		AppVersion: semver.Version{Major: 5, Minor: 19},
	}
	opts.IsTaobaoSKU = false
	assert.Equal(t, config.GetProviderByPlacement(opts, "123456"), ProviderTBWidget, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "error_page"), ProviderDefault, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "category_page"), ProviderRR, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "brand_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "none_page"), ProviderNone, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "sponsored_page"), ProviderDefault, "")

	opts.IsTaobaoSKU = true
	assert.Equal(t, config.GetProviderByPlacement(opts, "123456"), ProviderTBWidget, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "home_page"), ProviderTBPlacement, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "error_page"), ProviderDefault, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "category_page"), ProviderRR, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "brand_page"), ProviderDS, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "none_page"), ProviderNone, "")
	assert.Equal(t, config.GetProviderByPlacement(opts, "sponsored_page"), ProviderTBPlacement, "")
}
