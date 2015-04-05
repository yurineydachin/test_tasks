# -*- coding: utf-8 -*-

import os
import json
import logging
import argparse

from util import Singleton

class Bet365ScanConfig(object):
    __metaclass__ = Singleton

    _config_filename = '/var/log/betmill/scanners/Bet365Flash/Live/bet365scan.conf'

    _config = {
        'pidfile': '/var/log/betmill/scanners/pid/bet365scan.pid',
        'debug': False,

        'log_level': logging.INFO,
        'log_file': '/var/log/betmill/scanners/Bet365Flash/Live/bet365scan.log',
        'log_format': '%(asctime)s - %(levelname)s - %(message)s',
        'log_dateformat': '%Y-%m-%d %H:%M:%S',

        'bet365_server': {
            'host': '5.226.180.9',
            'port': 843,
            'topics': [
                'OV_1_1_3//',    # SportTypes::SPORT_SOCCER,
                                 # 2   horse racing,
                                 # 3   SportTypes::SPORT_CRICKET,
                                 # 4   greyhounds,
                                 # 6   lotto,
                'OV_8_1_3//',    # SportTypes::SPORT_RUGBY,          //Rugby Union,
                'OV_7_1_3//',    # SportTypes::SPORT_GOLF,
                'OV_9_1_3//',    # SportTypes::SPORT_BOX,
                'OV_10_1_3//',   # SportTypes::SPORT_FORMULA1,
                                 # 11  Athletics,
                'OV_12_1_3//',   # SportTypes::SPORT_FOOTBALL,
                'OV_13_1_3//',   # SportTypes::SPORT_TENNIS,
                'OV_14_1_3//',   # SportTypes::SPORT_SNOOKER,
                'OV_15_1_3//',   # SportTypes::SPORT_DARTS,
                'OV_16_1_3//',   # SportTypes::SPORT_BASEBALL,
                'OV_17_1_3//',   # SportTypes::SPORT_HOCKEY,
                'OV_18_1_3//',   # SportTypes::SPORT_BASKETBALL,
                'OV_19_1_3//',   # SportTypes::SPORT_RUGBY,          //Rugby League,
                'OV_27_1_3//',   # SportTypes::SPORT_AUTO_MOTOSPORT, //Motorbikes,
                'OV_35_1_3//',   # SportTypes::SPORT_BILLIARDS,      //Pool,
                'OV_36_1_3//',   # SportTypes::SPORT_AUSSIE_RULES,
                'OV_38_1_3//',   # SportTypes::SPORT_CYCLE_RACING,    //cycling
                                 # 66  bowls
                'OV_78_1_3//',   # SportTypes::SPORT_HANDBALL,
                                 # 88  Trotting,
                'OV_83_1_3//',   # SportTypes::SPORT_FUTSAL,
                'OV_84_1_3//',   # SportTypes::SPORT_BALL_HOCKEY,
                'OV_91_1_3//',   # SportTypes::SPORT_VOLLEYBALL,
                'OV_92_1_3//',   # SportTypes::SPORT_TABLE_TENNIS,
                'OV_94_1_3//',   # SportTypes::SPORT_BADMINTON,
                'OV_95_1_3//',   # SportTypes::SPORT_BEACH_VOLLEYBALL,
                'OV_98_1_3//',   # SportTypes::SPORT_CURLING,
                'OV_118_1_3//',  # SportTypes::SPORT_ALPINE_SKIING,
                'OV_119_1_3//',  # SportTypes::SPORT_BIATHLON,
                'OV_121_1_3//',  # SportTypes::SPORT_NORDIC_COMBINED,
                'OV_122_1_3//',  # SportTypes::SPORT_CROSS_COUNTRY,
                'OV_123_1_3//',  # SportTypes::SPORT_SKI_JUMPING,
                'OV_124_1_3//',  # SportTypes::SPORT_LUGE,
                'OV_125_1_3//',  # SportTypes::SPORT_SKATING,
                'OV_127_1_3//',  # SportTypes::SPORT_SKELETON,
                'OV_138_1_3//',  # SportTypes::SPORT_FREESTYLE,
                'OV_139_1_3//',  # SportTypes::SPORT_SNOWBOARD,
            ],
            'cascade_subscribe': True,
        },

        'telnet': {
            'enable': True,
            'host': 'localhost',
            'port': 9000,
        },

        'redis': {
            'host': 'localhost',
            'key': 'bet365liveflash',
            'loading_events': 1.5, # if loading_events >= interval => never sleep
            'interval': 2,
            'timeout': 10,
            'reconnect': 30,
        }
    }

    def __init__(self, filename=None, debug=None):
        if filename is None:
            filename = self._config_filename
        if not os.path.exists(filename):
            logging.debug("there is no config file at place `%s', trying to create", filename)
            fd = None
            try:
                fd = open(filename, 'w')
                json.dump(self._config, fd, indent=4)
            except IOError, e:
                logging.critical("error creating config file `%s': %s", filename, unicode(e))
            finally:
                if fd is not None:
                    fd.close()
        else:
            with open(filename, 'r') as fd:
                self._config = json.load(fd)

        if debug is not None:
            self._config['debug'] = debug

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
        aparse.add_argument('-c', '--config',
                            default=cls._config_filename,
                            dest='config',
                            help='Config file path (' + cls._config_filename + ' by default)')
        aparse.add_argument('-d', '--debug',
                            action='store_true',
                            dest='debug',
                            help='Debug mode')
        return aparse.parse_args()
