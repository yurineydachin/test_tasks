package recommendation_service

import (
	"context"
	"strconv"

	"github.com/sergei-svistunov/gorpc/transport/cache"
	d "mobile_search_api/api/ext_services/lazada_api/model/datastruct"
	"mobile_search_api/srv/version_rules"
)

var allProviders = map[string]bool{
	ProviderRR:   true,
	ProviderDS:   true,
	ProviderSP:   true,
	ProviderTB:   true,
	ProviderNone: true,
}

type IConfigAPI interface {
	LoadConfig(ctx context.Context, moduleName string) (d.ConfigRow, error)
}

type RecommendationConfig struct {
	Taobao            TaobaoEnv
	DataScience       DataScienceEnv
	SponsoredProducts SponsoredProductsEnv
	Placements        map[string]string
}

type DataScienceEnv struct {
	APILink     string
	APIClientID string
	APIKey      string
}

type SponsoredProductsEnv struct {
	APILink     string
	APIClientID string
	APIKey      string
}

type TaobaoEnv struct {
	AppIDHome      string
	AppIDPdp       string
	Endpoint       string
	EndpointBackup string
	ClientID       string
}

func (service *RecommendationService) GetConfig(ctx context.Context) (*RecommendationConfig, error) {
	settings, err := service.configAPI.LoadConfig(cache.NewContextWithTransportCache(ctx), "recommendation")
	if err != nil {
		return nil, err
	}
	return &RecommendationConfig{
		DataScience: DataScienceEnv{
			APILink:     settings.String("in_house_rr_api_link", ""),
			APIClientID: settings.String("in_house_rr_api_client_id", ""),
			APIKey:      settings.String("in_house_rr_api_key", ""),
		},
		SponsoredProducts: SponsoredProductsEnv{
			APILink:     settings.String("sponsored_product_api_link", ""),
			APIClientID: settings.String("sponsored_product_api_client_id", ""),
			APIKey:      settings.String("sponsored_product_api_key", ""),
		},
		Taobao: TaobaoEnv{
			AppIDHome:      settings.String("taobao_appid_home", ""),
			AppIDPdp:       settings.String("taobao_appid_pdp", ""),
			Endpoint:       settings.String("taobao_api_endpoint", ""),
			EndpointBackup: settings.String("taobao_backup_api_endpoint", ""),
			ClientID:       settings.String("taobao_client_id", ""),
		},
		Placements: map[string]string{
			"item_page.right":                   settings.String("in_house_rr_enable_item_page_right", ProviderDefault),
			"item_page.history":                 settings.String("in_house_rr_enable_item_page_history", ProviderDefault),
			"item_page.bottom":                  settings.String("in_house_rr_enable_item_page_bottom", ProviderDefault),
			"item_page.M1_top":                  settings.String("in_house_rr_enable_item_page_m1_top", ProviderDefault),
			"item_page.M1_top2":                 settings.String("in_house_rr_enable_item_page_m1_top2", ProviderDefault),
			"home_page.M1_top":                  settings.String("in_house_rr_enable_home_page_m1_top", ProviderDefault),
			"home_page.M1_bottom":               settings.String("in_house_rr_enable_home_page_m1_bottom", ProviderDefault),
			"home_page.M1_bottom2":              settings.String("in_house_rr_enable_home_page_m1_bottom2", ProviderDefault),
			"add_to_cart_page.mid":              settings.String("in_house_rr_enable_add_to_cart_page_mid", ProviderDefault),
			"add_to_cart_page.low":              settings.String("in_house_rr_enable_add_to_cart_page_low", ProviderDefault),
			"add_to_cart_page.high":             settings.String("in_house_rr_enable_add_to_cart_page_high", ProviderDefault),
			"add_to_cart_page.threshold":        settings.String("in_house_rr_enable_add_to_cart_page_threshold", ProviderDefault),
			"wish_list_page.bottom":             settings.String("in_house_rr_enable_wish_list_page_bottom", ProviderDefault),
			"cart_page.empty":                   settings.String("in_house_rr_enable_cart_page_empty", ProviderDefault),
			"item_page.M1_MoreLikeThis":         settings.String("in_house_rr_enable_item_page_m1_morelikethis", ProviderDefault),
			"error_page.bottom":                 settings.String("in_house_rr_enable_error_page_bottom", ProviderDefault),
			"category_page.bottom":              settings.String("in_house_rr_enable_category_page_bottom", ProviderDefault),
			"category_page.M1_bottom":           settings.String("in_house_rr_enable_category_page_m1_bottom", ProviderDefault),
			"category_page.M1_bottom2":          settings.String("in_house_rr_enable_category_page_m1_bottom2", ProviderDefault),
			"purchase_complete_page.cross_sell": settings.String("in_house_rr_enable_purchase_complete_page_cross_sell", ProviderDefault),
			"brand_page.bottom":                 settings.String("in_house_rr_enable_brand_page_bottom", ProviderDefault),
			"error_page.M1_SearchTop":           settings.String("in_house_rr_enable_error_page_m1_searchtop", ProviderDefault),
			"error_page.M1_SearchBottom":        settings.String("in_house_rr_enable_error_page_m1_searchbottom", ProviderDefault),
			"item_page.M1_ViewAll":              settings.String("in_house_rr_enable_item_page_m1_viewall", ProviderDefault),
			"cart_page.M1_bottom":               settings.String("in_house_rr_enable_cart_page_m1_bottom", ProviderDefault),

			"item_page.M2_top":             settings.String("in_house_rr_enable_item_page_m2_top", ProviderDefault),
			"item_page.M2_top2":            settings.String("in_house_rr_enable_item_page_m2_top2", ProviderDefault),
			"brand_page.M2_NewArrivals":    settings.String("provider_brand_page_m2_newarrivals", ProviderDefault),
			"brand_page.M2_RecentView":     settings.String("provider_brand_page_m2_recentview", ProviderDefault),
			"brand_page.M2_RecoForYou":     settings.String("provider_brand_page_m2_recoforyou", ProviderDefault),
			"brand_page.M2_TopSeller":      settings.String("provider_brand_page_m2_topseller", ProviderDefault),
			"cart_page.M2_bottom":          settings.String("provider_cart_page_m2_bottom", ProviderDefault),
			"cart_page.M2_bottom2":         settings.String("provider_cart_page_m2_bottom2", ProviderDefault),
			"category_page.M2_bottom":      settings.String("provider_category_page_m2_bottom", ProviderDefault),
			"category_page.M2_bottom2":     settings.String("provider_category_page_m2_bottom2", ProviderDefault),
			"category_page.M2_NewArrivals": settings.String("provider_category_page_m2_newarrivals", ProviderDefault),
			"category_page.M2_RecentView":  settings.String("provider_category_page_m2_recentview", ProviderDefault),
			"category_page.M2_RecoForYou":  settings.String("provider_category_page_m2_recoforyou", ProviderDefault),
			"category_page.M2_TopSeller":   settings.String("provider_category_page_m2_topseller", ProviderDefault),
			"error_page.M2_Searchnotfound": settings.String("provider_error_page_m2_searchnotfound", ProviderDefault),
			"home_page.M2_NewArrivals":     settings.String("provider_home_page_m2_newarrivals", ProviderDefault),
			"home_page.M2_RecentView":      settings.String("provider_home_page_m2_recentview", ProviderDefault),
			"home_page.M2_RecoForYou":      settings.String("provider_home_page_m2_recoforyou", ProviderDefault),
			"home_page.M2_TopSeller":       settings.String("provider_home_page_m2_topseller", ProviderDefault),
			"wish_list_page.M2_Bottom":     settings.String("provider_wish_list_page_m2_bottom", ProviderDefault),
			"wish_list_page.M2_Bottom2":    settings.String("provider_wish_list_page_m2_bottom2", ProviderDefault),

			"item_page.M3_top":             settings.String("in_house_rr_enable_item_page_m3_top", ProviderDefault),
			"item_page.M3_top2":            settings.String("in_house_rr_enable_item_page_m3_top2", ProviderDefault),
			"brand_page.M3_NewArrivals":    settings.String("provider_brand_page_m3_newarrivals", ProviderDefault),
			"brand_page.M3_RecentView":     settings.String("provider_brand_page_m3_recentview", ProviderDefault),
			"brand_page.M3_RecoForYou":     settings.String("provider_brand_page_m3_recoforyou", ProviderDefault),
			"brand_page.M3_TopSeller":      settings.String("provider_brand_page_m3_topseller", ProviderDefault),
			"cart_page.M3_bottom":          settings.String("provider_cart_page_m3_bottom", ProviderDefault),
			"cart_page.M3_Bottom2":         settings.String("provider_cart_page_m3_bottom2", ProviderDefault),
			"category_page.M3_bottom":      settings.String("provider_category_page_m3_bottom", ProviderDefault),
			"category_page.M3_bottom2":     settings.String("provider_category_page_m3_bottom2", ProviderDefault),
			"category_page.M3_NewArrivals": settings.String("provider_category_page_m3_newarrivals", ProviderDefault),
			"category_page.M3_RecentView":  settings.String("provider_category_page_m3_recentview", ProviderDefault),
			"category_page.M3_RecoForYou":  settings.String("provider_category_page_m3_recoforyou", ProviderDefault),
			"category_page.M3_TopSeller":   settings.String("provider_category_page_m3_topseller", ProviderDefault),
			"error_page.M3_Searchnotfound": settings.String("provider_error_page_m3_searchnotfound", ProviderDefault),
			"home_page.M3_NewArrivals":     settings.String("provider_home_page_m3_newarrivals", ProviderDefault),
			"home_page.M3_RecentView":      settings.String("provider_home_page_m3_recentview", ProviderDefault),
			"home_page.M3_RecoForYou":      settings.String("provider_home_page_m3_recoforyou", ProviderDefault),
			"home_page.M3_TopSeller":       settings.String("provider_home_page_m3_topseller", ProviderDefault),
			"wish_list_page.M3_Bottom":     settings.String("provider_wish_list_page_m3_bottom", ProviderDefault),
			"wish_list_page.M3_Bottom2":    settings.String("provider_wish_list_page_m3_bottom2", ProviderDefault),
		},
	}, nil
}

func (config *RecommendationConfig) GetProviderByPlacement(opts *RecommendationOpts, placement string) string {
	provider := ProviderDefault
	if _, err := strconv.ParseUint(placement, 10, 64); err == nil {
		return ProviderTBWidget
	}
	if p, find := config.Placements[placement]; find {
		provider = p
	}

	if ok, exists := allProviders[opts.PreferredProvider]; opts.PreferredProvider != "" && exists && ok {
		provider = opts.PreferredProvider
	} else if opts.SKU != "" {
		if opts.IsTaobaoSKU {
			if provider == ProviderSP { // GO-12805 show tao reco for tao product
				provider = ProviderTB
			}
		} else if provider == ProviderTB { // GO-12459. Don't use Taobao provider for non-taobao SKUs
			provider = ProviderDefault
		} else if provider == ProviderSP {
			if !rules.CheckRule("version_sponsored_products", opts.Platform, opts.AppVersion, "", "") {
				provider = ProviderDefault
			}
		}
	}

	if ok, exists := allProviders[provider]; !exists || !ok {
		provider = ProviderDefault
	}
	if provider == ProviderTB {
		return ProviderTBPlacement
	}
	return provider
}
