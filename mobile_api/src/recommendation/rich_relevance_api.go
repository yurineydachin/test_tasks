package recommendation_service

import (
	"context"
	"time"
)

type RichRelevanceAPI struct{}

func (r *RichRelevanceAPI) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	return []RecommendationItem{}, nil
}
func (r *RichRelevanceAPI) GetName() string {
	return ProviderRR
}

func (r *RichRelevanceAPI) SetRequestTimeout(timeout time.Duration) {
	return
}
