# -*- coding: utf-8 -*-

import logging

from bet365messageparser import Bet365ParsedMessage
from classification import Bet365Classification
from event import Bet365Event
from market import Bet365Market

class Bet365MessageProcessError(Exception):
    pass


# noinspection PyClassicStyleClass
class Bet365Data(object):
    def __init__(self):
        self.children = {}  # dict of Bet365Classification
        self.topics = {}  # topic name -> entity reference
        self.topics_to_subscribe = []
        self.topic = ''
        self.full_topic = ''
        self.mapper = None

    def process_message(self, message):
        # logging.debug('Processing message: topic %s, type %s, level %s', message.topic, message.type, message.level)

        if message.type == Bet365ParsedMessage.MESSAGE_INITIAL:
            is_event = self.process_message_initial(message)
            return is_event
        else:
            return False

        # elif message.type == Bet365ParsedMessage.MESSAGE_UPDATE:
        #     self.process_message_update(message)
        # elif message.type == Bet365ParsedMessage.MESSAGE_TOPIC_DELETE:
        #     self.process_message_delete(message)
        # elif message.type == Bet365ParsedMessage.MESSAGE_TOPIC_INSERT:
        #     self.process_message_insert(message)
        # else:
        #     raise Bet365MessageProcessError('Unknown message type: %s' % message.type)

    def process_message_initial(self, message):
        if message.level == Bet365ParsedMessage.MESSAGE_MARKER_SPORT_TYPE:  # initial message from InPlay
            self.topic = self.full_topic = message.topic
            for cl_data in message.data:
                cl = Bet365Classification(message.data[cl_data], self)
                self.children[cl.id] = cl
                logging.debug('Processing message: classification id %s, name %s', cl.id, cl.name)
            return False
        elif message.level == Bet365ParsedMessage.MESSAGE_MARKER_MATCH:  # initial message from 6V* topic
            if message.topic not in self.topics:
                raise Bet365MessageProcessError("Unknown event. Topic: %s" % message.topic)
            if not isinstance(self.topics[message.topic], Bet365Event):
                raise Bet365MessageProcessError("Consistency error: topic referencing to instance of type `%s', must be `Bet365Event'" % self.topics[message.topic].__class__.__name__)
            logging.info("Got event message for event %s", self.topics[message.topic].name)
            self.topics[message.topic].process_data(message.data[message.data.keys()[0]], True)
            return True

    def process_message_update(self, message):
        if message.topic in self.topics:
            self.topics[message.topic].set_vars(message.data)
            self.topics[message.topic].postprocess()

    def process_message_delete(self, message):
        if not message.topic in self.topics:
            logging.debug('Got delete message on topic %s, entity undefined' % message.topic)
            return

        entity = self.topics[message.topic]
        if isinstance(entity, Bet365Classification):
            logging.debug('Got delete message for classification #%s: %s (topic %s). Ignoring', entity.id, entity.name, message.topic)
            return

        logging.info('')
        logging.info("DELETE Orders WAS %s", ', '.join([str(entity.parent.children[pa].order) for pa in entity.parent.children]))
        del entity.parent.children[entity.id]  # remove parent reference

        if isinstance(entity.parent, Bet365Market):
            logging.info('DELETE PA, order %s', entity.order)
            for pa in entity.parent.children:
                if int(entity.parent.children[pa].order) > int(entity.order):
                    logging.info('Reordered %s, order %s -> %s', entity.parent.children[pa], entity.parent.children[pa].order, int(entity.parent.children[pa].order) - 1)
                    entity.parent.children[pa].order = int(entity.parent.children[pa].order) - 1
            logging.info(entity)
            logging.info("Orders NOW %s", ', '.join([str(entity.parent.children[pa].order) for pa in entity.parent.children]))

        del self.topics[message.topic]  # remove topic reference

    def process_message_insert(self, message):
        if message.level == 'CL':
            logging.info('Got new classification: %s', message.data_str())
            return

        parent_topic = message.topic[:message.topic.rindex('/')]
        if not parent_topic in self.topics:
            logging.debug("Got insert message level `%s' on topic %s (parent topic %s), entity undefined", message.level, message.topic, message.topic[:message.topic.rindex('/')])
            return

        entity = self.topics[parent_topic]

        reorder = False
        if isinstance(entity, Bet365Market) and len(entity.children) > 1 and len(entity.parent.children) > 1:
            #logging.info('Insert into %s', entity)
            reorder = True

        #logging.debug('Got insert message on topic %s, entity %s', message.topic, unicode(entity))
        entity.process_data(message.data, message.topic[:1] != 'L', reorder=reorder)
