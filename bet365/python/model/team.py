# -*- coding: utf-8 -*-

from data_object import Bet365DataObject


class Bet365TeamGroup(Bet365DataObject):
    message_lvl = 'TG'

    def get_child_cls(self, level=''):
        return Bet365Team


class Bet365Team(Bet365DataObject):
    message_lvl = 'TE'

    def __str__(self):
        return "Team %s" % self.name
