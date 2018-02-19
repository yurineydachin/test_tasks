package recommendation_service

import (
	"math/rand"
	"sync"
	"time"

	"godep.lzd.co/go-dconfig"
)

type IRecommendationsConfig interface {
	CheckProviderProbability(providerName string) bool
	RecommendMainPageEnabled() bool
	RecommendEnabled() bool
	GetRecommendRequestTimeout(providerName string) time.Duration
}

const (
	RecommendDefaultRequestTimeout = 1000
)

type recommendConfigEtcd struct {
	recommendEnabled                         bool
	recommendMainPageEnabled                 bool
	recommendTaobao                          int
	recommendDataScience                     int
	recommendSponsoredProducts               int
	recommendTaobaoRequestTimeout            int
	recommendDataScienceRequestTimeout       int
	recommendSponsoredProductsRequestTimeout int
	randomizer                               *rand.Rand
	mux                                      sync.RWMutex
}

var instance = &recommendConfigEtcd{
	recommendEnabled:                         true,
	recommendMainPageEnabled:                 true,
	recommendTaobao:                          100,
	recommendDataScience:                     100,
	recommendSponsoredProducts:               100,
	recommendTaobaoRequestTimeout:            RecommendDefaultRequestTimeout,
	recommendDataScienceRequestTimeout:       RecommendDefaultRequestTimeout,
	recommendSponsoredProductsRequestTimeout: RecommendDefaultRequestTimeout,
	randomizer: rand.New(rand.NewSource(time.Now().UnixNano())),
}

func init() {
	dconfig.RegisterBool("recommend-enabled", "Enable recommendations", instance.recommendEnabled,
		func(v bool) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendEnabled = v
		})
	dconfig.RegisterBool("recommend-main-page-enabled", "Enable recommendations", instance.recommendMainPageEnabled,
		func(v bool) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendMainPageEnabled = v
		})
	dconfig.RegisterInt("recommend-taobao", "Enable Taobao recommendations by percents", instance.recommendTaobao,
		func(v int) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendTaobao = v
		})
	dconfig.RegisterInt("recommend-data-science", "Enable Data Science recommendations by percents", instance.recommendDataScience,
		func(v int) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendDataScience = v
		})
	dconfig.RegisterInt("recommend-sponsored-products", "Enable Sponsored Products recommendations by percents", instance.recommendSponsoredProducts,
		func(v int) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendSponsoredProducts = v
		})
	dconfig.RegisterInt("recommend-taobao-timeout", "Request timeout for Taobao recommendations (ms)", instance.recommendTaobaoRequestTimeout,
		func(v int) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendTaobaoRequestTimeout = v
		})
	dconfig.RegisterInt("recommend-data-science-timeout", "Request timeout for Data Science recommendations (ms)", instance.recommendDataScienceRequestTimeout,
		func(v int) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendDataScienceRequestTimeout = v
		})
	dconfig.RegisterInt("recommend-sponsored-products-timeout", "Request timeout for Sponsored Products recommendations (ms)", instance.recommendSponsoredProductsRequestTimeout,
		func(v int) {
			instance.mux.Lock()
			defer instance.mux.Unlock()
			instance.recommendSponsoredProductsRequestTimeout = v
		})
}

func GetRecomendationsConfig() IRecommendationsConfig {
	return instance
}

func (conf *recommendConfigEtcd) CheckProviderProbability(providerName string) bool {
	conf.mux.RLock()
	defer conf.mux.RUnlock()
	probability := 100
	switch providerName {
	case ProviderTB, ProviderTBPlacement, ProviderTBWidget:
		probability = conf.recommendTaobao
	case ProviderDS:
		probability = conf.recommendDataScience
	case ProviderSP:
		probability = conf.recommendSponsoredProducts
	}

	v := conf.randomizer.Intn(100)

	switch {
	default:
		return true
	case !conf.recommendEnabled:
		return false
	case probability == 100:
		return true
	case probability == 0:
		return false
	case v >= probability:
		return false
	}
}

func (conf *recommendConfigEtcd) RecommendMainPageEnabled() bool {
	conf.mux.RLock()
	defer conf.mux.RUnlock()
	return conf.RecommendEnabled() && conf.recommendMainPageEnabled
}

func (conf *recommendConfigEtcd) RecommendEnabled() bool {
	conf.mux.RLock()
	defer conf.mux.RUnlock()
	return conf.recommendEnabled && (conf.recommendTaobao != 0 || conf.recommendDataScience != 0 || conf.recommendSponsoredProducts != 0)
}

func (conf *recommendConfigEtcd) GetRecommendRequestTimeout(providerName string) time.Duration {
	conf.mux.RLock()
	defer conf.mux.RUnlock()

	timeout := RecommendDefaultRequestTimeout
	switch providerName {
	case ProviderTB, ProviderTBPlacement, ProviderTBWidget:
		timeout = conf.recommendTaobaoRequestTimeout
	case ProviderDS:
		timeout = conf.recommendDataScienceRequestTimeout
	case ProviderSP:
		timeout = conf.recommendSponsoredProductsRequestTimeout
	}
	return time.Duration(timeout) * time.Millisecond
}
