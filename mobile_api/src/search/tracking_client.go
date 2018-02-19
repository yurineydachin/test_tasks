package search_service

import (
	"context"
	"fmt"
	"net/http"
	"net/http/httputil"
	"net/url"
	"time"

	"godep.lzd.co/go-dconfig"
	"godep.lzd.co/go-trace"
	"godep.lzd.co/mobapi_lib/cache/inmem"
	ctxmanager "godep.lzd.co/mobapi_lib/context"
	"godep.lzd.co/mobapi_lib/handler"
	"godep.lzd.co/mobapi_lib/logger"
	"godep.lzd.co/mobapi_lib/utils"

	"encoding/json"
	"mobile_search_api/api/core/monitoring"
	"mobile_search_api/api/ext_services/nobalancer"
	"mobile_search_api/api/ext_services/service_error"
)

var TrackingEnabled = false
var TrackingAPIURL = "http://10.222.250.8:8080/sendmsg"
var TrackingAPITimeout = 10

func init() {
	dconfig.RegisterString("tracking-api-url", "TrackingAPI URL", TrackingAPIURL,
		func(v string) {
			TrackingAPIURL = v
		})
	dconfig.RegisterInt("tracking-api-timeout", "TrackingAPI timeout (ms)", TrackingAPITimeout,
		func(v int) {
			TrackingAPITimeout = v
		})
	dconfig.RegisterBool("tracking-enabled", "Tracking enabled", TrackingEnabled,
		func(v bool) {
			TrackingEnabled = v
		})
}

type ITrackingAPI interface {
	SendData(context.Context, *TrackingData) error
}

type TrackingAPI struct {
	client nobalancer_client.IExtAPIClient
}

func NewTrackingAPIClient() ITrackingAPI {
	serviceName := "tracking_api"

	client := &http.Client{
		Transport: &http.Transport{
			//DisableCompression: true,
			MaxIdleConnsPerHost: 20,
		},
		Timeout: time.Duration(TrackingAPITimeout) * time.Millisecond,
	}

	callbacks := nobalancer_client.Callbacks{
		OnPrepareRequest: func(ctx context.Context, req *http.Request, data interface{}) context.Context {
			b, _ := httputil.DumpRequest(req, true)
			logger.Debug(ctx, "[Tracking] Request: %s", string(b))

			gotrace.SetHeaderRequestFromContext(ctx, req.Header)
			if s, e := ctxmanager.GetLoggerSession(ctx); e == nil {
				serviceSession := s.NewSession(serviceName, handler.CurlFromRequest(req))
				ctx = ctxmanager.NewContext(ctx, &ctxmanager.Context{
					SessionLogger: serviceSession,
				})
			}

			// Replace path in monitoring labels with placements if it not empty
			path := utils.GetPath(req)
			ctx = monitoring.SetMonitorData(ctx, serviceName, path)
			monitoring.FromContext(ctx).TimeStart = time.Now()

			if m, e := ctxmanager.GetSessionMocker(ctx); e == nil {
				sessionMocker := m.NewMocker(serviceName)
				sessionMocker.ExternalRequestParams(data)
				ctx = ctxmanager.NewContext(ctx, &ctxmanager.Context{
					SessionMocker: sessionMocker,
				})
			}
			return ctx
		},
		OnResponseUnmarshaling: func(ctx context.Context, req *http.Request, response *http.Response, buf interface{}, result []byte) error {
			if sessionMocker, err := ctxmanager.GetSessionMocker(ctx); err == nil {
				sessionMocker.ExternalRequest(req, response.StatusCode, result)
			} else {
				logger.Warning(ctx, err.Error())
			}
			switch v := buf.(type) {
			case *string:
				value := string(result)
				if v != nil {
					*v = value
				} else {
					v = &value
				}
			}
			return nil
		},
		OnSuccess: func(ctx context.Context, req *http.Request, data interface{}) {
			if s, e := ctxmanager.GetLoggerSession(ctx); e == nil {
				s.Finish(data)
			}
			monitoring.MonitorTimeResponse(ctx, 200)
		},
		OnError: func(ctx context.Context, req *http.Request, err error) error {
			if s, e := ctxmanager.GetLoggerSession(ctx); e == nil {
				s.Error(err)
			}
			if _, ok := err.(*service_error.ServiceError); !ok {
				monitoring.MonitorTimeResponse(ctx, 500)
			}
			return service_error.AddServiceName(err, serviceName)
		},
		OnPanic: func(ctx context.Context, req *http.Request, r interface{}, trace []byte) error {
			if s, e := ctxmanager.GetLoggerSession(ctx); e == nil {
				s.Error(trace)
			}
			monitoring.MonitorTimeResponse(ctx, 500)
			return service_error.AddServiceName(fmt.Errorf("panic while calling service: %v", r), serviceName)
		},
	}

	return &TrackingAPI{client: nobalancer_client.NewExtAPIClient(client, callbacks, inmem.NewFromFlags("tracking-api"))}
}

func (api *TrackingAPI) SendData(ctx context.Context, data *TrackingData) error {
	if !TrackingEnabled {
		return nil
	}
	jsonData, err := json.Marshal(data)
	if err != nil {
		return err
	}

	body := url.Values{}
	body.Add("t", "lazada_search_pvlog")
	body.Add("h", data.RN)
	body.Add("msg", string(jsonData))

	buf := ""
	api.client.SetRequestTimeout(time.Duration(TrackingAPITimeout) * time.Millisecond)
	return api.client.SetEncode(ctx, TrackingAPIURL, body, &buf, nil)
}
