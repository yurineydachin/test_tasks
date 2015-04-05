# -*- coding: utf-8 -*-

import os
import json
import logging
import argparse
import hashlib

from util import Singleton

class XbetLoaderConfig(object):
    __metaclass__ = Singleton

    _config = {
        'pidfile': '/var/log/betmill/scanners/pid/1xbetloader%s.pid',
        'debug': False,
        'forks': 10,
        'number': 0,

        'log_level': logging.INFO,
        'log_file': '/var/log/betmill/scanners/1xbet/LiveCache/1xbetloader%s.log',
        'log_format': '%(asctime)s - %(levelname)s - %(message)s',
        'log_dateformat': '%Y-%m-%d %H:%M:%S',

        '1xbet': {
            'host': 'www.1xbet.com',
            'use_proxy': ['SCANNER_PROXY5', 'SCANNER_PROXY6', 'SCANNER_PROXY7', 'SCANNER_PROXY8', 'SCANNER_PROXY9'],
            'common_url': 'https://%s/LiveFeed/GetLeftMenu?lng=ru_RU',
            'detail_url': 'https://%s/LiveFeed/GetGame?id=%s&lng=ru_RU',
            'connection_timeout': 5,
            'load_timeout': 10,
            'headers': [
                'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding: identity',
            ],
        },
        'proxy': {
            'SCANNER_PROXY1': 'socks5://37.200.70.40:18777',
            'SCANNER_PROXY2': 'socks5://31.186.103.94:18777',
            'SCANNER_PROXY3': 'socks5://serfer:123qweasdzxc@195.208.27.4:33128',
            'SCANNER_PROXY4': 'socks5://scanner_DE:kr058nD29cA@148.251.238.233:1080',
            'SCANNER_PROXY5': 'socks5://scanner_DE:kr058nD29cA@5.9.111.198:1080',
            'SCANNER_PROXY6': 'socks5://scanner_DE:kr058nD29cA@144.76.14.205:1080',
            'SCANNER_PROXY7': 'socks5://scanner_DE:kr058nD29cA@178.63.76.195:1080',
            'SCANNER_PROXY8': 'socks5://serfer:123qweasdzxc@195.208.27.3:33128',
            'SCANNER_PROXY9': 'socks5://serfer:123qweasdzxc@195.208.27.5:33128',
            'SCANNER_PROXY10': 'socks5://scanner:kr058nD29cA@178.62.3.115:1080',
            'SCANNER_PROXY11': 'socks5://scanner_LV:kr058nD29cA@195.122.28.80:11708',
            'SCANNER_PROXY12': 'socks5://89.221.57.6:59080',
        },
        'memcache': {
            'host': '127.0.0.1',
            'port': 11211,
            'key': '1xbetloader',
            'SITE_ROOT_URL': 'http://scanfoll.crwn42.badbin.ru/',
            'DB_NAME': 'scanfoll',
            'suffix': '',
        },
        'redis': {
            'host': 'localhost',
            'key': '1xbetloader',
        }
    }

    def __init__(self, debug=None, forks=None, number=None):
        if debug is not None:
            self._config['debug'] = debug
        if forks is not None:
            self._config['forks'] = forks
        if number is not None:
            self._config['number'] = number

        self._config['pidfile'] = self._config['pidfile'] % self._config['number']
        self._config['log_file'] = self._config['log_file'] % self._config['number']

        self._config['memcache']['suffix'] = hashlib.md5('%s%s' % (self._config['memcache']['SITE_ROOT_URL'], self._config['memcache']['DB_NAME'])).hexdigest()

    def __getitem__(self, index):
        return self._config[index]

    @classmethod
    def parse_cmd(cls):
        aparse = argparse.ArgumentParser()
        arg_control = aparse.add_subparsers(title='Control',
                                            description='Daemon control commands',
                                            dest='command')
        arg_control.add_parser('start',
                               help='Starts %(prog)s')
        arg_control.add_parser('stop',
                               help='Stop %(prog)s')
        arg_control.add_parser('restart',
                               help='Restarts %(prog)s')
        arg_control.add_parser('status',
                               help='Outputs %(prog)s status')
        aparse.add_argument('-d', '--debug',
                            action='store_true',
                            dest='debug',
                            help='Debug mode')
        aparse.add_argument('--forks',
                            metavar='N',
                            type=int,
                            dest='forks',
                            default=10,
                            help='Forks count')
        aparse.add_argument('--number',
                            metavar='N',
                            type=int,
                            dest='number',
                            help='Fork number. Must be in range [0, Forks count]')
        return aparse.parse_args()
