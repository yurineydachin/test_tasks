package recommendation_service

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestConvertResponseByDataSceince(t *testing.T) {
	response := RecommendedByDataSceinceResponse{
		RecommendationByDataSceince{
			Placement: "Placement0",
			Heading:   "Heading0",
			Products:  []ProductByByDataSceince{},
		},
		RecommendationByDataSceince{
			Placement: "Placement1",
			Heading:   "Heading1",
			Products: []ProductByByDataSceince{
				ProductByByDataSceince{
					SKU:   "SO406ELAA1OZBGSGAMZ",
					CtURL: "ct_url0",
				},
				ProductByByDataSceince{
					SKU:   "SO406ELAA7V1PISGAMZ",
					CtURL: "ct_url1",
				},
				ProductByByDataSceince{
					SKU:   "SO406ELAB7V1PISGAMZ",
					CtURL: "",
				},
			},
		},
	}
	result := convertResponseByDataSceince(response)

	if assert.Equal(t, len(result), 2) {
		assert.Equal(t, result[0].Placement, "Placement0")
		assert.Equal(t, result[0].Title, "Heading0")
		assert.Equal(t, result[1].Placement, "Placement1")
		assert.Equal(t, result[1].Title, "Heading1")
		if assert.Equal(t, len(result[1].Products), 2) {
			assert.Equal(t, result[1].Products[0].SKU, "SO406ELAA1OZBGSGAMZ")
			assert.Equal(t, result[1].Products[0].CtURL, "ct_url0")

			assert.Equal(t, result[1].Products[1].SKU, "SO406ELAA7V1PISGAMZ")
			assert.Equal(t, result[1].Products[1].CtURL, "ct_url1")
		}
	}
}
