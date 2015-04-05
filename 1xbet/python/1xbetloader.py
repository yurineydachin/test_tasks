#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import sys
import logging
import time
import threading
import memcache
import redis
import traceback
import subprocess
import gc

from daemon import Daemon
from config import XbetLoaderConfig
from worker import CommonPageWorker, DetailPageWorker

def main():
    cmd = XbetLoaderConfig.parse_cmd()
    XbetLoaderConfig(debug=cmd.debug, forks=cmd.forks, number=cmd.number)  # init config just once
    daemon = XbetLoaderDaemon()

    if cmd.command == 'start':
        print 'Starting %s' % sys.argv[0]
        daemon.start()
    elif cmd.command == 'stop':
        print 'Stopping %s' % sys.argv[0]
        daemon.stop()
    elif cmd.command == 'restart':
        print 'Stopping %s...' % sys.argv[0]
        daemon.stop()
        time.sleep(1)
        del daemon
        daemon = XbetLoaderDaemon()
        print 'Starting %s...' % sys.argv[0]
        daemon.start()
    elif cmd.command == 'status':
        if daemon.pidfile.is_running():
            sys.exit("%s is running with PID %s" % (sys.argv[0], daemon.pidfile.pid))
        else:
            sys.exit("%s is not running" % sys.argv[0])


class XbetLoaderDaemon(Daemon):
    def __init__(self):
        self.running = True
        self.mc = None
        self.redis = None
        self._common_worker = None
        self._detail_workers = {}
        self.config = XbetLoaderConfig()
        self.cycle = 1

        logging.basicConfig(
            level=self.config['log_level'],
            filename=self.config['log_file'],
            format=self.config['log_format'],
            datefmt=self.config['log_dateformat']
        )

        Daemon.__init__(self, self.config['pidfile'],
                        debug=self.config['debug'])

    def run(self):
        logging.info('%s Starting XbetLoaderDaemon %s - %s', os.getpid(), self.config['forks'], self.config['number'])
        self.cycle = 1

        try:
            self.mc = memcache.Client(self.config['memcache']['host'], self.config['memcache']['port'])
            try:
                self.redis = redis.Redis(self.config['redis']['host'])
            except Exception as e:
                logging.critical("Cannot connect to Redis server")
                return False

            self._common_worker = CommonPageWorker('CommonPage', self.config, self.redis)
            self._common_worker.start()

            while self.running is True:
                self.check_workers()
                time.sleep(1)
                self.cycle += 1

        except Exception as e:
            logging.critical("Failed to run XbetLoaderDaemon. %s: %s", type(e).__name__, str(e));
            logging.critical(traceback.format_exc())
        finally:
            logging.info('%s finally. Stoping workers', os.getpid())
            if self._common_worker is not None:
                self._common_worker.stop()
            for event_id in self._detail_workers.keys():
                self._detail_workers[event_id].stop()

    def check_workers(self):
        actuality_events = [time.time() - self._common_worker.get_actuality()]
        stopped = 0
        for event_id in self._detail_workers.keys():
            if event_id not in self._common_worker.get_event_ids():
                self._detail_workers[event_id].stop()
                del(self._detail_workers[event_id])
                stopped += 1

        for event_id in self._common_worker.get_event_ids():
            if event_id not in self._detail_workers:
                if event_id % self.config['forks'] == self.config['number']:
                    self._detail_workers[event_id] = DetailPageWorker('Event', event_id, self.config, self.redis)
                    self._detail_workers[event_id].start()
                else:
                    continue

            if self._detail_workers[event_id].get_errors() > 5:
                self._detail_workers[event_id].stop()
                stopped += 1
                self._detail_workers[event_id] = DetailPageWorker('Event', event_id, self.config, self.redis)
                self._detail_workers[event_id].start()

            actuality_events.append(time.time() - self._detail_workers[event_id].get_actuality())

        process_stat = self.get_process_stat()
        logging.info('STAT %s, cnt: %s, avg: %0.2f sec, oldest: %0.2f sec, cpu: %0.1f, memory: %0.2f MB', self.cycle, len(actuality_events), sum(actuality_events) / float(len(actuality_events)), max(actuality_events), float(process_stat[0]), float(process_stat[1]) / float(1024))
        if stopped:
            gc.collect()

    def get_process_stat(self):
        return subprocess.check_output('ps u -p %s | grep %s | awk \'{print $3 " " $6}\'' % (os.getpid(), os.getpid()), shell = True).strip().split(' ')

    def shutdown(self):
        self.running = False
        logging.info('%s Shutting down XbetLoaderDaemon', os.getpid())
        if self._common_worker:
            self._common_worker.stop()
        for event_id in self._detail_workers:
            self._detail_workers[event_id].stop()
        logging.info('%s Shutted down XbetLoaderDaemon', os.getpid())


if __name__ == "__main__":
    main()
