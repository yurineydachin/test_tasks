# -*- coding: utf-8 -*-

import logging
import json
import threading
import SocketServer

import util
from config import Bet365ScanConfig
from telnetsrv.threaded import TelnetHandler, command


# noinspection PyClassicStyleClass
class ThreadedTelnetServer(SocketServer.ThreadingMixIn, SocketServer.TCPServer):
    allow_reuse_address = True
    daemon_threads = True


class Bet365ScanTelnet(object):
    def __init__(self, worker):
        self._server = None
        self._server_thread = None
        self._worker = worker

    def start(self):
        address = (Bet365ScanConfig()['telnet']['host'], Bet365ScanConfig()['telnet']['port'])
        self._server = ThreadedTelnetServer(address, Bet365ScanTelnetHandler)
        self._server.worker = self._worker
        self._server_thread = threading.Thread(target=self._server.serve_forever)
        self._server_thread.daemon = True
        self._server_thread.start()
        logging.debug('telnet server thread started: %s', self._server_thread.name)
        logging.info('telnet server bound at %s:%s', address[0], address[1])

    def shutdown(self):
        self._server.shutdown()


# noinspection PyClassicStyleClass
class Bet365ScanTelnetHandler(TelnetHandler):
    def session_start(self):
        # noinspection PyAttributeOutsideInit
        self.config = {
            'raw': True
        }

    @command('cfg')
    def command_cfg(self, params):
        """
        Telnet session config operation
        Telnet session config operation
        Syntax: cfg [var [value]]
        """
        if len(params) == 0:
            self.writeutfres('Current session config state: ')
            self.writeutfres(json.dumps(self.config, indent=4))
            return

        if len(params) >= 1:
            if not params[0] in self.config:
                self.writeutfres("There is no %s variable in config" % params[0])
                return

        if len(params) == 1:
            self.writeutfres("Config value for %s: `%s'" % (params[0], self.config[params[0]]))
            return

        if len(params) == 2:
            if isinstance(self.config[params[0]], bool):
                self.config[params[0]] = util.str_to_boolean(params[1])
            else:
                self.config[params[0]] = params[1]

            self.writeutfres("Config value for %s: `%s'" % (params[0], self.config[params[0]]))
            return

    # noinspection PyUnusedLocal
    @command('status')
    def command_status(self, params):
        """
        Displays server status
        """
        self.writeutfres('Subscribed to:')
        for topic in self.server.worker.subscribed:
            self.writeutfres(topic)

    @command('sub')
    def command_sub(self, params):
        """
        Subscribes a topic
        """

        if len(params) == 0:
            self.writeerror("No topic name given")
            return

        self.writeutfres('Subscribing to topic %s' % params[0])
        try:
            self.server.worker.subscribe(params[0])
        except Bet365AlreadySubscribedException:
            self.writeutfres("Already subscribed to topic %s" % params[0])

    @command('unsub')
    def command_unsub(self, params):
        """
        Unsubscribes a topic
        """

        if len(params) == 0:
            self.writeerror("No topic name given")
            return

        if params[0] not in self.server.worker.subscribed:
            self.writeerror("No topic %s subscribed" % params[0])
            return

        self.writeutfres('Unsubscribing from to topic %s' % params[0])
        self.server.worker.unsubscribe(params[0])

    @command('itl')
    def command_itl(self, params):
        """
        Print initial topic response
        """

        if len(params) == 0:
            self.writeutfres("No topic name given, listing initials")
            for topic in self.server.worker.initial_reponses.keys():
                self.writeutfres(topic)
            return

        if params[0] not in self.server.worker.initial_reponses:
            self.writeerror("No initial data for topic %s" % params[0])
            return

        replaced = self.server.worker.initial_reponses[params[0]].replace('|', '\n')
        self.writeutfres(replaced)

    @command('data')
    def command_data(self, params):
        """
        Explore consumed data
        Explore consumed data
        Syntax: data [classification id [event id [market group id]]]
        """

        data = self.server.worker.data

        # Output classifications list
        if len(params) == 0:
            self.writeutfres('Classifications:')

            for cl in data.children:
                self.output_entity('\x1b[31;1m%s\t\x1b[0m%s', (data.children[cl].id, data.children[cl].name), data.children[cl])

        # Output topic -> entity dictionary
        if len(params) >= 1 and params[0] == 't':
            if len(params) == 1:
                self.writeutfres('Data topics dictionary: ')

                for t in data.topics:
                    self.writeutfres('\x1b[31;1m%s\t\x1b[0m%s' % (t, data.topics[t]))
            elif len(params) == 2:
                if params[1] not in data.topics:
                    self.writeutfres('No entity for topic %s' % params[1])
                else:
                    self.writeutfres('Topic %s:\n%s' % (params[1], data.topics[params[1]]))
            return

        # Output events
        if len(params) == 1:
            if params[0] == 's':  # TODO move to status command
                self.writeutfres('Data topics to subscribe: ')

                for t in data.topics_to_subscribe:
                    self.writeutfres(t)
            else:
                self.writeutfres('Events in classification %s: ' % params[0])

                if params[0] in data.children:
                    events = data.children[params[0]].children
                    for ev in events:
                        additional = events[ev].additional_output()
                        if len(additional) > 0:
                            additional = ', ' + additional

                        self.output_entity(
                            '\x1b[31;1m%s\t\x1b[32m%s\x1b[0m: championship %s, score %s, time %s%s%s%s',
                            (
                                events[ev].id,
                                events[ev].name,
                                events[ev].championship,
                                events[ev].score,
                                events[ev].get_event_time(),
                                "" if events[ev].time_ticking else ", paused",
                                "" if events[ev].is_match else ", not a match",
                                additional
                            ),
                            events[ev]
                        )

        # Output market groups
        if len(params) == 2:
            if params[0] in data.children:
                if params[1] in data.children[params[0]].children:
                    event = data.children[params[0]].children[params[1]]
                    additional = event.additional_output()
                    if len(additional) > 0:
                        additional = ', ' + additional
                    self.writeutfres('Event #%s:\n\tName: %s\n\tChampionship: %s\n\tScore: %s\n\tTime: %s%s\n\tEvent part: %s%s' % (
                        event.id,
                        event.name,
                        event.championship,
                        event.score,
                        event.get_event_time(),
                        "" if event.time_ticking else ", paused",
                        event.event_part,
                        additional
                    ))

                    if len(event.children) == 0:
                        self.writeutfres('No market groups')
                    else:
                        self.writeutfres('Market groups in event #%s: %s:' % (event.id, event.name))
                        for mg in event.children:
                            self.output_entity('\x1b[31;1m%s\t\x1b[32m%s\x1b[0m\tMapped: %s', (event.children[mg].id, event.children[mg].name, str(event.children[mg].is_mapped())), event.children[mg])

                    if event.teams is None or len(event.teams.children) == 0:
                        self.writeutfres('No teams')
                    else:
                        self.writeutfres('Teams in event #%s: %s:' % (event.id, event.name))
                        for team in event.teams.children:
                            self.output_entity('\x1b[31;1m%s\t\x1b[32m%s\x1b[0m', (event.teams.children[team].id, event.teams.children[team].name), event.teams.children[team])

                    if event.stats is None or len(event.stats.children) == 0:
                        self.writeutfres('No stats')
                    else:
                        self.writeutfres('Stats in event #%s: %s:' % (event.id, event.name))
                        for stat in event.stats.children:
                            self.output_entity('\x1b[31;1m%s\t\x1b[32m%s\t%s\x1b[0m', (event.stats.children[stat].id, event.stats.children[stat].icon_str(), event.stats.children[stat].label), event.stats.children[stat])
                else:
                    self.writeutfres('No such event')
            else:
                self.writeutfres('No such classification')

        # Output markets with propabilities
        if len(params) == 3:
            self.writeutfres('Markets in event %s, market group %s:' % (params[1], params[2]))
            if params[0] in data.children and params[1] in data.children[params[0]].children:
                mgs = data.children[params[0]].children[params[1]].children
                if params[2][:3] == "map":
                    self.output_mappings(mgs)
                else:
                    if params[2] in mgs:
                        mas = mgs[params[2]].children
                        for ma in mas:
                            self.output_entity('\x1b[31;1m%s\t\x1b[32m%s\x1b[0m propabilities:', (mas[ma].id, mas[ma].name), mas[ma])
                            pas = mas[ma].children
                            for pa in pas:
                                self.output_entity(
                                    '\t\x1b[31;1m%s\t\x1b[0mHA:%s OD:%s ODD:%s HD:%s\x1b[32m\t%s\tMapping: %s: %s\x1b[0m',
                                    (pas[pa].id, pas[pa].ha, pas[pa].od, pas[pa].od_decimal, pas[pa].hd, pas[pa].name, pas[pa].map(), pas[pa].mapped_value()),
                                    pas[pa]
                                )

    def output_mappings(self, mgs):
        for mg in mgs:
            self.writeutfres("\x1b[32m%s\x1b[0m (%s)\t" % (mgs[mg].name, mgs[mg].id))
            for mai in mgs[mg].children:
                for pai in mgs[mg].children[mai].children:
                    pa = mgs[mg].children[mai].children[pai]
                    if pa.map() is not None:
                        self.writeutfres("\t\x1b[31;1m%s\t\x1b[32m%s\x1b[0m" % (pa.map(), pa.mapped_value()))


    def output_entity(self, omask, otuple, entity):
        if self.config['raw']:
            omask += '\t%s Raw Vars: %s'
            otuple += (type(entity).__name__, entity.get_vars_raw())
        self.writeutfres(omask % otuple)

    def writeutfres(self, res):
        self.writeresponse(res.encode('ascii', 'replace'))
