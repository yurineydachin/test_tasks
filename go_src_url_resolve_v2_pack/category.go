package urlv2

import (
	"strconv"

	"content_api/model/catalog/category"
)

type CategoryResolver struct {
	lang  string
	cache *category.CatalogCategoryCache
}

func GetCategoryResolver(c *category.CatalogCategoryCache, lang string) SourceResolver {
	return &CategoryResolver{
		cache: c,
		lang:  lang,
	}
}

func (resolver *CategoryResolver) RedirectByPath(path string) []Model {
	return nil
}

func (resolver *CategoryResolver) ResolveKeys(keys []string) []Model {
	return convertCategories(resolver.cache.GetByIds(resolver.cache.GetByURL(keys)), resolver.lang)
}

func (resolver *CategoryResolver) RedirectBySearch(search string) []Model {
	return convertCategories(resolver.cache.GetByNames([]string{search}, true), resolver.lang)
}

func convertCategories(rows []*category.CatalogCategoryRow, lang string) []Model {
	if rows == nil || len(rows) == 0 {
		return nil
	}
	result := make([]Model, len(rows))
	for i := range rows {
		result[i] = Model{
			Type:        TYPE_CATEGORY,
			ID:          rows[i].IdCatalogCategory,
			Name:        getCategoryNameByLang(rows[i], lang),
			URLKey:      rows[i].UrlKey,
			RegionalKey: rows[0].RegionalKey,
			FilterValue: strconv.FormatUint(rows[i].IdCatalogCategory, 10),
		}
	}
	return result
}

func getCategoryNameByLang(cat *category.CatalogCategoryRow, lang string) string {
	switch lang {
	case "en":
		if cat.NameEn != "" {
			return cat.NameEn
		}
	case "id":
		if cat.NameId != "" {
			return cat.NameId
		}
	case "ms":
		if cat.NameMs != "" {
			return cat.NameMs
		}
	case "th":
		if cat.NameTh != "" {
			return cat.NameTh
		}
	case "vi":
		if cat.NameVi != "" {
			return cat.NameVi
		}
	}

	return cat.Name
}
