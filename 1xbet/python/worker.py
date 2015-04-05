# -*- coding: utf-8 -*-
###
# Load common and detail pages of 1xbet-live
###

import threading
import traceback
import logging
from time import time, sleep
import pycurl
import StringIO
import json
import memcache
import redis

class AbstractWorker(threading.Thread):

    def __init__(self, name, config, store):
        threading.Thread.__init__(self)
        self.name    = name
        self.config  = config
        self.running = True
        self.proxies = []
        self.proxy_index = 0
        self.store = store
        self.curl = None
        self.actuality = time()
        self.errors = 0

        try:
            self.prepare_proxy()
        except Exception as e:
            logging.critical('%s. Error preparing proxy. %s: %s', self.name, str(type(e)), str(e))
            self.proxies = []

    def run(self):
        logging.info('%s. Starting', self.name)
        self.running = True
        while self.running is True:
            try:
                self.loadPage()
            except KeyboardInterrupt:  # for debug flag
                pass
            except Exception as e:
                logging.critical('%s. Error spawning. %s: %s', self.name, str(type(e)), str(e))
                logging.critical(traceback.format_exc())

            #logging.info('%s sleep 1 sec ...', self.name)
            sleep(3)

        logging.info('%s. Shutting down', self.name)
        return False

    def loadPage(self):
        pass

    def stop(self):
        logging.info('%s. Stoping. %s', self.name, 'Errors %s' % self.errors if self.errors > 0 else '')
        self.running = False

    def get_curl(self, url):
        if self.curl is None:
            c = pycurl.Curl()
            c.setopt(pycurl.FOLLOWLOCATION, True)
            c.setopt(pycurl.SSL_VERIFYPEER, False)
            c.setopt(pycurl.CONNECTTIMEOUT, self.config['1xbet']['connection_timeout'])
            c.setopt(pycurl.TIMEOUT, self.config['1xbet']['load_timeout'])
            c.setopt(pycurl.HTTPHEADER, self.config['1xbet']['headers'])
            c.setopt(pycurl.HEADER, False)
            c.setopt(pycurl.ENCODING, 'gzip,deflate')
            #c.setopt(pycurl.IPRESOLVE, pycurl.IPRESOLVE_V4)
        else:
            c = self.curl

        c.setopt(pycurl.URL, url)
        if self.proxies:
            proxy = self.get_proxy()
            c.setopt(pycurl.PROXYTYPE, proxy['type'])
            if proxy['auth'] is not None:
                c.setopt(pycurl.PROXYAUTH, pycurl.HTTPAUTH_BASIC)
                c.setopt(pycurl.PROXYUSERPWD, proxy['auth'])
            c.setopt(pycurl.PROXY, proxy['addr'])

        self.curl = c
        return self.curl

    def get_proxy(self):
        self.proxy_index = (self.proxy_index + 1) % len(self.proxies)
        return self.proxies[self.proxy_index]

    def get_used_proxy_name(self):
        return self.proxies[self.proxy_index]['name']

    def prepare_proxy(self):
        self.proxies = []
        for proxy_name in self.config['1xbet']['use_proxy']:
            if proxy_name in self.config['proxy']:
                res = {
                    'name': proxy_name,
                    'type': None,
                    'auth': None,
                    'addr': None,
                }
                proxy_parts = self.config['proxy'][proxy_name].split("://")
                if proxy_parts[0] == 'socks5':
                    res['type'] = pycurl.PROXYTYPE_SOCKS5
                else:
                    res['type'] = pycurl.PROXYTYPE_HTTP

                if '@' in proxy_parts[1]:
                    proxy_parts2 = proxy_parts[1].split("@")
                    res['auth'] = proxy_parts2[0]
                    res['addr'] = proxy_parts2[1]
                else:
                    res['addr'] = proxy_parts[1]

                self.proxies.append(res)

        return len(self.proxies) > 0

    def save_data(self, value):
        val = json.dumps({
            'page': value,
            'actuality': self.actuality,
        })

        if isinstance(self.store, memcache.Client):
            cache_key = '%s-%s_%s' % (self.config['memcache']['key'], self.name, self.config['memcache']['suffix'])
            self.store.set(cache_key, val, 60, 0)
        elif isinstance(self.store, redis.Redis):
            cache_key = '%s-%s' % (self.config['redis']['key'], self.name)
            self.store.set(cache_key, val)
            self.store.expire(cache_key, 60)
        else:
            logging.critical('%s. Unsupported store type: %s', self.name, str(type(self.store)))

    def get_errors(self):
        return self.errors

    def get_actuality(self):
        return self.actuality

###
# Воркер для загруки основной страницы лайва 1xbet
###
class CommonPageWorker(AbstractWorker):

    def __init__(self, name, config, mc):
        AbstractWorker.__init__(self, name, config, mc)
        self.event_ids = []

    def get_url(self):
        return self.config['1xbet']['common_url'] % self.config['1xbet']['host']

    def loadPage(self):
        try:
            c = self.get_curl(self.get_url())
            b = StringIO.StringIO()
            c.setopt(pycurl.WRITEFUNCTION, b.write)
            c.perform()
            result = b.getvalue()
            code = c.getinfo(pycurl.HTTP_CODE)
            b.close()
        except Exception as e:
            self.errors += 1
            logging.critical('%s. Error loading %s. %s: %s', self.name, self.get_used_proxy_name(), str(type(e)), str(e))
            return None

        self.actuality = time()

        if code == 200:
            self.parse_data(result)
            #logging.info('%s. code: %s, len: %s, subgames: %s, proxy: %s. OK', self.name, code, len(result), len(self.event_ids), self.get_used_proxy_name())
        else:
            self.errors += 1
            #logging.info('%s. code: %s, len: %s, proxy: %s. Some problems', self.name, code, len(result), self.get_used_proxy_name())

    def parse_data(self, result):
        data = json.loads(result)
        if 'Value' in data and 'Success' in data and data['Success'] is True:
            self.errors = 0
            self.event_ids = []
            for event in data['Value']:
                if type(event) is dict:
                    for subgame in event['SubGames']:
                        self.event_ids.append(subgame['GameId'])
                else:
                    logging.info('%s returns wrong answer', self.name)
                    self.errors += 1
        else:
            self.errors += 1

        if self.errors <= 0:
            self.save_data(result)

    def get_event_ids(self):
        return self.event_ids

###
# Воркер для загруки детальной страницы подсобытия лайва 1xbet
###
class DetailPageWorker(AbstractWorker):

    def __init__(self, name, event_id, config, mc):
        AbstractWorker.__init__(self, '%s-%s' % (name, event_id), config, mc)
        self.event_id = event_id

    def get_url(self):
        return self.config['1xbet']['detail_url'] % (self.config['1xbet']['host'], self.event_id)

    def loadPage(self):
        try:
            c = self.get_curl(self.get_url())
            b = StringIO.StringIO()
            c.setopt(pycurl.WRITEFUNCTION, b.write)
            c.perform()
            result = b.getvalue()
            code = c.getinfo(pycurl.HTTP_CODE)
            b.close()
        except Exception as e:
            self.errors += 1
            logging.critical('%s. Error loading %s. %s: %s', self.name, self.get_used_proxy_name(), str(type(e)), str(e))
            return None

        self.actuality = time()

        if code == 200:
            self.parse_data(result)
            #logging.info('%s. code: %s, len: %s, proxy: %s. OK', self.name, code, len(result), self.get_used_proxy_name())
        else:
            self.errors += 1
            #logging.info('%s. code: %s, len: %s, proxy: %s. Some problems', self.name, code, len(result), self.get_used_proxy_name())

    def parse_data(self, result):
        data = json.loads(result)
        if 'Value' in data and 'Success' in data and data['Success'] is True:
            self.errors = 0
        else:
            self.errors += 1

        if self.errors <= 0:
            self.save_data(result)
