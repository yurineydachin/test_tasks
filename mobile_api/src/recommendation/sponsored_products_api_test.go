package recommendation_service

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestConvertResponseBySponsoredProducts(t *testing.T) {
	response := RecommendedBySponsoredProductsResponse{
		RecommendationBySponsoredProducts{
			Placement: "Placement0",
			Heading:   "Heading0",
			Products:  []ProductByBySponsoredProducts{},
		},
		RecommendationBySponsoredProducts{
			Placement: "Placement1",
			Heading:   "Heading1",
			Products: []ProductByBySponsoredProducts{
				ProductByBySponsoredProducts{
					SKU:   "SO406ELAA1OZBGSGAMZ-1017309",
					CtURL: "ct_url0",
					CID:   "cid_1",
					DID:   "did_1",
				},
				ProductByBySponsoredProducts{
					SKU:       "SO406ELAA7V1PISGAMZ-1017310",
					CtURL:     "ct_url1",
					SimpleSKU: "simple_sku_2",
					PDPSKU:    "pdp_sku_2",
				},
				ProductByBySponsoredProducts{
					SKU:       "SO406ELAA7V1PISGAMZ-1017311",
					CtURL:     "",
					SimpleSKU: "simple_sku_3",
					PDPSKU:    "pdp_sku_3",
				},
			},
		},
	}
	result := convertResponseBySponsoredProducts(response)

	if assert.Equal(t, len(result), 2) {
		assert.Equal(t, result[0].Placement, "Placement0")
		assert.Equal(t, result[0].Title, "Heading0")
		assert.Equal(t, result[1].Placement, "Placement1")
		assert.Equal(t, result[1].Title, "Heading1")
		if assert.Equal(t, len(result[1].Products), 2) {
			assert.Equal(t, result[1].Products[0].SKU, "SO406ELAA1OZBGSGAMZ")
			assert.Equal(t, result[1].Products[0].CtURL, "ct_url0")
			assert.Equal(t, result[1].Products[0].CID, "cid_1")
			assert.Equal(t, result[1].Products[0].DID, "did_1")
			assert.Equal(t, result[1].Products[0].SimpleSKU, "")
			assert.Equal(t, result[1].Products[0].PDPSKU, "")

			assert.Equal(t, result[1].Products[1].SKU, "SO406ELAA7V1PISGAMZ")
			assert.Equal(t, result[1].Products[1].CtURL, "ct_url1")
			assert.Equal(t, result[1].Products[1].CID, "")
			assert.Equal(t, result[1].Products[1].DID, "")
			assert.Equal(t, result[1].Products[1].SimpleSKU, "simple_sku_2")
			assert.Equal(t, result[1].Products[1].PDPSKU, "pdp_sku_2")
		}
	}
}
