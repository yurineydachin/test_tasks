package recommendation_service

import (
	"github.com/stretchr/testify/assert"
	"math/rand"
	"testing"
	"time"
)

// all switches are fully on
func TestRecommendConfigEtcd_CheckProviderProbability_On(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       100,
		recommendSponsoredProducts: 100,
		recommendTaobao:            100,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderSP))
}

// all switches are fully off
func TestRecommendConfigEtcd_CheckProviderProbability_Off(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           false,
		recommendMainPageEnabled:   false,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            0,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderSP))

}

// probability is 0 but recommendations are on
func TestRecommendConfigEtcd_CheckProviderProbability_RecomendOnProb0(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            0,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderSP))
}

// probability is 100 but recommendations are off
func TestRecommendConfigEtcd_CheckProviderProbability_RecomendOffProb100(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           false,
		recommendMainPageEnabled:   false,
		recommendDataScience:       100,
		recommendSponsoredProducts: 100,
		recommendTaobao:            100,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderSP))

}

// only Taobao is on
func TestRecommendConfigEtcd_CheckProviderProbability_TaobaoOn(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            100,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderSP))
}

// only Data Science is on
func TestRecommendConfigEtcd_CheckProviderProbability_DataScienceOn(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       100,
		recommendSponsoredProducts: 0,
		recommendTaobao:            0,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderSP))
}

// only Sponsored Products is on
func TestRecommendConfigEtcd_CheckProviderProbability_SponsoredProductsOn(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 100,
		recommendTaobao:            0,
		randomizer:                 rand.New(rand.NewSource(time.Now().UnixNano())),
	}

	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTB))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBPlacement))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderTBWidget))
	assert.Equal(t, false, cfg.CheckProviderProbability(ProviderDS))
	assert.Equal(t, true, cfg.CheckProviderProbability(ProviderSP))
}

func TestRecommendConfigEtcd_RecommendEnabled(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendDataScience:       100,
		recommendSponsoredProducts: 100,
		recommendTaobao:            100,
	}
	assert.Equal(t, true, cfg.RecommendEnabled())

	cfg = &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            1,
	}
	assert.Equal(t, true, cfg.RecommendEnabled())

	cfg = &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            0,
	}
	assert.Equal(t, false, cfg.RecommendEnabled())

	cfg = &recommendConfigEtcd{
		recommendEnabled:           false,
		recommendDataScience:       100,
		recommendSponsoredProducts: 100,
		recommendTaobao:            100,
	}
	assert.Equal(t, false, cfg.RecommendEnabled())

	cfg = &recommendConfigEtcd{}
	assert.Equal(t, false, cfg.RecommendEnabled())
}

func TestRecommendConfigEtcd_RecommendMainPageEnabled(t *testing.T) {
	cfg := &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       100,
		recommendSponsoredProducts: 100,
		recommendTaobao:            100,
	}
	assert.Equal(t, true, cfg.RecommendMainPageEnabled())

	cfg = &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            1,
	}
	assert.Equal(t, true, cfg.RecommendMainPageEnabled())

	cfg = &recommendConfigEtcd{
		recommendEnabled:           true,
		recommendMainPageEnabled:   true,
		recommendDataScience:       0,
		recommendSponsoredProducts: 0,
		recommendTaobao:            0,
	}
	assert.Equal(t, false, cfg.RecommendMainPageEnabled())

	cfg = &recommendConfigEtcd{
		recommendEnabled:           false,
		recommendMainPageEnabled:   true,
		recommendDataScience:       100,
		recommendSponsoredProducts: 100,
		recommendTaobao:            100,
	}
	assert.Equal(t, false, cfg.RecommendMainPageEnabled())

	cfg = &recommendConfigEtcd{
		recommendMainPageEnabled: true,
	}
	assert.Equal(t, false, cfg.RecommendMainPageEnabled())

	cfg = &recommendConfigEtcd{}
	assert.Equal(t, false, cfg.RecommendMainPageEnabled())
}
