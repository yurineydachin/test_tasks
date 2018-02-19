package model_service

import (
	"context"

	"godep.lzd.co/catalog_api_client_go/transfer"
)

type ICatalogAPI interface {
	GetHighlights(ctx context.Context, ids []string, field string) (transfer.HighlightsByCriteriaV1Response, error)
	GetSuppliers(ctx context.Context, ids []string, field string) ([]transfer.SupplierSearchV2ResponseItem, error)
	GetBrands(ctx context.Context, ids []string, field string) ([]transfer.BrandSearchV2ResponseItem, error)
	GetCategories(ctx context.Context, ids []string, field string) ([]transfer.CategorySearchV2ResponseItem, error)
}
