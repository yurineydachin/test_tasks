package url_resolve_service_v2

import (
	"context"
	"mobile_search_api/api/ext_services"
)

type IContentAPI interface {
	GetURLResolveV2(ctx context.Context, path, search, lang string, checkRedirect bool, overlays map[string]string) (*ext_services.URLResolve, error)
}
