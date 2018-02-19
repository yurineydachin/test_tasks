// AUTOGENERATED FILE: easyjson marshaller/unmarshallers.

package urlv2

import (
	json "encoding/json"
	jlexer "github.com/mailru/easyjson/jlexer"
	jwriter "github.com/mailru/easyjson/jwriter"
)

// suppress unused package warning
var (
	_ = json.RawMessage{}
	_ = jlexer.Lexer{}
	_ = jwriter.Writer{}
)

func easyjsonD3b49167DecodeContentApiModelUrlV2(in *jlexer.Lexer, out *UrlResolve) {
	if in.IsNull() {
		in.Skip()
		return
	}
	in.Delim('{')
	for !in.IsDelim('}') {
		key := in.UnsafeString()
		in.WantColon()
		if in.IsNull() {
			in.Skip()
			in.WantComma()
			continue
		}
		switch key {
		case "redirect":
			if in.IsNull() {
				in.Skip()
				out.Redirect = nil
			} else {
				if out.Redirect == nil {
					out.Redirect = new(Target)
				}
				easyjsonD3b49167DecodeContentApiModelUrlV21(in, &*out.Redirect)
			}
		case "keys":
			if in.IsNull() {
				in.Skip()
				out.UrlKey = nil
			} else {
				in.Delim('[')
				if !in.IsDelim(']') {
					out.UrlKey = make([]Model, 0, 1)
				} else {
					out.UrlKey = []Model{}
				}
				for !in.IsDelim(']') {
					var v1 Model
					easyjsonD3b49167DecodeContentApiModelUrlV22(in, &v1)
					out.UrlKey = append(out.UrlKey, v1)
					in.WantComma()
				}
				in.Delim(']')
			}
		case "static":
			if in.IsNull() {
				in.Skip()
				out.StaticPage = nil
			} else {
				if out.StaticPage == nil {
					out.StaticPage = new(StaticPage)
				}
				easyjsonD3b49167DecodeContentApiModelUrlV23(in, &*out.StaticPage)
			}
		case "state":
			if in.IsNull() {
				in.Skip()
				out.State = nil
			} else {
				in.Delim('[')
				if !in.IsDelim(']') {
					out.State = make([]StateItem, 0, 1)
				} else {
					out.State = []StateItem{}
				}
				for !in.IsDelim(']') {
					var v2 StateItem
					easyjsonD3b49167DecodeContentApiModelUrlV24(in, &v2)
					out.State = append(out.State, v2)
					in.WantComma()
				}
				in.Delim(']')
			}
		default:
			in.SkipRecursive()
		}
		in.WantComma()
	}
	in.Delim('}')
}
func easyjsonD3b49167EncodeContentApiModelUrlV2(out *jwriter.Writer, in UrlResolve) {
	out.RawByte('{')
	first := true
	_ = first
	if !first {
		out.RawByte(',')
	}
	first = false
	out.RawString("\"redirect\":")
	if in.Redirect == nil {
		out.RawString("null")
	} else {
		easyjsonD3b49167EncodeContentApiModelUrlV21(out, *in.Redirect)
	}
	if !first {
		out.RawByte(',')
	}
	first = false
	out.RawString("\"keys\":")
	if in.UrlKey == nil {
		out.RawString("null")
	} else {
		out.RawByte('[')
		for v3, v4 := range in.UrlKey {
			if v3 > 0 {
				out.RawByte(',')
			}
			easyjsonD3b49167EncodeContentApiModelUrlV22(out, v4)
		}
		out.RawByte(']')
	}
	if !first {
		out.RawByte(',')
	}
	first = false
	out.RawString("\"static\":")
	if in.StaticPage == nil {
		out.RawString("null")
	} else {
		easyjsonD3b49167EncodeContentApiModelUrlV23(out, *in.StaticPage)
	}
	if !first {
		out.RawByte(',')
	}
	first = false
	out.RawString("\"state\":")
	if in.State == nil {
		out.RawString("null")
	} else {
		out.RawByte('[')
		for v5, v6 := range in.State {
			if v5 > 0 {
				out.RawByte(',')
			}
			easyjsonD3b49167EncodeContentApiModelUrlV24(out, v6)
		}
		out.RawByte(']')
	}
	out.RawByte('}')
}

// MarshalJSON supports json.Marshaler interface
func (v UrlResolve) MarshalJSON() ([]byte, error) {
	w := jwriter.Writer{}
	easyjsonD3b49167EncodeContentApiModelUrlV2(&w, v)
	return w.Buffer.BuildBytes(), w.Error
}

// MarshalEasyJSON supports easyjson.Marshaler interface
func (v UrlResolve) MarshalEasyJSON(w *jwriter.Writer) {
	easyjsonD3b49167EncodeContentApiModelUrlV2(w, v)
}

// UnmarshalJSON supports json.Unmarshaler interface
func (v *UrlResolve) UnmarshalJSON(data []byte) error {
	r := jlexer.Lexer{Data: data}
	easyjsonD3b49167DecodeContentApiModelUrlV2(&r, v)
	return r.Error()
}

// UnmarshalEasyJSON supports easyjson.Unmarshaler interface
func (v *UrlResolve) UnmarshalEasyJSON(l *jlexer.Lexer) {
	easyjsonD3b49167DecodeContentApiModelUrlV2(l, v)
}
func easyjsonD3b49167DecodeContentApiModelUrlV24(in *jlexer.Lexer, out *StateItem) {
	if in.IsNull() {
		in.Skip()
		return
	}
	in.Delim('{')
	for !in.IsDelim('}') {
		key := in.UnsafeString()
		in.WantColon()
		if in.IsNull() {
			in.Skip()
			in.WantComma()
			continue
		}
		switch key {
		case "path":
			out.Path = string(in.String())
		case "search":
			out.Search = string(in.String())
		case "query":
			out.Query = string(in.String())
		case "keys":
			if in.IsNull() {
				in.Skip()
				out.Keys = nil
			} else {
				in.Delim('[')
				if !in.IsDelim(']') {
					out.Keys = make([]string, 0, 4)
				} else {
					out.Keys = []string{}
				}
				for !in.IsDelim(']') {
					var v7 string
					v7 = string(in.String())
					out.Keys = append(out.Keys, v7)
					in.WantComma()
				}
				in.Delim(']')
			}
		case "items":
			if in.IsNull() {
				in.Skip()
				out.Models = nil
			} else {
				in.Delim('[')
				if !in.IsDelim(']') {
					out.Models = make([]Model, 0, 1)
				} else {
					out.Models = []Model{}
				}
				for !in.IsDelim(']') {
					var v8 Model
					easyjsonD3b49167DecodeContentApiModelUrlV22(in, &v8)
					out.Models = append(out.Models, v8)
					in.WantComma()
				}
				in.Delim(']')
			}
		default:
			in.SkipRecursive()
		}
		in.WantComma()
	}
	in.Delim('}')
}
func easyjsonD3b49167EncodeContentApiModelUrlV24(out *jwriter.Writer, in StateItem) {
	out.RawByte('{')
	first := true
	_ = first
	if in.Path != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"path\":")
		out.String(string(in.Path))
	}
	if in.Search != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"search\":")
		out.String(string(in.Search))
	}
	if in.Query != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"query\":")
		out.String(string(in.Query))
	}
	if len(in.Keys) != 0 {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"keys\":")
		if in.Keys == nil {
			out.RawString("null")
		} else {
			out.RawByte('[')
			for v9, v10 := range in.Keys {
				if v9 > 0 {
					out.RawByte(',')
				}
				out.String(string(v10))
			}
			out.RawByte(']')
		}
	}
	if !first {
		out.RawByte(',')
	}
	first = false
	out.RawString("\"items\":")
	if in.Models == nil {
		out.RawString("null")
	} else {
		out.RawByte('[')
		for v11, v12 := range in.Models {
			if v11 > 0 {
				out.RawByte(',')
			}
			easyjsonD3b49167EncodeContentApiModelUrlV22(out, v12)
		}
		out.RawByte(']')
	}
	out.RawByte('}')
}
func easyjsonD3b49167DecodeContentApiModelUrlV23(in *jlexer.Lexer, out *StaticPage) {
	if in.IsNull() {
		in.Skip()
		return
	}
	in.Delim('{')
	for !in.IsDelim('}') {
		key := in.UnsafeString()
		in.WantColon()
		if in.IsNull() {
			in.Skip()
			in.WantComma()
			continue
		}
		switch key {
		case "url":
			out.Key = string(in.String())
		case "lang":
			if in.IsNull() {
				in.Skip()
				out.Lang = nil
			} else {
				in.Delim('[')
				if !in.IsDelim(']') {
					out.Lang = make([]string, 0, 4)
				} else {
					out.Lang = []string{}
				}
				for !in.IsDelim(']') {
					var v13 string
					v13 = string(in.String())
					out.Lang = append(out.Lang, v13)
					in.WantComma()
				}
				in.Delim(']')
			}
		default:
			in.SkipRecursive()
		}
		in.WantComma()
	}
	in.Delim('}')
}
func easyjsonD3b49167EncodeContentApiModelUrlV23(out *jwriter.Writer, in StaticPage) {
	out.RawByte('{')
	first := true
	_ = first
	if in.Key != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"url\":")
		out.String(string(in.Key))
	}
	if len(in.Lang) != 0 {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"lang\":")
		if in.Lang == nil {
			out.RawString("null")
		} else {
			out.RawByte('[')
			for v14, v15 := range in.Lang {
				if v14 > 0 {
					out.RawByte(',')
				}
				out.String(string(v15))
			}
			out.RawByte(']')
		}
	}
	out.RawByte('}')
}
func easyjsonD3b49167DecodeContentApiModelUrlV22(in *jlexer.Lexer, out *Model) {
	if in.IsNull() {
		in.Skip()
		return
	}
	in.Delim('{')
	for !in.IsDelim('}') {
		key := in.UnsafeString()
		in.WantColon()
		if in.IsNull() {
			in.Skip()
			in.WantComma()
			continue
		}
		switch key {
		case "id":
			out.ID = uint64(in.Uint64())
		case "key":
			out.URLKey = string(in.String())
		case "type":
			out.Type = string(in.String())
		case "name":
			out.Name = string(in.String())
		case "filter_value":
			out.FilterValue = string(in.String())
		case "regional_key":
			out.RegionalKey = string(in.String())
		case "code":
			out.Code = int(in.Int())
		default:
			in.SkipRecursive()
		}
		in.WantComma()
	}
	in.Delim('}')
}
func easyjsonD3b49167EncodeContentApiModelUrlV22(out *jwriter.Writer, in Model) {
	out.RawByte('{')
	first := true
	_ = first
	if in.ID != 0 {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"id\":")
		out.Uint64(uint64(in.ID))
	}
	if in.URLKey != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"key\":")
		out.String(string(in.URLKey))
	}
	if in.Type != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"type\":")
		out.String(string(in.Type))
	}
	if in.Name != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"name\":")
		out.String(string(in.Name))
	}
	if in.FilterValue != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"filter_value\":")
		out.String(string(in.FilterValue))
	}
	if in.RegionalKey != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"regional_key\":")
		out.String(string(in.RegionalKey))
	}
	if in.Code != 0 {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"code\":")
		out.Int(int(in.Code))
	}
	out.RawByte('}')
}
func easyjsonD3b49167DecodeContentApiModelUrlV21(in *jlexer.Lexer, out *Target) {
	if in.IsNull() {
		in.Skip()
		return
	}
	in.Delim('{')
	for !in.IsDelim('}') {
		key := in.UnsafeString()
		in.WantColon()
		if in.IsNull() {
			in.Skip()
			in.WantComma()
			continue
		}
		switch key {
		case "target":
			out.Target = string(in.String())
		case "type":
			out.Type = string(in.String())
		case "code":
			out.HTTPCode = int(in.Int())
		case "mobapi":
			out.MobAPI = bool(in.Bool())
		default:
			in.SkipRecursive()
		}
		in.WantComma()
	}
	in.Delim('}')
}
func easyjsonD3b49167EncodeContentApiModelUrlV21(out *jwriter.Writer, in Target) {
	out.RawByte('{')
	first := true
	_ = first
	if in.Target != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"target\":")
		out.String(string(in.Target))
	}
	if in.Type != "" {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"type\":")
		out.String(string(in.Type))
	}
	if in.HTTPCode != 0 {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"code\":")
		out.Int(int(in.HTTPCode))
	}
	if in.MobAPI {
		if !first {
			out.RawByte(',')
		}
		first = false
		out.RawString("\"mobapi\":")
		out.Bool(bool(in.MobAPI))
	}
	out.RawByte('}')
}
