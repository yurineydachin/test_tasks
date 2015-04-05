# -*- coding: utf-8 -*-

import logging
from data_object import Bet365DataObject


class Bet365MarketGroup(Bet365DataObject):
    message_lvl = 'MG'

    def get_child_cls(self, level=''):
        return Bet365Market

    def __str__(self):
        return 'Market group #%s: %s. Event #%s: %s' % (self.id, self.name, self.parent.id, self.parent.name)

    def is_mapped(self):
        if hasattr(self, 'mapped'):
            return self.mapped
        self.mapped = False
        for mai in self.children:
            for pai in self.children[mai].children:
                if self.children[mai].children[pai].map() is None:
                    self.mapped = False if not self.mapped else "Partial"
                    continue  # break replaced for partial mapping support: there is one and only place where mapping is invoked. Instead of telnet, of course
                self.mapped = True
        return self.mapped


class Bet365Market(Bet365DataObject):
    message_lvl = 'MA'

    def get_child_cls(self, level=''):
        return Bet365Probability

    def __str__(self):
        return 'Market #%s: %s. Market group #%s: %s. Event #%s: %s, Suspended: %s' % \
            (self.id, self.name, self.parent.id, self.parent.name, self.parent.parent.id, self.parent.parent.name, self.suspended)


class Bet365Probability(Bet365DataObject):
    MAPPED_ATTR_OD = 1
    MAPPED_ATTR_HA = 2
    MAPPED_ATTR_NAME = 3

    message_lvl = 'PA'

    def __init__(self, data, parent=None):
        self.od = ''
        self.od_decimal = ''
        self.ha = ''
        self.hd = ''
        self.mapping = None
        self.mapped_attr = self.MAPPED_ATTR_OD

        super(Bet365Probability, self).__init__(data, parent)

        # XXX it is dangerous to call here because teams could not be ready yet
        #self.map()

    def set_vars_cb(self, key, value):
        if key == 'OD':
            self.od = value
            self.od_decimal = self.od_to_decimal()
        elif key == 'HA':
            self.ha = value
        elif key == 'HD':
            self.hd = value

    def od_to_decimal(self):
        parts = self.od.split('/')
        if len(parts) != 2:
            return ''
        try:
            return "%0.3f" % (1 + float(parts[0]) / float(parts[1]))
        except ZeroDivisionError:  # Sometimes OD could be 0/0. I just don't want to flood log files
            return '0.0'
        except Exception, e:
            logging.debug("Error converting OD %s to decimal. Exception %s: %s", self.od, type(e).__name__, e)
            return ''

    def __str__(self):
        return 'Probability #%s: %s %s. Market group #%s: %s. Event #%s: %s, Suspended: %s, Order: %s' % \
            (self.id, self.ha, self.od, self.parent.parent.id, self.parent.parent.name, self.parent.parent.parent.id, self.parent.parent.parent.name, self.suspended, self.order)

    def map(self):
        if self.mapping is not None and self.mapper is not None:
            return self.mapping
        if self.mapper is None:
            return None
        self.mapping = self.mapper.map(self)
        logging.debug("Probability name %s, mapping %s", self.name, self.mapping)
        return self.mapping

    def mapped_value(self):
        if self.mapped_attr == self.MAPPED_ATTR_OD:
            return self.od_decimal
        if self.mapped_attr == self.MAPPED_ATTR_HA:
            return self.ha
        if self.mapped_attr == self.MAPPED_ATTR_NAME:
            return self.name
        logging.critical('Unknown probability mapped_attr %s', str(self.mapped_attr))
