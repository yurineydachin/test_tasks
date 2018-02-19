package urlv2

import (
	"content_api/model/cms_folder"
	"content_api/model/page_manager/pagelist"
)

type StaticPageResolver struct {
	lang     string
	isMobapi bool
	cacheCms *cms_folder.CmsFolderCache
	cachePM  *pagelist.PageListCache
}

func GetStaticPageResolver(cacheCms *cms_folder.CmsFolderCache, cachePM *pagelist.PageListCache, isMobapi bool, lang string) SourceResolver {
	return &StaticPageResolver{
		cacheCms: cacheCms,
		cachePM:  cachePM,
		isMobapi: isMobapi,
		lang:     lang,
	}
}

func (resolver *StaticPageResolver) RedirectByPath(path string) []Model {
	row, err := resolver.cacheCms.GetStaticPage(path)
	if err != nil || row == nil {
		return nil
	}
	langFound := false
	for i := range row.Lang {
		if resolver.lang == row.Lang[i] {
			langFound = true
			break
		}
	}
	if !langFound {
		return nil
	}

	if resolver.isMobapi && resolver.cachePM != nil && resolver.cachePM.IsMobileAppStaticPageExist(path, resolver.lang) == false {
		return nil
	}

	return []Model{
		Model{
			Type:   TYPE_STATIC_PAGE,
			URLKey: row.Key,
		},
	}
}

func (resolver *StaticPageResolver) ResolveKeys(keys []string) []Model {
	return nil
}

func (resolver *StaticPageResolver) RedirectBySearch(search string) []Model {
	return nil
}
