# -*- coding: utf-8 -*-

import logging

from data_object import Bet365DataObject
import event

class Bet365Classification(Bet365DataObject):
    message_lvl = 'CL'

    def get_child_cls(self, level=''):
        event_type = {
            "1": event.Bet365SoccerEvent,
            "7": event.Bet365GolfEvent,
            "9": event.Bet365BoxingEvent,
            "12": event.Bet365AmericanFootballEvent,
            "13": event.Bet365TennisEvent,
            "16": event.Bet365BaseballEvent,
            "17": event.Bet365IceHockeyEvent,
            "18": event.Bet365BasketballEvent,
            "91": event.Bet365VolleyballEvent,
            "92": event.Bet365TableTennis,
            "94": event.Bet365Badminton
        }

        if self.id in event_type:
            return event_type[self.id]
        logging.info("No classification in event lookup `%s': %s. Notice to add to lookup", self.id, self.name)
        return event.Bet365Event

    def update_topics(self, data):
        if self.parent.full_topic[(self.parent.full_topic.rindex('/') + 1):] == data['vars']['IT']: # in case of CL_* as initial topic
            self.update_topics_int(self.parent.full_topic, False)
        else:
            self.update_topics_int(data['vars']['IT'])


    def __str__(self):
        return 'Classification #%s: %s' % (self.id, self.name)
