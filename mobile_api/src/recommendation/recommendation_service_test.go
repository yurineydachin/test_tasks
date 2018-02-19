package recommendation_service

import (
	"context"
	"fmt"
	"sort"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"godep.lzd.co/catalog_api_client_go/transfer"
	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/product"
	"mobile_search_api/srv/search_opts"
)

type tmpContentAPI struct{}

var _ IRecommendationsConfig = (*recommendationConfigMock)(nil)

type recommendationConfigMock struct {
}

func (m *recommendationConfigMock) CheckProviderProbability(providerName string) bool {
	return true
}

func (m *recommendationConfigMock) RecommendMainPageEnabled() bool {
	return true
}

func (m *recommendationConfigMock) RecommendEnabled() bool {
	return true
}

func (m *recommendationConfigMock) GetRecommendRequestTimeout(providerName string) time.Duration {
	return time.Duration(RecommendDefaultRequestTimeout) * time.Second
}

func (c *tmpContentAPI) GetSingleProducts(ctx context.Context, skus []string, lang string) ([]search.Product, error) {

	RequestedSKUExample := "SO406ELAAA8N6PSGAMZ"
	products := []search.Product{
		{
			SKU:            "GU595FAAA9L94TSGAMZ",
			SupplierNameEN: "The Handphone Shop",
		},
		{
			SKU:            "SO406ELAAA8N6PSGAMX",
			RequestedSKU:   &RequestedSKUExample,
			SupplierNameEN: "SRC International",
		},
		{
			SKU:            "SO406ELAA5HNLFSGAMZ",
			SupplierNameEN: "PT.Photo",
		},
	}
	return products, nil
}

func TestFillProduct(t *testing.T) {

	recommendationService := &RecommendationService{
		productService: product_service.NewProductService(&tmpContentAPI{}),
		aliceBaseURL:   "http://www.lazada.sg",
		recomendConf:   &recommendationConfigMock{},
	}

	result := []RecommendationItem{
		{
			Products: []product_service.Product{
				{SKU: "GU595FAAA9L94TSGAMZ", CtURL: "ct_url_1", CID: "cid_1"},
				{SKU: "SO406ELAAA8N6PSGAMZ", CtURL: "ct_url_2", DID: "did_2"},
				{SKU: "SO406ELAA5HNLFSGAMZ", CtURL: "ct_url_3", SimpleSKU: "simple_sku_3", PDPSKU: "pdp_sku_3"},
			},
		},
	}

	var err error
	result, err = recommendationService.fillProducts(context.Background(), result, &RecommendationOpts{Lang: "en"})

	assert.Nil(t, err, "")
	if assert.Equal(t, len(result), 1) {
		if assert.NotNil(t, result[0].Products, "") && assert.Equal(t, len(result[0].Products), 3) {

			assert.Equal(t, result[0].Products[0].SellerName, "The Handphone Shop")
			assert.Equal(t, result[0].Products[0].CtURL, "http://www.lazada.sg/ct_url_1")
			assert.Equal(t, result[0].Products[0].CID, "cid_1")
			assert.Equal(t, result[0].Products[0].DID, "")
			assert.Equal(t, result[0].Products[0].SimpleSKU, "")
			assert.Equal(t, result[0].Products[0].PDPSKU, "")

			assert.Equal(t, result[0].Products[1].SellerName, "SRC International")
			assert.Equal(t, result[0].Products[1].CtURL, "http://www.lazada.sg/ct_url_2")
			assert.Equal(t, result[0].Products[1].CID, "")
			assert.Equal(t, result[0].Products[1].DID, "did_2")
			assert.Equal(t, result[0].Products[1].SimpleSKU, "")
			assert.Equal(t, result[0].Products[1].PDPSKU, "")

			assert.Equal(t, result[0].Products[2].SellerName, "PT.Photo")
			assert.Equal(t, result[0].Products[2].CtURL, "http://www.lazada.sg/ct_url_3")
			assert.Equal(t, result[0].Products[2].CID, "")
			assert.Equal(t, result[0].Products[2].DID, "")
			assert.Equal(t, result[0].Products[2].SimpleSKU, "simple_sku_3")
			assert.Equal(t, result[0].Products[2].PDPSKU, "pdp_sku_3")
		}
	}
}

type tmpProviderTBRecommendationAPI struct{}

func (r *tmpProviderTBRecommendationAPI) GetName() string {
	return ProviderTB
}

func (r *tmpProviderTBRecommendationAPI) SetRequestTimeout(timeout time.Duration) {
	return
}

func (r *tmpProviderTBRecommendationAPI) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	result := make([]RecommendationItem, len(opts.Placements))
	for i := range opts.Placements {
		result[i] = RecommendationItem{
			Placement: opts.Placements[i],
			Products: []product_service.Product{
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], 2*i)},
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], 2*i+1)},
			},
		}
	}
	return result, nil
}

type tmpProviderDSRecommendationAPI struct{}

func (r *tmpProviderDSRecommendationAPI) GetName() string {
	return ProviderDS
}

func (r *tmpProviderDSRecommendationAPI) SetRequestTimeout(timeout time.Duration) {
	return
}

func (r *tmpProviderDSRecommendationAPI) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	result := make([]RecommendationItem, len(opts.Placements))
	for i := range opts.Placements {
		result[i] = RecommendationItem{
			Placement: opts.Placements[i],
			Products: []product_service.Product{
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], i)},
			},
		}
	}
	return result, nil
}

func TestSortStrings(t *testing.T) {
	p := []string{"100000", "home_page", "error_page", "category_page", "brand_page", "200000"}
	sort.Strings(p)

	assert.Equal(t, p[0], "100000")
	assert.Equal(t, p[1], "200000")
	assert.Equal(t, p[2], "brand_page")
	assert.Equal(t, p[3], "category_page")
	assert.Equal(t, p[4], "error_page")
	assert.Equal(t, p[5], "home_page")
}

func TestGetProviderPlacements(t *testing.T) {

	service := &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderTBWidget:    &tmpProviderTBRecommendationAPI{},
			ProviderTBPlacement: &tmpProviderTBRecommendationAPI{},
			ProviderDS:          &tmpProviderDSRecommendationAPI{},
		},
		recomendConf: &recommendationConfigMock{},
	}

	opts := &RecommendationOpts{
		Placements: []string{"100000", "home_page", "error_page", "category_page", "brand_page", "200000", "home_page", "error_page", "category_page"},
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"error_page":    ProviderDS,
			"brand_page":    ProviderTB,
			"category_page": ProviderTB,
		},
	}

	providerPlacements := service.getProviderPlacements(opts, config)

	if assert.NotNil(t, providerPlacements, "") {
		assert.Equal(t, providerPlacements[ProviderDS], []string{"error_page", "home_page"})
		assert.Equal(t, providerPlacements[ProviderTBWidget], []string{"100000", "200000"})
		assert.Equal(t, providerPlacements[ProviderTBPlacement], []string{"brand_page", "category_page"})
	}
}

func TestGetRecommendationItems(t *testing.T) {

	service := &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderTBWidget:    &tmpProviderTBRecommendationAPI{},
			ProviderTBPlacement: &tmpProviderTBRecommendationAPI{},
			ProviderDS:          &tmpProviderDSRecommendationAPI{},
		},
		productService: product_service.NewProductService(&tmpContentAPI{}),
		recomendConf:   &recommendationConfigMock{},
	}

	opts := &RecommendationOpts{
		Placements: []string{"100000", "home_page", "error_page", "category_page", "brand_page", "200000"},
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"error_page":    ProviderDS,
			"brand_page":    ProviderTB,
			"category_page": ProviderTB,
		},
	}

	results := service.getRecommendationItems(context.Background(), opts, config)

	if assert.Equal(t, len(results), 6) {
		assert.Equal(t, results[0].Source, ProviderDS)
		assert.Equal(t, results[0].Placement, "error_page")
		assert.Equal(t, len(results[0].Products), 1)

		assert.Equal(t, results[1].Source, ProviderDS)
		assert.Equal(t, results[1].Placement, "home_page")
		assert.Equal(t, len(results[1].Products), 1)

		assert.Equal(t, results[2].Source, ProviderTB)
		assert.Equal(t, results[2].Placement, "100000")
		assert.Equal(t, len(results[2].Products), 2)

		assert.Equal(t, results[3].Source, ProviderTB)
		assert.Equal(t, results[3].Placement, "200000")
		assert.Equal(t, len(results[3].Products), 2)

		assert.Equal(t, results[4].Source, ProviderTB)
		assert.Equal(t, results[4].Placement, "brand_page")
		assert.Equal(t, len(results[4].Products), 2)

		assert.Equal(t, results[5].Source, ProviderTB)
		assert.Equal(t, results[5].Placement, "category_page")
		assert.Equal(t, len(results[5].Products), 2)
	}
}

func TestSortBySourceAndPlacement(t *testing.T) {

	results := []RecommendationItem{
		{Source: ProviderTB, Placement: "item_page"},
		{Source: ProviderTB, Placement: "20000"},
		{Source: ProviderDS, Placement: "brand_page"},
		{Source: ProviderTB, Placement: "category_page"},
		{Source: ProviderDS, Placement: "home_page"},
		{Source: ProviderTB, Placement: "10000"},
		{Source: ProviderTB, Placement: "error_page"},
	}
	sort.Sort(SortBySourceAndPlacement(results))

	assert.Equal(t, results[0].Source, ProviderDS)
	assert.Equal(t, results[0].Placement, "brand_page")

	assert.Equal(t, results[1].Source, ProviderDS)
	assert.Equal(t, results[1].Placement, "home_page")

	assert.Equal(t, results[2].Source, ProviderTB)
	assert.Equal(t, results[2].Placement, "10000")

	assert.Equal(t, results[3].Source, ProviderTB)
	assert.Equal(t, results[3].Placement, "20000")

	assert.Equal(t, results[4].Source, ProviderTB)
	assert.Equal(t, results[4].Placement, "category_page")

	assert.Equal(t, results[5].Source, ProviderTB)
	assert.Equal(t, results[5].Placement, "error_page")

	assert.Equal(t, results[6].Source, ProviderTB)
	assert.Equal(t, results[6].Placement, "item_page")
}

type tmpProviderEmptyAPI struct{}

var emptyP = "empty"

func (r *tmpProviderEmptyAPI) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	return []RecommendationItem{}, nil
}
func (r *tmpProviderEmptyAPI) GetName() string {
	return emptyP
}

func (r *tmpProviderEmptyAPI) SetRequestTimeout(timeout time.Duration) {
	return
}

func TestEmptyGetRecommendationItems(t *testing.T) {
	service := &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderTBWidget:    &tmpProviderEmptyAPI{},
			ProviderTBPlacement: &tmpProviderEmptyAPI{},
			ProviderDS:          &tmpProviderEmptyAPI{},
		},
		productService: product_service.NewProductService(&tmpContentAPI{}),
		recomendConf:   &recommendationConfigMock{},
	}

	opts := &RecommendationOpts{
		Placements: []string{"100000", "home_page", "error_page", "category_page", "brand_page", "200000"},
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"error_page":    ProviderDS,
			"brand_page":    ProviderTB,
			"category_page": ProviderTB,
		},
	}

	results := service.getRecommendationItems(context.Background(), opts, config)
	if assert.Equal(t, len(results), 6) {
		assert.Equal(t, results[0].Source, emptyP)
		assert.Equal(t, results[0].Placement, "100000")
		assert.Equal(t, len(results[0].Products), 0)

		assert.Equal(t, results[1].Source, emptyP)
		assert.Equal(t, results[1].Placement, "200000")
		assert.Equal(t, len(results[1].Products), 0)

		assert.Equal(t, results[2].Source, emptyP)
		assert.Equal(t, results[2].Placement, "brand_page")
		assert.Equal(t, len(results[2].Products), 0)

		assert.Equal(t, results[3].Source, emptyP)
		assert.Equal(t, results[3].Placement, "category_page")
		assert.Equal(t, len(results[3].Products), 0)

		assert.Equal(t, results[4].Source, emptyP)
		assert.Equal(t, results[4].Placement, "error_page")
		assert.Equal(t, len(results[4].Products), 0)

		assert.Equal(t, results[5].Source, emptyP)
		assert.Equal(t, results[5].Placement, "home_page")
		assert.Equal(t, len(results[5].Products), 0)
	}
}

func TestGetTitlePartByLang(t *testing.T) {
	assert.Equal(t, getTitlePartByLang("name1#name2", ""), "name1#name2")
	assert.Equal(t, getTitlePartByLang("name1#name2", "en"), "name2")
	assert.Equal(t, getTitlePartByLang("name1#name2", "vi"), "name1")
	assert.Equal(t, getTitlePartByLang("name1#name2", "ms"), "name1")
	assert.Equal(t, getTitlePartByLang("#name2", "ms"), "name2")
	assert.Equal(t, getTitlePartByLang("name1#", "en"), "name1")
	assert.Equal(t, getTitlePartByLang("#", "en"), "#")
}

type tmpProviderDSRecommendationAPIForBackup struct{}

func (r *tmpProviderDSRecommendationAPIForBackup) GetName() string {
	return ProviderDS
}

func (r *tmpProviderDSRecommendationAPIForBackup) SetRequestTimeout(timeout time.Duration) {
	return
}

func (r *tmpProviderDSRecommendationAPIForBackup) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	result := make([]RecommendationItem, len(opts.Placements))
	for i := range opts.Placements {
		result[i] = RecommendationItem{
			Placement: opts.Placements[i],
			Products: []product_service.Product{
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], 0)},
			},
		}
	}
	return result, nil
}

type tmpProviderSPRecommendationAPIForBackup struct{}

func (r *tmpProviderSPRecommendationAPIForBackup) GetName() string {
	return ProviderSP
}

func (r *tmpProviderSPRecommendationAPIForBackup) SetRequestTimeout(timeout time.Duration) {
	return
}

func (r *tmpProviderSPRecommendationAPIForBackup) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	result := make([]RecommendationItem, len(opts.Placements))
	for i := range opts.Placements {
		result[i] = RecommendationItem{
			Placement: opts.Placements[i],
			Products: []product_service.Product{
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], 1)},
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], 2)},
				{SKU: fmt.Sprintf("%s_%d", opts.Placements[i], 3)},
			},
		}
	}
	return result, nil
}

type tmpContentAPIForGetRecommendationItems struct{}

func (c *tmpContentAPIForGetRecommendationItems) GetSingleProducts(ctx context.Context, skus []string, lang string) ([]search.Product, error) {
	simplesWithOneInStock := map[string]search.Simple{"simple": {Meta: search.SimpleMeta{Quantity: 1}}}
	return []search.Product{
		{SKU: "test_backup_page_0", Simples: simplesWithOneInStock},
		{SKU: "test_backup_page_1", Simples: simplesWithOneInStock},
		{SKU: "test_backup_page_2", Simples: simplesWithOneInStock},
		{SKU: "test_stock_page_0", Simples: simplesWithOneInStock},
		{SKU: "test_stock_page_1"},
		{SKU: "test_stock_page_2"},
		{SKU: "test_stock_page_3"},
	}, nil
}

func TestGetRecommendationItemsBackupProviders(t *testing.T) {
	var service *RecommendationService
	var results []RecommendationItem
	opts := &RecommendationOpts{
		Placements: []string{"test_backup_page"},
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"test_backup_page": ProviderSP,
		},
	}
	// All products in test_backup_page available in stock

	// With empty ProviderDS & ProviderSP providers
	service = &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderDS: &tmpProviderEmptyAPI{},
			ProviderSP: &tmpProviderEmptyAPI{},
		},
		productService: product_service.NewProductService(&tmpContentAPIForGetRecommendationItems{}),
		recomendConf:   &recommendationConfigMock{},
	}
	results = service.getRecommendationItems(context.Background(), opts, config)
	if assert.Equal(t, len(results), 1) {
		assert.Equal(t, results[0].Source, emptyP)
		assert.Equal(t, results[0].Placement, "test_backup_page")
		assert.Equal(t, len(results[0].Products), 0)
	}

	// With not empty ProviderSP & empty ProviderDS providers
	service = &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderDS: &tmpProviderEmptyAPI{},
			ProviderSP: &tmpProviderSPRecommendationAPIForBackup{},
		},
		productService: product_service.NewProductService(&tmpContentAPIForGetRecommendationItems{}),
		recomendConf:   &recommendationConfigMock{},
	}
	results = service.getRecommendationItems(context.Background(), opts, config)
	if assert.Equal(t, len(results), 1) {
		assert.Equal(t, results[0].Source, ProviderSP)
		assert.Equal(t, results[0].Placement, "test_backup_page")
		assert.Equal(t, len(results[0].Products), 3)
	}

	// With empty ProviderSP & not empty ProviderDS providers
	service = &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderDS: &tmpProviderDSRecommendationAPIForBackup{},
			ProviderSP: &tmpProviderEmptyAPI{},
		},
		productService: product_service.NewProductService(&tmpContentAPIForGetRecommendationItems{}),
		recomendConf:   &recommendationConfigMock{},
	}
	results = service.getRecommendationItems(context.Background(), opts, config)
	if assert.Equal(t, len(results), 1) {
		assert.Equal(t, results[0].Source, ProviderDS)
		assert.Equal(t, results[0].Placement, "test_backup_page")
		assert.Equal(t, len(results[0].Products), 1)
	}

	// With not empty ProviderSP & ProviderDS providers
	service = &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderDS: &tmpProviderDSRecommendationAPIForBackup{},
			ProviderSP: &tmpProviderSPRecommendationAPIForBackup{},
		},
		productService: product_service.NewProductService(&tmpContentAPIForGetRecommendationItems{}),
		recomendConf:   &recommendationConfigMock{},
	}
	results = service.getRecommendationItems(context.Background(), opts, config)
	if assert.Equal(t, len(results), 1) {
		assert.Equal(t, results[0].Source, ProviderSP)
		assert.Equal(t, results[0].Placement, "test_backup_page")
		assert.Equal(t, len(results[0].Products), 3)
	}
}

func TestGetRecommendationItemsBackupProvidersWithStock(t *testing.T) {
	var service *RecommendationService
	var results []RecommendationItem
	opts := &RecommendationOpts{
		Placements: []string{"test_stock_page"},
	}
	config := &RecommendationConfig{
		Placements: map[string]string{
			"test_stock_page": ProviderSP,
		},
	}

	// With not empty ProviderSP & ProviderDS providers, but all ProviderSP products does not in stock
	service = &RecommendationService{
		providers: map[string]IRecommendationAPI{
			ProviderDS: &tmpProviderDSRecommendationAPIForBackup{},
			ProviderSP: &tmpProviderSPRecommendationAPIForBackup{},
		},
		productService: product_service.NewProductService(&tmpContentAPIForGetRecommendationItems{}),
		recomendConf:   &recommendationConfigMock{},
	}
	results = service.getRecommendationItems(context.Background(), opts, config)
	if assert.Equal(t, len(results), 1) {
		assert.Equal(t, results[0].Source, ProviderDS)
		assert.Equal(t, results[0].Placement, "test_stock_page")
		assert.Equal(t, len(results[0].Products), 1)
	}
}

func TestGetRegionalKeysFromCategories(t *testing.T) {
	regionKey00001 := "00001"
	regionKey00002 := "00002"
	regionKey00003 := "00003"
	regionKey00004 := "00004"
	items := []transfer.CategorySearchV2ResponseItem{
		{
			IDCatalogCategory: 3,
			RegionalKey:       &regionKey00003,
		},
		{
			IDCatalogCategory: 1,
			RegionalKey:       &regionKey00001,
		},
		{
			IDCatalogCategory: 2,
			RegionalKey:       &regionKey00002,
		},
		{
			IDCatalogCategory: 5,
		},
		{
			IDCatalogCategory: 4,
			RegionalKey:       &regionKey00004,
		},
	}
	var regions []string
	regions = GetRegionalKeysFromCategories(items, []string{})
	assert.Equal(t, len(regions), 0)

	regions = GetRegionalKeysFromCategories([]transfer.CategorySearchV2ResponseItem{}, []string{})
	assert.Equal(t, len(regions), 0)

	regions = GetRegionalKeysFromCategories([]transfer.CategorySearchV2ResponseItem{}, []string{"3", "2", "4", "1"})
	assert.Equal(t, len(regions), 0)

	regions = GetRegionalKeysFromCategories(items, []string{"3", "3"})
	if assert.Equal(t, len(regions), 2) {
		assert.Equal(t, regions[0], regionKey00003)
		assert.Equal(t, regions[1], regionKey00003)
	}

	regions = GetRegionalKeysFromCategories(items, []string{"3", "2", "4", "1"})
	if assert.Equal(t, len(regions), 4) {
		assert.Equal(t, regions[0], regionKey00003)
		assert.Equal(t, regions[1], regionKey00002)
		assert.Equal(t, regions[2], regionKey00004)
		assert.Equal(t, regions[3], regionKey00001)
	}

	regions = GetRegionalKeysFromCategories(items, []string{"23", "14"})
	assert.Equal(t, len(regions), 0)

	regions = GetRegionalKeysFromCategories(items, []string{"3", "44", "3"})
	if assert.Equal(t, len(regions), 2) {
		assert.Equal(t, regions[0], regionKey00003)
		assert.Equal(t, regions[1], regionKey00003)
	}
}

func TestRemoveDuplicatesInSlice(t *testing.T) {
	var regions []string
	regions = RemoveDuplicatesInSlice([]string{})
	assert.Equal(t, len(regions), 0)

	regions = RemoveDuplicatesInSlice([]string{"3", "2", "4", "1"})
	if assert.Equal(t, len(regions), 4) {
		assert.Equal(t, regions[0], "3")
		assert.Equal(t, regions[1], "2")
		assert.Equal(t, regions[2], "4")
		assert.Equal(t, regions[3], "1")
	}

	regions = RemoveDuplicatesInSlice([]string{"3", "2", "3", "1"})
	if assert.Equal(t, len(regions), 3) {
		assert.Equal(t, regions[0], "3")
		assert.Equal(t, regions[1], "2")
		assert.Equal(t, regions[2], "1")
	}

	regions = RemoveDuplicatesInSlice([]string{"3", "3", "3", "3"})
	if assert.Equal(t, len(regions), 1) {
		assert.Equal(t, regions[0], "3")
	}

}

func TestGetAliceBaseURLEmpty(t *testing.T) {
	// Empty Alice Base URL
	service := &RecommendationService{}
	URL := service.getAliceBaseURL(&RecommendationOpts{})
	assert.Equal(t, URL, "")
	URL = service.getAliceBaseURL(&RecommendationOpts{Platform: search_opts.PlatformPWA})
	assert.Equal(t, URL, "")

	// Correct Alice Base URL with http
	service = &RecommendationService{aliceBaseURL: "http://www.lazada.sg"}
	URL = service.getAliceBaseURL(&RecommendationOpts{})
	assert.Equal(t, URL, "http://www.lazada.sg")
	URL = service.getAliceBaseURL(&RecommendationOpts{Platform: search_opts.PlatformPWA})
	assert.Equal(t, URL, "https://www.lazada.sg")

	// Correct Alice Base URL with https
	service = &RecommendationService{aliceBaseURL: "https://www.lazada.sg"}
	URL = service.getAliceBaseURL(&RecommendationOpts{})
	assert.Equal(t, URL, "https://www.lazada.sg")
	URL = service.getAliceBaseURL(&RecommendationOpts{Platform: search_opts.PlatformPWA})
	assert.Equal(t, URL, "https://www.lazada.sg")

	// Incorrect Alice Base URL wo protocol
	service = &RecommendationService{aliceBaseURL: "www.lazada.sg"}
	URL = service.getAliceBaseURL(&RecommendationOpts{})
	assert.Equal(t, URL, "www.lazada.sg")
	URL = service.getAliceBaseURL(&RecommendationOpts{Platform: search_opts.PlatformPWA})
	assert.Equal(t, URL, "www.lazada.sg")

	// Incorrect Alice Base URL with dub protocol
	service = &RecommendationService{aliceBaseURL: "http://www.lazada.sghttp://www.lazada.sg"}
	URL = service.getAliceBaseURL(&RecommendationOpts{})
	assert.Equal(t, URL, "http://www.lazada.sghttp://www.lazada.sg")
	URL = service.getAliceBaseURL(&RecommendationOpts{Platform: search_opts.PlatformPWA})
	assert.Equal(t, URL, "https://www.lazada.sghttp://www.lazada.sg")
}
