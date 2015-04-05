# -*- coding: utf-8 -*-

import logging
from data_object import Bet365DataObject


class Bet365StatGroup(Bet365DataObject):
    message_lvl = 'SG'

    def get_child_cls(self, level=''):
        return Bet365Stat


class Bet365Stat(Bet365DataObject):
    message_lvl = 'ST'
    icons = {
        "0": u"No Icon",
        "1": u"Info",
        "2": u"Goal",
        "4": u"Yellow card",
        "5": u"Red card",
    }

    def __init__(self, data, parent=None):
        self.label = ''
        self.icon = "1"
        super(Bet365Stat, self).__init__(data, parent)

    def set_vars_cb(self, key, value):
        if key == 'IC':
            self.icon = value
        elif key == 'LA':
            self.label = value

    def icon_str(self):
        if self.icon in self.icons:
            return self.icons[self.icon]
        logging.warning("Unknown icon type `%s', label `%s'", self.icon, self.label)
        return ""

    def __str__(self):
        return "%s: %s" % (self.icon_str(), self.label)
