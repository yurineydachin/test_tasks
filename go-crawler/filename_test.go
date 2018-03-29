package main

import (
	"github.com/stretchr/testify/assert"
	"testing"
)

func TestCheckPathSimple(t *testing.T) {
	dir, filename := checkPath("p1/p2/p3/f1", 100)
	assert.Equal(t, dir, "p1/p2/p3")
	assert.Equal(t, filename, "f1_")
}

func TestCheckPathWrongSimbols(t *testing.T) {
	dir, filename := checkPath("p1&%/::p2///p3/-f1*_:", 100)
	assert.Equal(t, dir, "p1_/_p2/p3")
	assert.Equal(t, filename, "_f1__")
}

func TestCheckPathReal(t *testing.T) {
	dir, filename := checkPath("http://yandex.ru/video/search?source=oo&text=%D0%BA%D0%BE%D0%BC%D0%B5%D0%B4%D0%B8%D0%B8&path=popular_req", 100)
	assert.Equal(t, dir, "http_/yandex.ru/video")
	assert.Equal(t, filename, "search?source=oo_text=_D0_BA_D0_BE_D0_BC_D0_B5_D0_B4_D0_B8_D0_B8_path=popular_req_")
}

func TestCheckPathChunks(t *testing.T) {
	dir, filename := checkPath("firstLongPathName/secondLongPathName/thirdLongPathName", 5)
	assert.Equal(t, dir, "first/LongP/athNa/me/secon/dLong/PathN/ame/third/LongP/athNa")
	assert.Equal(t, filename, "me_")
}
