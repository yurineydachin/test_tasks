# -*- coding: utf-8 -*-
###
# Following logic supposed to provide full connectivity with bet365.com diffusion server.
###

import threading
import logging
import ctypes
import re
import traceback, sys
from json import dumps
from collections import defaultdict
from time import time, sleep
from redis import Redis

import diffusion
from bet365messagedecoder import Bet365DecodedMessage
from config import Bet365ScanConfig

class Bet365AlreadySubscribedException(Exception):
    pass


class Bet365NotSubscribedException(Exception):
    pass


class Bet365SubscribeError(Exception):
    pass


class Bet365UnsubscribeError(Exception):
    pass


class Bet365ConnectionError(Exception):
    pass


class Bet365ServerListener(object):
    LEVEL_SPORT = 'Sport'
    LEVEL_EVENT = 'Event'

    MESSAGE_CHUNK_DELIM = '|'
    MESSAGE_VAR_DELIM   = ';'
    MESSAGE_KV_DELIM    = '='

    MESSAGE_INITIAL = 'F'
    MESSAGE_UPDATE  = 'U'
    MESSAGE_DELETE  = 'D'
    MESSAGE_INSERT  = 'I'

    MESSAGE_MARKER_SPORT = 'CL'  # and first sport list data chunk
    MESSAGE_MARKER_EVENT = 'EV'  # and first match info data chunk

    def __init__(self, server_name, server_spec, lock):
        self._lock = lock
        self._connection = None
        self._callbacks = None
        self.subscribed = {}
        self._first_subscribed_event = None
        self._subscribed_count = 0
        self._subscribed_count_all = 0
        self._subscribed_queue = {}
        self._subscribed_max = 50
        self.initial_reponses = {}
        self.server_name = server_name
        self.server_spec = server_spec
        self.running = True
        self.start_again = True
        self.start_failed = 0
        self.last_dump = time()
        self.last_message = time()
        self._rs_config = Bet365ScanConfig()['redis']
        self._redis = None
        self._dump = defaultdict(dict)

    def run(self):
        while self.start_again is True and self.start_failed < 5:
            if self.run_again() is False:
                self.start_failed += 1

    def run_again(self):
        logging.info('Starting listener, fails: %s' % self.start_failed)
        self.running = True
        self.last_message = time()
        try:
            self.connect()
        except Exception as e:
            logging.critical("Error connection to %s. %s: %s", self.server_name, str(type(e)), str(e))
            return False

        if not self._redis:
            try:
                self._redis = Redis(self._rs_config['host'])
            except Exception as e:
                logging.critical("Cannot connect to Redis server")
                return False

        self.clear_all_subscribe()
        ok = self.subscribe_sports()

        if not ok:
            return False

        try:
            self.setup_callbacks()
            while self.running is True:
                diffusion.diff_loop(ctypes.byref(self._connection), ctypes.byref(self._callbacks))
                self.checkRunning()
        except KeyboardInterrupt:  # for debug flag
            pass
        except Exception as e:
            logging.critical("Error spawning listener %s. %s: %s", self.server_name, str(type(e)), str(e))
        finally:
            logging.info('Shutting down listener')
            diffusion.diff_disconnect(ctypes.byref(self._connection))
        return False

    def checkRunning(self):
        if self.running is False:
            return {'running': False, 'again': self.start_again, 'sleep': None}

        if self._rs_config:
            if self._subscribed_count < self._subscribed_max and len(self._subscribed_queue) > 0:
                for topic in self._subscribed_queue.copy():
                    try:
                        self.subscribe(topic, self._subscribed_queue[topic])
                        del self._subscribed_queue[topic]
                    except Exception as e:
                        logging.info('Error subscribe topic %s from queue. %s: %s', topic, str(type(e)), str(e))
                    if self._subscribed_count >= self._subscribed_max:
                        break

            sleep_time =  self._rs_config['timeout']
            if (time() - self.last_message) > self._rs_config['reconnect']:
                logging.info('checkRunning. Seems bad connect!')
                #self._lock.acquire()
                self.on_disconnect()
                #self._lock.release()
            else:
                sleep_time =  self._rs_config['timeout'] - (time() - self.last_dump)
                if self._first_subscribed_event and time() - self._first_subscribed_event > self._rs_config['loading_events'] and self._subscribed_count <= 0:
                    #self._lock.acquire()
                    self.restart(was_timeout = False)
                    #self._lock.release()
                elif sleep_time <= 0:
                    logging.info('checkRunning. Timeout!')
                    #self._lock.acquire()
                    self.restart(was_timeout = True)
                    #self._lock.release()
                    #sleep_time =  self._rs_config['timeout'] - (time() - self.last_dump)

        return {'running': True, 'again': self.start_again, 'sleep': sleep_time}

    def subscribe_sports(self):
        try:
            logging.info("Subscribing all sports")
            for topic in self.server_spec['topics']:
                self.subscribe(topic, self.LEVEL_SPORT)
            return True
        except Exception as e:
            logging.critical("Error subscribing initial topic set for %s. %s: %s", self.server_name, str(type(e)), str(e))
            return False

    def stop(self):
        logging.info('Stoping Bet365ServerListener')
        self.running = False
        self.start_again = False

    def connect(self):
        logging.info("Trying to connect to %s", self.server_name)
        self._connection = diffusion.diff_connect(ctypes.c_char_p(self.server_spec['host']),
                                                  int(self.server_spec['port']), None)

        if self._connection is None:
            raise Bet365ConnectionError('Connection failed')

        self._connection = self._connection.contents
        logging.info("Connected as client %s", self._connection.client_id)

    def subscribe(self, topic, level):
        if self._subscribed_count >= self._subscribed_max:
            self._subscribed_queue[topic] = level
            return True

        if topic in self.subscribed:
            raise Bet365AlreadySubscribedException("Subscribe: already subscribed to topic %s" % topic)

        if diffusion.diff_subscribe(ctypes.byref(self._connection), ctypes.c_char_p(topic)) == -1:
            raise Bet365SubscribeError("Error subscribing to topic %s" % topic)
        self.subscribed[topic] = level

        if level == self.LEVEL_EVENT:
            if self._first_subscribed_event is None:
                self._first_subscribed_event = time()
            self._subscribed_count += 1
            self._subscribed_count_all += 1
        #logging.info("%s: %s ON, aT:%d, E:%d", self.server_name, topic, len(self.subscribed), self._subscribed_count)

    def unsubscribe(self, topic):
        if topic not in self.subscribed:
            raise Bet365NotSubscribedException("Unsubscribe: not subscribed to topic %s", topic)

        if diffusion.diff_unsubscribe(ctypes.byref(self._connection), ctypes.c_char_p(topic)) == -1:
            raise Bet365UnsubscribeError("Error unsubscribing from topic %s" % topic)

        level = self.subscribed[topic]
        del self.subscribed[topic]

        if level == self.LEVEL_EVENT:
            #pass
            self._subscribed_count -= 1
        #logging.info("%s: %s OFF, aT:%d, E:%d", self.server_name, topic, len(self.subscribed), self._subscribed_count)

    def unsubscribe_all(self):
        logging.info('Unsubscribing all sports/events')
        error = False;
        for topic in self.subscribed.copy():
            try:
                self.unsubscribe(topic)
            except Exception as e:
                logging.info('Error unsubscribe_all. %s: %s', str(type(e)), str(e))
                error = True

        if error is True:
            self.clear_all_subscribe()

    def clear_all_subscribe(self):
        self.subscribed = {}
        self._first_subscribed_event = None
        self._subscribed_count = 0
        self._subscribed_count_all = 0
        self._subscribed_queue = {}

    # Listening stuff
    def setup_callbacks(self):
        self._callbacks = diffusion.DIFFUSION_CALLBACKS()
        diffusion.DIFF_CB_ZERO(self._callbacks)
        self._callbacks.on_initial_load = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_message)
        self._callbacks.on_delta = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_message)
        self._callbacks.on_ping_server = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_message)
        self._callbacks.on_ping_client = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_ping)
        self._callbacks.on_fetch_reply = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_message)
        self._callbacks.on_ack = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_message)
        self._callbacks.on_fragment = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_fragment_message)
        self._callbacks.on_fragment_cancel = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_fragment_message)
        self._callbacks.on_unhandled_message = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_message)
        self._callbacks.on_disconnect = diffusion.DIFFUSION_CALLBACK_NOTHING_PASSED(self.on_disconnect)
        self._callbacks.on_command_topic_load = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(self.on_command_topic_load)
        self._callbacks.on_command_topic_notification = diffusion.DIFFUSION_CALLBACK_MESSAGE_PASSED(
            self.on_command_topic_notification)

    def on_message(self, message):
        #logging.info('Listener thread got message on topic %s: type %d, encoding %d, data_length %d', message.contents.topic, message.contents.message_type, message.contents.encoding, message.contents.data_length)

        try:
            raw_message = Bet365RawMessage(message.contents)
            decoded_message = Bet365DecodedMessage(raw_message)
            if raw_message.message_type == diffusion.DIFFUSION_MSG_TOPIC_LOAD:
                self.initial_reponses[raw_message.topic] = decoded_message.message_body

            self.process_message(decoded_message)

        except Exception as e:
            logging.info('Error decoding/processing message. %s: %s', str(type(e)), str(e))
            # logging.info(sys.exc_info())
            # exc_type, exc_value, exc_traceback = sys.exc_info()
            # logging.info(traceback.extract_tb(exc_traceback, limit=4))

    def process_message(self, decoded_message):
        self.last_message = time()
        topic = decoded_message.message.topic
        message_start = decoded_message.message_body[:5]

        if message_start == "%s%s%s%s" % (self.MESSAGE_INITIAL, self.MESSAGE_CHUNK_DELIM, self.MESSAGE_MARKER_SPORT, self.MESSAGE_VAR_DELIM): #F|CL;
            self._dump[topic] = {
                'level': self.LEVEL_SPORT,
                'actuality': time(),
                'message': decoded_message.message_body
            }
            #logging.info("Dump sport topic: %s, body:\n%s", topic, decoded_message.message_body.replace('|', "\n"))
            events = re.findall(ur"EV;[^|]+;ID=([\w]+);", decoded_message.message_body)
            logging.info("%s, subscribing: %s", topic, ', '.join(events))

            topic_sport = topic.split('/')
            if len(topic_sport) == 2:
                topic_sport = topic_sport[1] + '//'
                if topic_sport in self.subscribed:
                    self.unsubscribe(topic_sport)


            for event in events:
                try:
                    self.subscribe('6V' + event, self.LEVEL_EVENT)
                except Bet365AlreadySubscribedException as e:
                    logging.debug(e)
                except Exception as e:
                    logging.error('Cascade subscribing failed. %s: %s', str(type(e)), str(e))

        elif message_start == "%s%s%s%s" % (self.MESSAGE_INITIAL, self.MESSAGE_CHUNK_DELIM, self.MESSAGE_MARKER_EVENT, self.MESSAGE_VAR_DELIM): #F|EV;
            self._dump[topic] = {
                'level': self.LEVEL_EVENT,
                'actuality': time(),
                'message': decoded_message.message_body
            }
            #logging.info("Dump event topic: %s, body:\n%s", topic, decoded_message.message_body.replace('|', "\n"))

            #self._subscribed_count -= 1

            self.unsubscribe(topic)
            # wait till all topics receive initials
            #if self._subscribed_count == 0:
            #    self.restart(was_timeout = False)
        else:
            pass
            #logging.info("Unknown topic: %s -> %s", topic, message_start)
            #logging.info("Unknown topic: %s, body:\n%s", topic, decoded_message.message_body.replace('|', "\n"))

    def restart(self, was_timeout = False):
        # sleep & restart
        if was_timeout is False:
            self.dump()
            self.unsubscribe_all()

            self.start_failed = 0      # normal work without timeout
            sleep_time =  self._rs_config['interval'] - (time() - self.last_dump)
            if self._subscribed_count < 0:
                logging.info('Dublicate event topics %d' % self._subscribed_count)
            if sleep_time > 0:
                logging.info('Sleep %.2f sec...' % sleep_time)
                sleep(sleep_time)
            else:
                logging.info('Do not sleep (%.2f)' % sleep_time)

        elif self._subscribed_count > 0:
            self.dump()
            self.unsubscribe_all()
        else:
            return self.stop()

        logging.info('Restart, sub: %s / %s / %s', self._subscribed_count, self._subscribed_count_all, len(self._subscribed_queue))
        # reset internal vars
        self.subscribed = {}
        self._first_subscribed_event = None
        self._subscribed_count = 0
        self._subscribed_count_all = 0
        self._subscribed_queue = {}
        self.last_dump = time()
        self._dump = defaultdict(dict)
        self.subscribe_sports()

    def dump(self):
        logging.info('Try dump data')
        try:
            dump_to = str(self._rs_config['key'])
            dumpcontents = dumps(self._dump)
            self._redis.set(dump_to, dumpcontents)
            self._redis.expire(dump_to, 15)
            logging.info('Saved data to Redis, key "%s", size %s' % (dump_to, len(dumpcontents)))
        except Exception as e:
            logging.critical('Cannot dump data into Redis, key "%s", %s' % (dump_to, str(e)))

    def on_ping(self, message):
        logging.info('Got ping. Responding')
        diffusion.diff_ping_response(ctypes.byref(self._connection), message)

    def on_fragment_message(self, message):
        logging.critical('Got fragment message %s. Not implemented!', unicode(message))

    def on_disconnect(self):
        logging.info('Got disconnect, sub: %s / %s / %s', self._subscribed_count, self._subscribed_count_all, len(self._subscribed_queue))
        self.running = False
        self.start_again = True
        if self._subscribed_count > 0:
            self.dump()
            logging.info('Try to run again')
        else:
            self.stop()

    def on_command_topic_load(self, message):
        pass

    def on_command_topic_notification(self, message):
        pass


###
# Дополнительный поток для управления основным рабочим механизмом _worker.run()
# Если diffusion перестает слать сообщения, то _worker перегружается
###
class Bet365ServerListenerRestarter(threading.Thread):
    def __init__(self, worker):
        threading.Thread.__init__(self)
        self._worker = worker
        self.running = True

    def run(self):
        logging.info('Restarter run')
        try:
            while self.running:
                sleep_time = 1
                status = self._worker.checkRunning()
                if status['running'] is False:
                    logging.info('Restarter. Bet365ServerListener is not running')
                    if status['again'] is False:
                        logging.info('Restarter. break')
                        break
                elif status['sleep'] < sleep_time:
                    sleep_time = status['sleep']

                logging.info('checkRunning. Ok -> sleep %s instead %s sec ...' % (sleep_time, status['sleep']))
                sleep(sleep_time)

        except Exception as e:
            logging.critical("Restarter. Error  %s. %s: %s", self.server_name, str(type(e)), str(e))
        finally:
            logging.info('Restarter finished')

    def stop(self):
        logging.info('Stoping Restarter')
        self.running = False


###
# c-api to python conversion
# TODO llist headers support if necessary
###
class Bet365RawMessage(object):
    message_type = None
    encoding = None
    topic = None
    data_length = None
    data = None

    def __init__(self, ctypes_message):
        self.message_type = ctypes_message.message_type
        self.encoding = ctypes_message.encoding
        self.topic = unicode(ctypes_message.topic)
        self.data_length = ctypes_message.data_length
        self.data = ctypes_message.data[:ctypes_message.data_length]
