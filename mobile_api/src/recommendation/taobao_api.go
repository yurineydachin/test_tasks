package recommendation_service

import (
	"context"
	"fmt"
	"net/http"
	"net/http/httputil"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/mailru/easyjson"
	"github.com/sergei-svistunov/gorpc/transport/cache"
	"godep.lzd.co/go-trace"
	"godep.lzd.co/mobapi_lib/cache/inmem"
	ctxmanager "godep.lzd.co/mobapi_lib/context"
	"godep.lzd.co/mobapi_lib/handler"
	"godep.lzd.co/mobapi_lib/logger"
	"godep.lzd.co/mobapi_lib/utils"
	"mobile_search_api/api/core/monitoring"
	"mobile_search_api/api/ext_services/nobalancer"
	"mobile_search_api/api/ext_services/service_error"
	"mobile_search_api/srv/product"
)

type TaoBaoAPIByWidget struct {
	client nobalancer_client.IExtAPIClient
}

type TaoBaoAPIByPlacement struct {
	client nobalancer_client.IExtAPIClient
}

// easyjson:json
type TaoBaoHttpSessionResponse struct {
	Data      easyjson.RawMessage `json:"data"`
	Success   bool                `json:"success"`
	ID        string              `json:"id"`
	ErrorCode string              `json:"errorCode"`
}

func NewTaoBaoAPIClient(proxyURL string) (*TaoBaoAPIByWidget, *TaoBaoAPIByPlacement) {
	serviceName := "taobao_api"

	transport := &http.Transport{
		//DisableCompression: true,
		MaxIdleConnsPerHost: 20,
	}
	if proxyURL != "" {
		if proxy, err := url.Parse(proxyURL); err == nil {
			transport.Proxy = http.ProxyURL(proxy)
		}
	}
	client := &http.Client{
		Transport: transport,
		Timeout:   RecommendDefaultRequestTimeout,
	}

	callbacks := nobalancer_client.Callbacks{
		OnPrepareRequest: func(ctx context.Context, req *http.Request, data interface{}) context.Context {
			b, _ := httputil.DumpRequest(req, true)
			logger.Debug(ctx, "[TaoBao] Request to taobao: %s", string(b))

			gotrace.SetHeaderRequestFromContext(ctx, req.Header)
			if s, e := ctxmanager.GetLoggerSession(ctx); e == nil {
				serviceSession := s.NewSession(serviceName, handler.CurlFromRequest(req))
				ctx = ctxmanager.NewContext(ctx, &ctxmanager.Context{
					SessionLogger: serviceSession,
				})
			}
			// Replace path in monitoring labels with placements if it not empty
			path := req.URL.Query().Get("placements")
			if path == "" {
				path = utils.GetPath(req)
				// Monitoring for taobao-backup url
				for _, part := range strings.Split(path, "/") {
					if strings.Contains(part, "placements=") {
						kv := strings.Split(part, "=")
						if len(kv) == 2 && kv[1] != "" {
							path = "backup/" + kv[1]
						}
					}
				}
			}

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
			var mainResp TaoBaoHttpSessionResponse
			if err := nobalancer_client.Unmarshal(result, &mainResp); err != nil {
				return fmt.Errorf("request %q failed to decode response %q: %v", req.URL.RequestURI(), string(result), err)
			}
			if mainResp.Success {
				if err := nobalancer_client.Unmarshal(mainResp.Data, buf); err != nil {
					return fmt.Errorf("request %q failed to decode response data %+v: %v", req.URL.RequestURI(), mainResp.Data, err)
				}
				return nil
			}
			return &service_error.ServiceError{
				Message: mainResp.ErrorCode,
			}
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

	apiClient := nobalancer_client.NewExtAPIClient(client, callbacks, inmem.NewFromFlags("taobao-api"))
	return &TaoBaoAPIByWidget{client: apiClient}, &TaoBaoAPIByPlacement{client: apiClient}
}

func (opts *RecommendationOpts) FillTaobaoConfig(config *RecommendationConfig) {
	opts.ClientID = config.Taobao.ClientID
	opts.Endpoint = config.Taobao.Endpoint
	opts.EndpointBackup = config.Taobao.EndpointBackup
	if opts.SKU != "" {
		opts.AppID = config.Taobao.AppIDPdp
	} else {
		opts.AppID = config.Taobao.AppIDHome
	}
}

func (opts *RecommendationOpts) commonValues() url.Values {
	values := url.Values{}
	values.Add("jsonBackup", "true")

	if opts.AppID != "" {
		values.Add("appId", opts.AppID)
	}
	if opts.AdID != "" {
		values.Add("adId", opts.AdID)
	}
	if opts.SKU != "" {
		values.Add("sku", opts.SKU)
	}

	if opts.CustomerID != 0 {
		values.Add("customerId", strconv.FormatUint(opts.CustomerID, 10))
	}

	if len(opts.RegionalKeys) > 0 {
		values.Add("regional_key", opts.RegionalKeys[0])
	}

	if opts.Venture != "" {
		values.Add("venture", opts.Venture)
	}

	if opts.Lang != "" {
		values.Add("lang", opts.Lang)
	}
	return values
}

func (opts *RecommendationOpts) placementURL() string {
	values := opts.commonValues()
	if len(opts.Placements) > 0 {
		values.Add("placements", strings.Join(opts.Placements, ","))
	}
	return nobalancer_client.CreateRawURL(opts.Endpoint, "?"+values.Encode())
}

func (opts *RecommendationOpts) widgetURL() string {
	values := opts.commonValues()
	if len(opts.Placements) > 0 {
		values.Add("lazadaWidgetIds", strings.Join(opts.Placements, ","))
	}
	return nobalancer_client.CreateRawURL(opts.EndpointBackup, "?"+values.Encode())
}

func (opts *RecommendationOpts) placementBackupURL() string {
	values := []string{"jsonBackup=true"}

	if opts.Lang != "" {
		values = append(values, "lang="+opts.Lang)
	}

	if len(opts.Placements) > 0 {
		values = append(values, "placements="+strings.Join(opts.Placements, ","))
	}

	if opts.SKU != "" {
		values = append(values, "sku="+opts.SKU)
	}

	if opts.Venture != "" {
		values = append(values, "venture="+opts.Venture)
	}
	return nobalancer_client.CreateRawURL(opts.EndpointBackup, opts.AppID+"/"+strings.Join(values, "/")+"/data.jsonp")
}

func (opts *RecommendationOpts) widgetBackupURL() string {
	values := []string{"jsonBackup=true"}

	if opts.Lang != "" {
		values = append(values, "lang="+opts.Lang)
	}

	if len(opts.Placements) > 0 {
		values = append(values, "lazadaWidgetIds="+strings.Join(opts.Placements, ","))
	}

	if opts.CustomerID != 0 {
		values = append(values, "customerId="+strconv.FormatUint(opts.CustomerID, 10))
	}
	return nobalancer_client.CreateRawURL(opts.EndpointBackup, opts.AppID+"/"+strings.Join(values, "/")+"/data.jsonp")
}

// easyjson:json
type RecommendedByWidgetResponse []RecommendationByWidget

type RecommendationByWidget struct {
	Placement uint64            `json:"widgetId"`
	Heading   string            `json:"heading"`
	Products  []ProductByWidget `json:"products"`
}

type ProductByWidget struct {
	SKU   string `json:"sku"`
	CtURL string `json:"ct_url"`
}

// easyjson:json
type RecommendedByPlacementResponse []RecommendationByPlacement

type RecommendationByPlacement struct {
	Placement string               `json:"placement_name"`
	Heading   string               `json:"strat_message"`
	Products  []ProductByPlacement `json:"recs"`
}

type ProductByPlacement struct {
	SKU   string `json:"pid"`
	CtURL string `json:"ct_url"`
}

func (api *TaoBaoAPIByWidget) GetName() string {
	return ProviderTB
}

func (api *TaoBaoAPIByWidget) SetRequestTimeout(timeout time.Duration) {
	api.client.SetRequestTimeout(timeout)
}

func (api *TaoBaoAPIByWidget) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	opts.FillTaobaoConfig(config)

	var result RecommendedByWidgetResponse
	var entry = cache.CacheEntry{Body: &result}

	err := api.client.GetWithCache(ctx, opts.widgetURL(), &entry, nil)

	if err != nil {
		_ = api.client.Get(ctx, opts.widgetBackupURL(), &result, nil)
	}

	if result, ok := entry.Body.(*RecommendedByWidgetResponse); ok {
		return convertResponseByWidget(*result), nil
	}

	return convertResponseByWidget(result), nil
}

func (api *TaoBaoAPIByPlacement) GetName() string {
	return ProviderTB
}

func (api *TaoBaoAPIByPlacement) SetRequestTimeout(timeout time.Duration) {
	api.client.SetRequestTimeout(timeout)
}

func (api *TaoBaoAPIByPlacement) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	opts.FillTaobaoConfig(config)

	var result RecommendedByPlacementResponse
	var entry = cache.CacheEntry{Body: &result}

	err := api.client.GetWithCache(ctx, opts.placementURL(), &entry, nil)
	if err != nil {
		_ = api.client.Get(ctx, opts.placementBackupURL(), &result, nil)
	}

	if result, ok := entry.Body.(*RecommendedByPlacementResponse); ok {
		return convertResponseByPlacement(*result), nil
	}
	return convertResponseByPlacement(result), nil
}

func convertResponseByWidget(response RecommendedByWidgetResponse) []RecommendationItem {
	result := make([]RecommendationItem, len(response))
	for i, recommendation := range response {
		result[i] = RecommendationItem{
			Placement: strconv.FormatUint(recommendation.Placement, 10),
			Title:     recommendation.Heading,
			Products:  make([]product_service.Product, 0, len(recommendation.Products)),
		}
		for _, rProduct := range recommendation.Products {
			if rProduct.SKU == "" || rProduct.CtURL == "" {
				continue
			}
			result[i].Products = append(result[i].Products, product_service.Product{
				SKU:   rProduct.SKU,
				CtURL: rProduct.CtURL,
			})
		}
	}
	return result
}

func convertResponseByPlacement(response RecommendedByPlacementResponse) []RecommendationItem {
	result := make([]RecommendationItem, len(response))
	for i, recommendation := range response {
		result[i] = RecommendationItem{
			Placement: recommendation.Placement,
			Title:     recommendation.Heading,
			Products:  make([]product_service.Product, 0, len(recommendation.Products)),
		}
		for _, rProduct := range recommendation.Products {
			if rProduct.SKU == "" || rProduct.CtURL == "" {
				continue
			}
			result[i].Products = append(result[i].Products, product_service.Product{
				SKU:   rProduct.SKU,
				CtURL: rProduct.CtURL,
			})
		}
	}
	return result
}
