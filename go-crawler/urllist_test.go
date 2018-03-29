package main

import (
	"github.com/stretchr/testify/assert"
	"testing"

	"os"
)

var testName = "found"
var testDomain = "http://test.com"

func clearFile(t *testing.T) {
	realFilename, err := filenameFromUrl(DataDir, testName+"_"+testDomain)
	if assert.Nil(t, err) {
		os.Remove(realFilename)
	}
}

func TestSimpleNewUrlList(t *testing.T) {
	clearFile(t)
	ul, err := NewUrlList(testDomain, testName)
	if assert.Nil(t, err) &&
		assert.NotNil(t, ul) {
		assert.Equal(t, 0, ul.Len())
		assert.Nil(t, ul.Close())
	}
}

func TestAddSomeLines(t *testing.T) {
	clearFile(t)
	ul, err := NewUrlList(testDomain, testName)
	if assert.Nil(t, err) &&
		assert.NotNil(t, ul) {

		assert.Equal(t, 0, ul.Len())
		assert.Equal(t, false, ul.Check("line1"))
		assert.Nil(t, ul.Store("line1"))
		assert.Equal(t, 1, ul.Len())
		assert.Equal(t, true, ul.Check("line1"))

		assert.Equal(t, false, ul.Check("line2"))
		assert.Nil(t, ul.Store("line2"))
		assert.Equal(t, 2, ul.Len())
		assert.Equal(t, true, ul.Check("line2"))

		assert.Nil(t, ul.Close())
	}
}

func TestLoadWithLines(t *testing.T) {
	ul, err := NewUrlList(testDomain, testName)
	if assert.Nil(t, err) &&
		assert.NotNil(t, ul) {

		assert.Equal(t, 2, ul.Len())
		assert.Equal(t, true, ul.Check("line1"))
		assert.Equal(t, true, ul.Check("line2"))
		assert.Equal(t, false, ul.Check("line3"))

		assert.Nil(t, ul.Close())
	}
}
