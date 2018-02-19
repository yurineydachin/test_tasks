package urlv2

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestNilRedirectByPath(t *testing.T) {
	assert.Nil(t, GetNilResolver().RedirectByPath("url-key-brand1"), "")
}

func TestNilResolveKeys(t *testing.T) {
	assert.Nil(t, GetNilResolver().ResolveKeys([]string{"url-key-brand1"}), "")
}

func TestNilRedirectBySearch(t *testing.T) {
	assert.Nil(t, GetNilResolver().RedirectBySearch("Brand Name2"), "")
}
