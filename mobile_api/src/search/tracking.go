package search_service

import (
	"context"
	"crypto/md5"
	"encoding/hex"
	"fmt"
	"math/rand"
	"strconv"
	"strings"
	"time"

	ctxmanager "godep.lzd.co/mobapi_lib/context"
	"godep.lzd.co/mobapi_lib/token"

	"mobile_search_api/api/ext_services/content_api/model/search"
	"mobile_search_api/srv/search_opts"
)

var userAgentSubstringToApp = map[string]string{
	"android": "android",
	"okhttp":  "android",
	"ipod":    "ipod",
	"ipad":    "ipad",
	"iphone":  "iphone",
}

type TrackingData struct {
	api ITrackingAPI

	RN            string `json:"rn"`
	ResultType    string `json:"internalsearchresulttype"`
	Query         string `json:"internalsearchterm"`
	SKUs          string `json:"auctions"`
	BucketID      string `json:"bucket_id"`
	Timestamp     int64  `json:"timestamp"`
	Filters       string `json:"tag_nav"`
	CategoryID    string `json:"cat_nav"`
	ClientURL     string `json:"wsurl"`
	ServerURL     string `json:"search_url"`
	IP            string `json:"ip"`
	Sort          string `json:"sort"`
	TotalProducts uint64 `json:"hits"`
	CountOnPage   int    `json:"count"`
	App           string `json:"app"`
	CustomerID    uint64 `json:"user_id"`
	AdjustID      string `json:"adjust_id"`
	Lang          string `json:"language"`
	Venture       string `json:"country"`
	IsStressTest  bool   `json:"is_stress_test"`
}

func (service *SearchService) NewTrackingData() *TrackingData {
	return &TrackingData{
		api:       service.trackingAPI,
		Timestamp: time.Now().Unix(),
	}
}

func (data *TrackingData) Send(ctx context.Context) {
	data.api.SendData(ctx, data)
}

func (data *TrackingData) FillBySearchResponse(response search.Response) {
	data.SKUs = convertHitsToAuctions(response.Hits)
	data.CategoryID = findCategoryIDInRequestDump(response.RequestDump)
	data.TotalProducts = uint64(response.Total)
	data.CountOnPage = len(response.Hits)
}

func (data *TrackingData) FillByContext(ctx context.Context) {
	data.RN = generateRN(ctx)
	if ctxData, err := ctxmanager.FromContext(ctx); err == nil {
		data.ClientURL = ctxData.ReqURI
		data.IP = ctxData.ReqRemoteAddr
		data.AdjustID = ctxData.ReqClientId
		data.App = findApp(ctxData.ReqUserAgent)
	}
}

func (data *TrackingData) FillBySearchOpts(opts *search_opts.SearchOpts) {
	data.Query = opts.Query
	data.BucketID = opts.GetBucketID()
	data.Filters = opts.Filters
	data.ServerURL = "/search_get/?" + opts.Generate().Encode()
	data.Sort = opts.Sort
	data.Lang = opts.Lang
	data.IsStressTest = opts.IsStressTest
}

func (data *TrackingData) FillByApiToken(apiToken token.INullToken) {
	if apiToken != nil && !apiToken.IsGuest() {
		data.CustomerID = apiToken.GetCustomerID()
	}
}

func (data *TrackingData) FillResultType(redirectExists bool) {
	if redirectExists {
		data.ResultType = "redirect"
	} else if data.BucketID == search_opts.BucketIDDefault || data.BucketID == search_opts.BucketID0 || data.BucketID == search_opts.BucketID1 || data.BucketID == search_opts.BucketID2 || data.BucketID == search_opts.BucketID3 {
		data.ResultType = "ha3"
	} else {
		data.ResultType = "es"
	}
}

func findApp(userAgent string) string {
	userAgent = strings.ToLower(userAgent)
	for sub, app := range userAgentSubstringToApp {
		if strings.Contains(userAgent, sub) {
			return app
		}
	}
	return userAgent
}

func findCategoryIDInRequestDump(dump *search.RequestDump) string {
	if dump == nil {
		return ""
	}
	if values, ok := dump.Facets["category"]; ok && len(values) > 0 {
		return strings.Join(values, ",")
	}
	return ""
}

func convertHitsToAuctions(products []search.Product) string {
	result := make([]string, len(products))
	for i := range products {
		price := float64(products[i].Meta.Price)
		if products[i].Meta.SpecialPrice != nil && float64(*products[i].Meta.SpecialPrice) < price {
			price = float64(*products[i].Meta.SpecialPrice)
		}
		result[i] = fmt.Sprintf("%s:%0.2f:%d", products[i].SKU, price, products[i].PrimaryCategory)
	}
	return strings.Join(result, ",")
}

func generateRN(ctx context.Context) string {
	now := time.Now()
	rand.Seed(now.Unix())

	ip := ""
	if ctxData, err := ctxmanager.FromContext(ctx); err == nil {
		ip = ctxData.ReqRemoteAddr
	}
	original := ip + now.Format("20060102150405") + strconv.FormatInt(now.UnixNano()/int64(time.Millisecond), 10) + strconv.FormatInt(int64(rand.Intn(1000000000)), 10)

	hasher := md5.New()
	hasher.Write([]byte(original))
	return hex.EncodeToString(hasher.Sum(nil))
}
