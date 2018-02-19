package search_service

import (
	"context"
	"crypto/md5"
	"fmt"
	"time"

	"github.com/opentracing/opentracing-go"
	"godep.lzd.co/go-trace"
	ctxmanager "godep.lzd.co/mobapi_lib/context"
)

func fillRequestDeviceIDInContext(ctx context.Context) {
	ctxData, err := ctxmanager.FromContext(ctx)
	if err != nil {
		return
	}
	var uniqID string
	if ctxData.ReqTokenHeader != "" {
		uniqID = ctxData.ReqTokenHeader
	} else {
		if span := opentracing.SpanFromContext(ctx); span != nil {
			if sc, ok := span.Context().(gotrace.Span); ok {
				uniqID = sc.TraceID
			}
		}
	}
	requestID := fmt.Sprintf("%x_%d", md5.Sum([]byte(ctxData.ReqURI+uniqID)), time.Now().UnixNano())
	deviceID := ctxData.ReqClientId
	if span := opentracing.SpanFromContext(ctx); span != nil {
		if requestID != "" {
			span.SetBaggageItem("RequestID", requestID)
		}
		if deviceID != "" {
			span.SetBaggageItem("DeviceID", deviceID)
		}
	}
}
