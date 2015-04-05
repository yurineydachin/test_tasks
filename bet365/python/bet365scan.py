#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import sys
import logging
import time
import threading

from daemon import Daemon
from config import Bet365ScanConfig
from util import str_to_boolean
from bet365serverlistener import Bet365ServerListener, Bet365ServerListenerRestarter
from telnet import Bet365ScanTelnet

def main():
    cmd = Bet365ScanConfig.parse_cmd()
    Bet365ScanConfig(filename=cmd.config, debug=cmd.debug)  # init config just once
    daemon = Bet365ScanDaemon()

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
        daemon = Bet365ScanDaemon()
        print 'Starting %s...' % sys.argv[0]
        daemon.start()
    elif cmd.command == 'status':
        if daemon.pidfile.is_running():
            sys.exit("%s is running with PID %s" % (sys.argv[0], daemon.pidfile.pid))
        else:
            sys.exit("%s is not running" % sys.argv[0])


class Bet365ScanDaemon(Daemon):
    def __init__(self):
        self._worker = None
        self._worker_restarter = None
        self._telnet = None

        logging.basicConfig(
            level=Bet365ScanConfig()['log_level'],
            filename=Bet365ScanConfig()['log_file'],
            format=Bet365ScanConfig()['log_format'],
            datefmt=Bet365ScanConfig()['log_dateformat']
        )

        Daemon.__init__(self, Bet365ScanConfig()['pidfile'],
                        debug=Bet365ScanConfig()['debug'])

    def run(self):
        logging.info('%s Starting Bet365ScanDaemon' % os.getpid())

        try:
            lock = threading.Lock()
            self._worker = Bet365ServerListener('bet365_server', Bet365ScanConfig()['bet365_server'], lock)

            '''
            try:
                self._worker_restarter = Bet365ServerListenerRestarter(self._worker)
                self._worker_restarter.start()
                #pass
            except Exception as e:
                logging.error('Error starting Bet365ServerListenerRestarter %s: %s', type(e).__name__, e)

            try:
                if str_to_boolean(Bet365ScanConfig()['telnet']['enable']):
                    self._telnet = Bet365ScanTelnet(self._worker)
                    self._telnet.start()
                else:
                    logging.info('telnet server disabled')
            except Exception as e:
                logging.error('Error starting telnet %s: %s', type(e).__name__, e)
            '''

            self._worker.run()
        except Exception as e:
            logging.critical("Failed to run listener. %s: %s", type(e).__name__, str(e));
        finally:
            logging.info('%s finally. stop worker and telnet' % os.getpid())
            if self._worker is not None:
                self._worker.stop()
            if self._telnet is not None:
                self._telnet.shutdown()

    def shutdown(self):
        logging.info('%s Shutting down Bet365ScanDaemon' % os.getpid())
        if self._worker_restarter:
            self._worker_restarter.stop()
        if self._worker:
            self._worker.stop()
        logging.info('%s Shutted down Bet365ScanDaemon' % os.getpid())


if __name__ == "__main__":
    main()
