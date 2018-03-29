package main

import (
	"bytes"
	"flag"
	"fmt"
	"io"
	"io/ioutil"
	"net/http"
	"net/url"
	"os"
	"regexp"
	"strings"
	"sync"
	"time"

	"github.com/PuerkitoBio/goquery"
)

var (
	re  = regexp.MustCompile("[^a-zA-Z0-9=?./]+")
	re2 = regexp.MustCompile("/+")
)

const (
	maxRoutines   = 10
	DataDir       = "" // "data/"
	MaxLenDirName = 250
)

func main() {
	flag.Parse()

	args := flag.Args()
	fmt.Println(args)
	if len(args) < 1 {
		fmt.Println("Please specify start page")
		os.Exit(1)
	}

	q, err := NewQueue(args[0])
	if err != nil {
		fmt.Println("Please specify start page")
		os.Exit(1)
	}

	t, l := q.Stat()
	fmt.Printf("Stat before start total: %d, queue: %d\n", t, l)
	var wg sync.WaitGroup
	for i := 0; i < maxRoutines; i++ {
		wg.Add(1)
		go func(num int, q *Queue) {
			defer wg.Done()
			for u := range q.Queue {
				fmt.Printf("%d processing: %s\n", num, u)

				urls, err := parseUrl(u)
				if err != nil {
					fmt.Printf("%d fail: %s, err: %v\n", num, u, err)
				}
				for _, u := range urls {
					q.AddToQueue(u)
				}
				q.Parsed(u)
			}
		}(i, q)
	}

	for !q.IsOver() {
		time.Sleep(time.Second)
		t, l = q.Stat()
		fmt.Printf("Stat in progress total: %d, queue: %d\n", t, l)
	}
	t, l = q.Stat()
	fmt.Printf("Stat finish total: %d, queue: %d\n", t, l)
	q.Close()
	wg.Wait()
}

// ---------------- Utils -----------------

func parseUrl(u string) ([]string, error) {
	res, err := http.Get(u)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()
	if res.StatusCode != 200 {
		return nil, fmt.Errorf("status code error: %d %s", res.StatusCode, res.Status)
	}

	var buf bytes.Buffer
	tee := io.TeeReader(res.Body, &buf)

	doc, err := goquery.NewDocumentFromReader(tee)
	if err != nil {
		return nil, err
	}

	result := []string{}
	doc.Find("a").Each(func(i int, s *goquery.Selection) {
		href, _ := s.Attr("href")
		if newUrl := fixUrl(href, u); newUrl != "" {
			result = append(result, newUrl)
		}
	})

	if err = saveFile(u, &buf); err != nil {
		return result, err
	}

	return result, nil
}

func fixUrl(target, base string) string {
	u, err := url.Parse(target)
	if err != nil {
		return ""
	}
	baseUrl, err := url.Parse(base)
	if err != nil {
		return ""
	}
	u = baseUrl.ResolveReference(u)
	resolvedTarger := u.String()
	if strings.HasPrefix(resolvedTarger, base) {
		return strings.Trim(resolvedTarger, "/")
	}
	return ""
}

func saveFile(u string, body io.Reader) error {
	realFilename, err := filenameFromUrl(DataDir, u)
	if err != nil {
		return err
	}
	file, err := os.Create(realFilename)
	if err != nil {
		return err
	}
	defer file.Close()
	size, err := io.Copy(file, body)
	if err != nil {
		return err
	}
	if size == 0 {
		return fmt.Errorf("file empty")
	}
	return nil
}

func filenameFromUrl(baseDir string, u string) (string, error) {
	dir, filename := checkPath(u, MaxLenDirName)
	dir = baseDir + dir
	if err := os.MkdirAll(dir, 0777); err != nil {
		return "", fmt.Errorf("Error create dir '%s': %v", dir, err)
	}
	return dir + "/" + filename, nil
}

func checkPath(path string, maxLenDirName int) (string, string) {
	parts := strings.Split(strings.Trim(re2.ReplaceAllString(re.ReplaceAllString(path, "_"), "/"), "/"), "/")
	finalParts := make([]string, 0, len(parts))
	for i := range parts {
		part := parts[i]
		for len(part) > maxLenDirName {
			finalParts = append(finalParts, part[:maxLenDirName])
			part = part[maxLenDirName:]
		}
		finalParts = append(finalParts, part)
	}
	if len(finalParts) > 1 {
		return strings.Join(finalParts[:len(finalParts)-1], "/"), finalParts[len(finalParts)-1] + "_"
	}
	return "", finalParts[0] + "_"
}

// -------------- Queue -----------

type Queue struct {
	Queue chan string

	found  *UrlList
	parsed *UrlList
}

func NewQueue(baseUrl string) (*Queue, error) {
	found, err := NewUrlList(baseUrl, "found")
	if err != nil {
		return nil, err
	}
	parsed, err := NewUrlList(baseUrl, "parsed")
	if err != nil {
		return nil, err
	}

	q := &Queue{
		Queue:  make(chan string, 0),
		found:  found,
		parsed: parsed,
	}
	if found.Len() == 0 {
		q.AddToQueue(baseUrl)
	} else if found.Len() > parsed.Len() {
		toQueue := found.Diff(parsed)
		q.Queue = make(chan string, len(toQueue))
		for i := range toQueue {
			q.Queue <- toQueue[i]
			fmt.Printf("add to queue: '%s'\n", toQueue[i])
		}
	}
	return q, nil
}

func (q *Queue) AddToQueue(u string) {
	if !q.found.Check(u) {
		fmt.Printf("add to queue: '%s'\n", u)
		if err := q.found.Store(u); err != nil {
			fmt.Printf("fail add '%s' to found list: err: %v\n", u, err)
		}
		go func() { q.Queue <- u }()
	}
}

func (q *Queue) Parsed(u string) {
	if err := q.parsed.Store(u); err != nil {
		fmt.Printf("fail add '%s' to parsed list: err: %v\n", u, err)
	}
}

func (q *Queue) IsOver() bool {
	return q.found.Len() <= q.parsed.Len()
}

func (q *Queue) Stat() (int, int) {
	return q.found.Len(), q.found.Len() - q.parsed.Len()
}

func (q *Queue) Close() {
	close(q.Queue)
	if err := q.found.Close(); err != nil {
		fmt.Printf("Error closing found list: %v\n", err)
	}
	if err := q.parsed.Close(); err != nil {
		fmt.Printf("Error closing parsed list: %v\n", err)
	}
}

// -------------- UrlList --------------

type UrlList struct {
	mx   sync.RWMutex
	m    map[string]bool
	file *os.File
}

func NewUrlList(baseUrl, filename string) (*UrlList, error) {
	realFilename, err := filenameFromUrl(DataDir, filename+"_"+baseUrl)
	if err != nil {
		return nil, err
	}
	f, err := os.OpenFile(realFilename, os.O_APPEND|os.O_CREATE|os.O_RDWR, 0644)
	if err != nil {
		return nil, err
	}
	b, err := ioutil.ReadAll(f)
	if err != nil {
		return nil, err
	}
	list := strings.Split(string(b), "\n")
	m := make(map[string]bool, len(list))
	for _, u := range list {
		if u != "" {
			m[u] = true
		}
	}
	return &UrlList{
		m:    m,
		file: f,
	}, nil
}

func (c *UrlList) Check(key string) bool {
	c.mx.RLock()
	_, ok := c.m[key]
	defer c.mx.RUnlock()
	return ok
}

func (c *UrlList) Len() int {
	c.mx.RLock()
	size := len(c.m)
	defer c.mx.RUnlock()
	return size
}

func (c *UrlList) Store(key string) error {
	c.mx.Lock()
	c.m[key] = true
	defer c.mx.Unlock()
	if _, err := c.file.Write([]byte(key + "\n")); err != nil {
		return err
	}
	return nil
}

func (c *UrlList) Diff(l *UrlList) []string {
	res := make([]string, 0, c.Len()-l.Len())
	for key := range c.m {
		if _, exists := l.m[key]; !exists {
			res = append(res, key)
		}
	}
	return res
}

func (c *UrlList) Close() error {
	if c.file != nil {
		return c.file.Close()
	}
	return nil
}
