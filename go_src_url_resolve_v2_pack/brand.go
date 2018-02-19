package urlv2

import (
	"strconv"

	"content_api/model/catalog/brand"
)

type BrandResolver struct {
	lang  string
	cache *brand.CatalogBrandCache
}

func GetBrandResolver(c *brand.CatalogBrandCache, lang string) SourceResolver {
	return &BrandResolver{
		cache: c,
		lang:  lang,
	}
}

func (resolver *BrandResolver) RedirectByPath(path string) []Model {
	return nil
}

func (resolver *BrandResolver) ResolveKeys(keys []string) []Model {
	return convertBrands(resolver.cache.GetByURL(keys))
}

func (resolver *BrandResolver) RedirectBySearch(search string) []Model {
	return convertBrands(resolver.cache.GetByNames([]string{search}, true))
}

func convertBrands(rows []*brand.CatalogBrandRow) []Model {
	if rows == nil || len(rows) == 0 {
		return nil
	}
	result := make([]Model, len(rows))
	for i := range rows {
		result[i] = Model{
			Type:        TYPE_BRAND,
			ID:          rows[i].IDCatalogBrand,
			Name:        rows[i].Name,
			URLKey:      rows[i].UrlKey,
			RegionalKey: rows[i].RegionalKey,
			FilterValue: strconv.FormatUint(rows[i].IDCatalogBrand, 10),
		}
	}
	return result
}
