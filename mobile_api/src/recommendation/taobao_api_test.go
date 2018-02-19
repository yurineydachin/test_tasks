package recommendation_service

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestConvertResponseByWidget(t *testing.T) {
	response := RecommendedByWidgetResponse{
		RecommendationByWidget{
			Placement: 0,
			Heading:   "Heading0",
			Products:  []ProductByWidget{},
		},
		RecommendationByWidget{
			Placement: 1,
			Heading:   "Heading1",
			Products: []ProductByWidget{
				ProductByWidget{
					SKU:   "SO406ELAA2OZBGSGAMZ",
					CtURL: "",
				},
				ProductByWidget{
					SKU:   "SO406ELAA1OZBGSGAMZ",
					CtURL: "ct_url0",
				},
				ProductByWidget{
					SKU:   "SO406ELAA7V1PISGAMZ",
					CtURL: "ct_url1",
				},
			},
		},
	}
	result := convertResponseByWidget(response)

	if assert.Equal(t, len(result), 2) {
		assert.Equal(t, result[0].Placement, "0")
		assert.Equal(t, result[0].Title, "Heading0")
		assert.Equal(t, result[1].Placement, "1")
		assert.Equal(t, result[1].Title, "Heading1")
		if assert.Equal(t, len(result[1].Products), 2) {
			assert.Equal(t, result[1].Products[0].SKU, "SO406ELAA1OZBGSGAMZ")
			assert.Equal(t, result[1].Products[0].CtURL, "ct_url0")

			assert.Equal(t, result[1].Products[1].SKU, "SO406ELAA7V1PISGAMZ")
			assert.Equal(t, result[1].Products[1].CtURL, "ct_url1")
		}
	}
}

func TestConvertResponseByPlacement(t *testing.T) {
	response := RecommendedByPlacementResponse{
		RecommendationByPlacement{
			Placement: "Placement0",
			Heading:   "Heading0",
			Products:  []ProductByPlacement{},
		},
		RecommendationByPlacement{
			Placement: "Placement1",
			Heading:   "Heading1",
			Products: []ProductByPlacement{
				ProductByPlacement{
					SKU:   "SO406ELAA1OZBGSGAMZ",
					CtURL: "ct_url0",
				},
				ProductByPlacement{
					SKU:   "SO406ELAA7V2PISGAMZ",
					CtURL: "",
				},
				ProductByPlacement{
					SKU:   "SO406ELAA7V1PISGAMZ",
					CtURL: "ct_url1",
				},
			},
		},
	}
	result := convertResponseByPlacement(response)

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
