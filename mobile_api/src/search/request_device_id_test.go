package search_service

import (
	"context"
	"crypto/md5"
	"fmt"
	"testing"

	"github.com/opentracing/opentracing-go"
	"github.com/opentracing/opentracing-go/mocktracer"
	"github.com/stretchr/testify/assert"
	ctxmanager "godep.lzd.co/mobapi_lib/context"
)

var mockTracer = mocktracer.New()

func TestFillRequestIDErrInContext(t *testing.T) {
	ctx := opentracing.ContextWithSpan(context.Background(), mockTracer.StartSpan("TestFillDeviceID"))
	fillRequestDeviceIDInContext(ctx)

	span := opentracing.SpanFromContext(ctx)
	if assert.NotNil(t, span, "") {
		assert.Equal(t, span.BaggageItem("RequestID"), "")
		assert.Equal(t, span.BaggageItem("DeviceID"), "")
	}
}

func TestFillRequestIDEmptyData(t *testing.T) {
	ctx := opentracing.ContextWithSpan(context.Background(), mockTracer.StartSpan("TestFillDeviceID"))
	ctx = ctxmanager.NewContext(ctx, &ctxmanager.Context{})
	fillRequestDeviceIDInContext(ctx)

	empty := fmt.Sprintf("%x", md5.Sum([]byte("")))
	span := opentracing.SpanFromContext(ctx)
	if assert.NotNil(t, span, "") {
		assert.Contains(t, span.BaggageItem("RequestID"), empty)
		assert.Equal(t, span.BaggageItem("DeviceID"), "")
	}
}

func TestFillRequestIDByToken(t *testing.T) {
	ctxData := &ctxmanager.Context{
		ReqURI:         "/search/filters/v1?lang=en&phrase=sony&filters=price~100-500",
		ReqTokenHeader: "Acf27JofZetcz+CX+ZvcnwtTqA+76Zv2gMcwHZnOfv+be1DJfi8JSTDkh/lZ5JBImA==",
	}
	ctx := opentracing.ContextWithSpan(context.Background(), mockTracer.StartSpan("TestFillDeviceID"))
	ctx = ctxmanager.NewContext(ctx, ctxData)
	fillRequestDeviceIDInContext(ctx)

	span := opentracing.SpanFromContext(ctx)
	if assert.NotNil(t, span, "") {
		assert.Contains(t, span.BaggageItem("RequestID"), "630c51cc2fdb9e16d9131476f75ae9cc")
		assert.Equal(t, span.BaggageItem("DeviceID"), "")
	}
}

func TestFillRequestIDByTraceID(t *testing.T) {
	ctxData := &ctxmanager.Context{
		ReqURI: "/search/filters/v1?lang=en&phrase=sony&filters=price~100-500",
	}
	ctx := opentracing.ContextWithSpan(context.Background(), mockTracer.StartSpan("TestFillDeviceID"))
	ctx = ctxmanager.NewContext(ctx, ctxData)
	fillRequestDeviceIDInContext(ctx)

	empty := fmt.Sprintf("%x", md5.Sum([]byte("")))
	span := opentracing.SpanFromContext(ctx)
	if assert.NotNil(t, span, "") {
		assert.NotContains(t, span.BaggageItem("RequestID"), empty)
		assert.Equal(t, span.BaggageItem("DeviceID"), "")
	}
}

func TestFillDeviceID(t *testing.T) {
	ctxData := &ctxmanager.Context{
		ReqClientId: "0123456789",
	}
	ctx := opentracing.ContextWithSpan(context.Background(), mockTracer.StartSpan("TestFillDeviceID"))
	ctx = ctxmanager.NewContext(ctx, ctxData)
	fillRequestDeviceIDInContext(ctx)

	empty := fmt.Sprintf("%x", md5.Sum([]byte("")))
	span := opentracing.SpanFromContext(ctx)
	if assert.NotNil(t, span, "") {
		assert.Contains(t, span.BaggageItem("RequestID"), empty)
		assert.Equal(t, span.BaggageItem("DeviceID"), "0123456789")
	}
}
