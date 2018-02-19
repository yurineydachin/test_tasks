package urlv2

type NilResolver struct{}

func GetNilResolver() SourceResolver {
	return &NilResolver{}
}

func (resolver *NilResolver) RedirectByPath(path string) []Model {
	return nil
}

func (resolver *NilResolver) ResolveKeys(keys []string) []Model {
	return nil
}

func (resolver *NilResolver) RedirectBySearch(search string) []Model {
	return nil
}
