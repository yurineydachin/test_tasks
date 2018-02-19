package urlv2

import (
	"strconv"

	"content_api/model/suppliers"
)

type SupplierResolver struct {
	lang  string
	cache *suppliers.SuppliersCache
}

func GetSupplierResolver(c *suppliers.SuppliersCache, lang string) SourceResolver {
	return &SupplierResolver{
		cache: c,
		lang:  lang,
	}
}

func (resolver *SupplierResolver) RedirectByPath(path string) []Model {
	return nil
}

func (resolver *SupplierResolver) ResolveKeys(keys []string) []Model {
	list, err := resolver.cache.GetByURL(keys, false)
	if err != nil {
		return nil
	}
	return convertSuppliers(list, resolver.lang)
}

func (resolver *SupplierResolver) RedirectBySearch(search string) []Model {
	return convertSuppliers(resolver.cache.GetByNames([]string{search}, true), resolver.lang)
}

func convertSuppliers(rows []suppliers.SupplierRow, lang string) []Model {
	if rows == nil || len(rows) == 0 {
		return nil
	}
	result := make([]Model, len(rows))
	for i := range rows {
		result[i] = Model{
			Type:        TYPE_SUPPLIER,
			ID:          rows[i].SupplierID,
			Name:        rows[i].Name,
			URLKey:      rows[i].UrlKey,
			RegionalKey: rows[i].RegionalKey,
			FilterValue: strconv.FormatUint(rows[i].SupplierID, 10),
		}
		if lang == "en" && rows[i].NameEn != "" {
			result[i].Name = rows[i].NameEn
		}
	}
	return result
}
