package recommendation_service

import (
	"context"
	"crypto/tls"
	"fmt"
	"net/http"
	"net/http/httputil"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/sergei-svistunov/gorpc/transport/cache"
	"godep.lzd.co/go-trace"
	"godep.lzd.co/mobapi_lib/cache/inmem"
	ctxmanager "godep.lzd.co/mobapi_lib/context"
	"godep.lzd.co/mobapi_lib/handler"
	"godep.lzd.co/mobapi_lib/logger"
	"godep.lzd.co/mobapi_lib/utils"
	"mobile_search_api/api/core/monitoring"
	core_utils "mobile_search_api/api/core/utils"
	"mobile_search_api/api/ext_services/nobalancer"
	"mobile_search_api/api/ext_services/service_error"
	"mobile_search_api/srv/product"
)

type SponsoredProductsAPI struct {
	client nobalancer_client.IExtAPIClient
}

func NewSponsoredProductsAPIClient() *SponsoredProductsAPI {
	serviceName := "sponsored_products_api"

	transport := &http.Transport{
		MaxIdleConnsPerHost: 20,
		TLSClientConfig:     &tls.Config{InsecureSkipVerify: true},
	}
	client := &http.Client{
		Transport: transport,
		Timeout:   RecommendDefaultRequestTimeout,
	}

	callbacks := nobalancer_client.Callbacks{
		OnPrepareRequest: func(ctx context.Context, req *http.Request, data interface{}) context.Context {
			b, _ := httputil.DumpRequest(req, true)
			logger.Debug(ctx, "[SponsoredProducts] Request: %s", string(b))

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
			if err := nobalancer_client.Unmarshal(result, buf); err != nil {
				return fmt.Errorf("request %q failed to decode response data %+v: %v", req.URL.RequestURI(), result, err)
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

	return &SponsoredProductsAPI{client: nobalancer_client.NewExtAPIClient(client, callbacks, inmem.NewFromFlags("sponsored-products-api"))}
}

func (opts *RecommendationOpts) FillSponsoredProductsConfig(config *RecommendationConfig) {
	opts.ClientID = config.SponsoredProducts.APIClientID
	opts.Endpoint = config.SponsoredProducts.APILink
	opts.AppID = config.SponsoredProducts.APIKey
}

func (opts *RecommendationOpts) SponsoredProductsURL() string {
	values := url.Values{}

	if opts.AppID != "" {
		values.Add("apikey", opts.AppID)
	}
	if opts.ClientID != "" {
		values.Add("clientid", opts.ClientID)
	}

	if opts.CustomerID != 0 {
		values.Add("customerid", strconv.FormatUint(opts.CustomerID, 10))
	} else {
		values.Add("customerid", opts.AdID)
	}

	if opts.Venture != "" {
		values.Add("venture", opts.Venture)
	}
	if len(opts.Placements) > 0 {
		values.Add("placements", strings.Join(opts.Placements, ","))
	}

	if opts.SimpleSKU != "" {
		values.Add("sku", opts.SimpleSKU)
	}
	if len(opts.RegionalKeys) > 0 {
		values.Add("regional_key", strings.Join(opts.RegionalKeys, ","))
	}
	if opts.BrandID != 0 {
		values.Add("brand_id", strconv.FormatUint(opts.BrandID, 10))
	}
	if opts.SellerID != 0 {
		values.Add("seller_id", strconv.FormatUint(opts.SellerID, 10))
	}
	if opts.Lang != "" {
		values.Add("lang", opts.Lang)
	}
	return strings.TrimSuffix(opts.Endpoint, "/") + "/sponsored?" + values.Encode()
}

// easyjson:json
type RecommendedBySponsoredProductsResponse []RecommendationBySponsoredProducts

type RecommendationBySponsoredProducts struct {
	Placement string                         `json:"placement_name"`
	Heading   string                         `json:"strat_message"`
	Products  []ProductByBySponsoredProducts `json:"recs"`
}

type ProductByBySponsoredProducts struct {
	SKU       string `json:"pid"`
	CtURL     string `json:"ct_url"`
	CID       string `json:"cid"`
	DID       string `json:"did"`
	SimpleSKU string `json:"simple_sku"`
	PDPSKU    string `json:"pdp_sku"`
}

func (api *SponsoredProductsAPI) GetName() string {
	return ProviderSP
}

func (api *SponsoredProductsAPI) SetRequestTimeout(timeout time.Duration) {
	api.client.SetRequestTimeout(timeout)
}

func (api *SponsoredProductsAPI) GetRecommendations(ctx context.Context, config *RecommendationConfig, opts *RecommendationOpts) ([]RecommendationItem, error) {
	opts.FillSponsoredProductsConfig(config)
	var entry = cache.CacheEntry{Body: &RecommendedBySponsoredProductsResponse{}}

	err := api.client.GetWithCache(ctx, opts.SponsoredProductsURL(), &entry, nil)
	if err != nil {
		return nil, err
	}

	if result, ok := entry.Body.(*RecommendedBySponsoredProductsResponse); ok {
		return convertResponseBySponsoredProducts(*result), nil
	}

	return nil, nil
}

func convertResponseBySponsoredProducts(response RecommendedBySponsoredProductsResponse) []RecommendationItem {
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
			sku, err := core_utils.ConfigSkuBySimpleSku(rProduct.SKU)
			if err != nil {
				continue
			}
			result[i].Products = append(result[i].Products, product_service.Product{
				SKU:       sku,
				CtURL:     rProduct.CtURL,
				CID:       rProduct.CID,
				DID:       rProduct.DID,
				SimpleSKU: rProduct.SimpleSKU,
				PDPSKU:    rProduct.PDPSKU,
			})
		}
	}
	return result
}
