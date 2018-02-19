package search_service

import (
	"context"
	"net/url"

	"mobile_search_api/api/ext_services/content_api/model/search"
)

type IContentAPI interface {
	SearchGet(ctx context.Context, params url.Values) (search.Response, error)
	GetSingleProducts(ctx context.Context, skus []string, lang string) ([]search.Product, error)
}
