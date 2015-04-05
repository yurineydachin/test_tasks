# -*- coding: utf-8 -*-

import abc
import logging
from collections import defaultdict

class Bet365DataObject(object):
    __metaclass__ = abc.ABCMeta
    message_lvl = ''

    def __init__(self, data, parent=None):
        self.parent = None
        self.topics = None
        self.topics_to_subscribe = None
        self.topic = None
        self.full_topic = None
        self.id = None
        self.name = ''
        self.order = ''
        self.children = {}
        self.update_markets = None
        self.vars_raw = {}
        self.mapper = None
        self.suspended = None

        if parent is not None:
            self.parent = parent
            self.topics = self.parent.topics
            self.topics_to_subscribe = self.parent.topics_to_subscribe
            self.mapper = parent.mapper
        self.process_data(data)

    def process_data(self, data, update_markets=False, reorder=False):
        self.update_markets = update_markets

        if 'vars' in data:
            self.set_vars(data['vars'])
            self.update_topics(data)

        for k in data:
            if k == 'vars':
                continue
            cls = self.get_child_cls(k[:2])
            if cls is None:
                continue
            if k[:2] != cls.message_lvl:
                logging.info('Entity %s(%s:%s): %s for child differs from expected %s', cls.__name__, self.id, self.name, k[:2], cls.message_lvl)
                continue

            entity = cls(data[k], self)

            order_exists = False
            if reorder:
                for pa in self.children:
                    if self.children[pa].order == entity.order:
                        order_exists = int(entity.order)
                        break
                if order_exists:
                    logging.info('INSERT PA, order %s', entity.order)
                    logging.info("INSERT Orders WAS %s", ', '.join([str(self.children[pa].order) for pa in self.children]))
                    for pa in self.children:
                        if int(self.children[pa].order) >= order_exists:
                            logging.info('Reordered %s, order %s -> %s', self.children[pa], self.children[pa].order, int(self.children[pa].order) + 1)
                            self.children[pa].order = int(self.children[pa].order) + 1
            self.put_child_in_collection(entity)
            if order_exists:
                logging.info(self)
                logging.info("Orders NOW %s\n\n", ', '.join([str(self.children[pa].order) for pa in self.children]))

        self.postprocess()

    def update_topics(self, data):
        self.update_topics_int(data['vars']['IT'])

    def update_topics_int(self, topic, prefixed=True):
        self.topic = topic
        if prefixed:
            self.full_topic = self.parent.full_topic + '/' + self.topic
        else:
            self.full_topic = self.topic
        self.topics[unicode(self.full_topic)] = self

    def set_vars(self, entity_vars):
        self.vars_raw.update(entity_vars)

        for var in entity_vars:
            if var == 'ID':
                if self.id is None and len(entity_vars[var]) > 0:
                    self.id = entity_vars[var]
            elif var == 'NA':
                if len(entity_vars[var]) > 0:
                    self.name = entity_vars[var]
            elif var == 'IT':
                pass
            elif var == 'OR':
                # hack: don't change order ever?
                if self.order == '':
                    self.order = entity_vars[var]
                else:
                    logging.info(self)
                    logging.info('Attempt to change order')
            elif var == 'SU':
                self.suspended = entity_vars[var] == "1"
            else:
                self.set_vars_cb(var, entity_vars[var])

        if self.id is None or len(self.id) == 0:
            self.id = entity_vars['IT']
        if type(self.id) == defaultdict:
            logging.critical('id is defaultdict?')
        self.set_vars_post_cb(entity_vars)

    def set_vars_cb(self, key, value):
        pass

    def set_vars_post_cb(self, entity_vars):
        pass

    def get_child_cls(self, level=''):
        return None

    def put_child_in_collection(self, child):
        self.children[child.id] = child

    def postprocess(self):
        pass

    def get_vars_raw(self):
        return ';'.join(['='.join(i) for i in self.vars_raw.items()])

    def get_main_vars(self):
        ret = []
        for k, v in self.vars_raw.items():
            if k in ('NA', 'HA', 'OR') and len(v.strip()) > 0:
                #hack for order?
                if k == 'OR':
                    ret.append(k + '=' + str(self.order))
                else:
                    ret.append(k + '=' + v.strip())

        return ';'.join(ret)

    def get_parent(self, parent_type):
        retval = self
        while retval.parent is not None:
            retval = retval.parent
            if isinstance(retval, parent_type):
                return retval
        return None
