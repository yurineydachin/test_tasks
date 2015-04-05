# -*- coding: utf-8 -*-

import logging
import calendar
import pytz
import time
import datetime
import re

from data_object import Bet365DataObject
from config import Bet365ScanConfig
from market import Bet365MarketGroup, Bet365Probability
from stat import Bet365StatGroup
from team import Bet365TeamGroup


# Determine if Daylight Saving Time is active
def _dst_fix():
    dt = datetime.datetime.now(pytz.timezone('Europe/London'))
    du = datetime.datetime.utcnow()
    return (dt.hour - du.hour) * 3600

# These clowns using London time instead of UTC for timestamps!
_server_time_diff = _dst_fix()


class Bet365Event(Bet365DataObject):
    message_lvl = 'EV'
    mappers = {}

    def __init__(self, data, parent=None):
        self.championship = None
        self.start_time = None
        self.score = None
        self.is_match = False
        self.is_break = False
        self.quarter = False

        self.time_ticking = False
        self.time_countdown = False
        self.time_updated = None
        self.time_updated_raw = ''
        self.time_minutes = 0
        self.time_seconds = 0

        self.event_part = 1

        self.teams = None
        self.stats = None

        super(Bet365Event, self).__init__(data, parent)

        self.mapper = self.get_mapper()

    def update_topics(self, data):
        self.update_topics_int(data['vars']['IT'])
        self.update_topics_int('6V' + data['vars']['ID'], False)
        if Bet365ScanConfig()['bet365_server']['cascade_subscribe'] and self.topic != data['vars']['IT']:
            self.topics_to_subscribe.append(self.topic)

    def get_child_cls(self, level=''):
        if not self.update_markets:  # we don't need info from InPlay here; just 6V* topics
            return None
        if level == 'MG':
            return Bet365MarketGroup
        elif level == 'TG':
            return Bet365TeamGroup
        elif level == 'SG':
            return Bet365StatGroup

    def put_child_in_collection(self, child):
        if isinstance(child, Bet365TeamGroup):
            self.teams = child
        elif isinstance(child, Bet365StatGroup):
            self.stats = child
        else:
            super(Bet365Event, self).put_child_in_collection(child)

    def set_vars_cb(self, key, value):
        if key == 'SS':
            self.score = value
        elif key == 'HP':
            #self.is_match = False if value == "0" else True #Not working!
            self.is_match = True
        elif key == 'CT':
            self.championship = value
        elif key == 'CP' and value[0] == 'Q':
            # quarter, basket etc
            self.quarter = value[1:]
        elif key == 'TT':
            self.time_ticking = (value == "1")
        elif key == 'TD':
            self.time_countdown = (value == "1")
        elif key == 'TM':
            try:
                self.time_minutes = int(value)
            except ValueError:
                logging.warn("Error parsing event minutes (TM): can't convert `%s' to int", value)
        elif key == 'TS':
            try:
                self.time_seconds = int(value)
            except ValueError:
                logging.warn("Error parsing event seconds (TS): can't convert `%s' to int", value)
        elif key == 'TU' and len(value) > 0:
            try:
                self.time_updated = calendar.timegm((int(value[:4]), int(value[4:6]), int(value[6:8]), int(value[8:10]), int(value[10:12]), int(value[12:14])))
                self.time_updated_raw = value
            except ValueError, e:
                logging.warn("Error parsing event time updated (TU): value `%s', error: %s", value, unicode(e))

    def set_vars_post_cb(self, entity_vars):
        pass

    def get_event_time(self, formatted=True, absolute=False):
        if self.time_minutes is None or self.time_seconds is None:
            return None
        seconds = self.time_minutes * 60 + self.time_seconds

        if self.time_ticking:
            if self.time_updated is None:
                return None
            diff = int(time.time()) - self.time_updated + _server_time_diff
            if self.time_countdown:
                seconds -= diff
            else:
                seconds += diff

        if absolute:
            seconds += time.time()

        return datetime.timedelta(seconds=seconds) if formatted else seconds

    def __str__(self):
        return 'Event #%s: %s' % (self.id, self.name)

    # noinspection PyMethodMayBeStatic
    def additional_output(self):
        return u''

    def get_mapper(self):
        if type(self) in self.mappers:
            return self.mappers[type(self)]
        return None

        #logging.debug("No probability mapper for event type %s", type(event).__name__)

    @classmethod
    def fill_mappers(cls):
        if len(cls.mappers) == 0:
            for mapper in ProbabilityMapperBase.__subclasses__():
                if mapper.event_type is not None:
                    cls.mappers[mapper.event_type] = mapper


#
# Classification-specific event types
#
class Bet365SoccerEvent(Bet365Event):  # 1
    def postprocess(self):
        super(Bet365SoccerEvent, self).postprocess()
        # XXX third half?
        if self.stats is not None:
            for s in self.stats.children:
                if self.stats.children[s].label.find("Half Time") != -1:
                    self.event_part = 2
                if self.stats.children[s].label.find("score at the end of First Half") != -1:
                    self.event_part = 2
                if self.stats.children[s].label.find("score at the end of Second Half") != -1:
                    self.event_part = 3

                m = re.search('(.*?)\s*>?score at the end', self.stats.children[s].label)
                if m and m.group(1):
                    self.score += ',' + m.group(1)



class Bet365GolfEvent(Bet365Event):  # 7
    pass


class Bet365BoxingEvent(Bet365Event):  # 9
    pass


class Bet365AmericanFootballEvent(Bet365Event):  # 12
    pass


class Bet365TennisEvent(Bet365Event):  # 13
    def postprocess(self):
        super(Bet365TennisEvent, self).postprocess()
        self.event_part = self.score.count(',') + 1

class Bet365BaseballEvent(Bet365Event):  # 16
    pass


class Bet365IceHockeyEvent(Bet365Event):  # 17
    pass


class Bet365BasketballEvent(Bet365Event):  # 18
    def postprocess(self):
        super(Bet365BasketballEvent, self).postprocess()
        if self.quarter:
            self.event_part = self.quarter

class Bet365VolleyballEvent(Bet365Event):  # 91
    pass


class Bet365TableTennis(Bet365Event):  # 92
    pass

class Bet365Badminton(Bet365Event): # 94
    pass


# Probability mappers
# Python cyclic imports forced me to put it here
class ProbabilityMapperBase(object):
    event_type = None
    lookup = {}

    @classmethod
    def map(cls, probability):
        mg_id = probability.parent.parent.id
        if mg_id in cls.lookup:
            try:
                retval = cls.lookup[mg_id].__func__(cls, probability)
                if retval is None:
                    logging.debug("Error mapping MG %s", mg_id)
                return retval
            except Exception as e:
                logging.warn("Error mapping probability in market group %s. Mapper %s. Error: %s", mg_id, cls.__name__, e)
        return None


class ProbabilityMapperSoccer(ProbabilityMapperBase):
    event_type = Bet365SoccerEvent

    @classmethod
    def _parse_scores(cls, _scores, reverse=False, limit=None):
        scs = _scores.split('-')
        if len(scs) != 2:
            return None
        if limit is not None and (int(scs[0]) > limit or int(scs[1]) > limit):  # XXX system limitation
            return None
        if reverse:
            scs = list(reversed(scs))
        return scs

    @classmethod
    def map_exact_score(cls, probability):
        scores = cls._parse_scores(probability.name, probability.parent.name == "2", 4)
        if scores is None:
            return None
        return "EXACT_SCORE_%s_%s" % (scores[0], scores[1])

    @classmethod
    def map_exact_score_ht(cls, probability):
        event = probability.get_parent(Bet365Event)
        scores = cls._parse_scores(probability.name, probability.parent.name == "2", 3)
        if scores is None:
            return None
        return "EXACT_SCORE_3_HT%s_%s_%s" % (str(event.event_part), scores[0], scores[1])

    @classmethod
    def map_total_goals(cls, probability, order_add=False):
        add = ""
        order = int(probability.order)
        if order > (9 - (1 if order_add else 0)):  # XXX system limitation
            return None

        if probability.parent.name == "OVER":
            add = "G"
        elif probability.parent.name == "UNDER":
            add = "L"
        else:
            probability.mapped_attr = Bet365Probability.MAPPED_ATTR_NAME

        if order_add:
            order = str(order + 1)

        return "FT_TOTAL_%s_T%s" % (order, add)

    @classmethod
    def map_alt_goals(cls, probability):
        return cls.map_total_goals(probability, True)

    @classmethod
    def map_goals_odd_even(cls, probability):
        return "FT_%s" % probability.name

    @classmethod
    def map_team_to_score_1ht(cls, probability):
        return cls.map_team_to_score_ht(probability, '1')

    @classmethod
    def map_team_to_score_2ht(cls, probability):
        return cls.map_team_to_score_ht(probability, '2')

    @classmethod
    def map_team_to_score_ht(cls, probability, ht):
        side = str(int(probability.parent.order) + 1)
        return "HIT_%s_HT%s_%s" % (side, ht, probability.name.upper())

    @classmethod
    def map_both_team_to_score_1ht(cls, probability):
        return cls.map_both_team_to_score_ht(probability, '1')

    @classmethod
    def map_both_team_to_score_2ht(cls, probability):
        return cls.map_both_team_to_score_ht(probability, '2')

    @classmethod
    def map_both_team_to_score_ht(cls, probability, ht):
        add = "BH" if probability.name == "Yes" else "NH"
        return "WHO_HIT_HT%s_%s" % (ht, add)

    @classmethod
    def map_team_to_score_both_ht(cls, probability):
        side = "HOME" if probability.parent.order == 0 else "AWAY"
        return "HIT_%s_IN_BOTH_HT_%s" % (side, probability.name.upper())

    @classmethod
    def map_halftime_fulltime(cls, probability):
        event = probability.get_parent(Bet365Event)
        if event.teams is None or (not "1" in event.teams.children and not "2" in event.teams.children):
            logging.warn("Event #%s: %s. No team info!", event.id, event.name)
            return None

        teams = probability.name.split(" - ")
        for i, t in enumerate(teams):
            t = t.strip()
            if t == "Draw":
                teams[i] = "D"
            if t == event.teams.children["1"].name:
                teams[i] = "H"
            elif t == event.teams.children["2"].name:
                teams[i] = "A"

        return "HTFT_%s%s" % (teams[0], teams[1])

    @classmethod
    def map_nth_goal(cls, probability):
        m = re.search(r'(\d)+', probability.parent.parent.name)
        if not m:
            return None
        goalnum = int(m.group(0))

        add = None
        if probability.order == "0":
            add = "FG1" if goalnum == 1 else "NG1"
        elif probability.order == "1":
            add = "NO_GOAL"
        elif probability.order == "2":
            add = "FG2" if goalnum == 1 else "NG2"


        if add is not None:
            if goalnum == 1:
                return "FGLG_%s" % add
            else:
                return "NG%s_%s" % (str(goalnum), add)
        return None

    @classmethod
    def map_last_goal(cls, probability):
        event = probability.get_parent(Bet365Event)
        if event.teams is None or (not "1" in event.teams.children and not "2" in event.teams.children):
            logging.warn("Event #%s: %s. No team info!", event.id, event.name)
            return None

        add = None
        if probability.name == event.teams.children["1"].name:
            add = "LG1"
        elif probability.name == event.teams.children["2"].name:
            add = "LG2"
        else:
            add = "NO_GOAL"

        if add is not None:
            return "FGLG_%s" % add
        return None

    @classmethod
    def map_both_team_to_score(cls, probability):
        add = "BH" if probability.name == "Yes" else "NH"
        return "WHO_HIT_%s" % add

    @classmethod
    def map_team_total_home(cls, probability):
        return cls.map_team_total(probability, "HOME")

    @classmethod
    def map_team_total_away(cls, probability):
        return cls.map_team_total(probability, "AWAY")

    @classmethod
    def map_team_total(cls, probability, team):
        order = int(probability.order)
        if order > 10:  # system limitation
            return None
        add = ""

        if probability.parent.name == "OVER":
            add = "G"
        elif probability.parent.name == "UNDER":
            add = "L"
        else:
            probability.mapped_attr = Bet365Probability.MAPPED_ATTR_NAME
        return "TOTAL%s_FT_%s_T%s" % (str(order + 1), team, add)

    @classmethod
    def map_goals_1ht(cls, probability):
        return cls.map_goals_ht(probability, '1')

    @classmethod
    def map_goals_2ht(cls, probability):
        return cls.map_goals_ht(probability, '2')

    @classmethod
    def map_goals_ht(cls, probability, ht):
        add = ""
        if probability.parent.name == "OVER":
            add = "G"
        elif probability.parent.name == "UNDER":
            add = "L"
        else:
            probability.mapped_attr = Bet365Probability.MAPPED_ATTR_NAME

        if probability.order == "0":
            return "HT%s_T%s" % (ht, add)

        order = int(probability.order)
        if order > 10:  # system limitation
            return None
        order = str(order - 1)
        return "HT%s_TOTAL_%s_T%s" % (ht, order, add)

    @classmethod
    def map_to_win_1ht(cls, probability):
        return cls.map_to_win_ht(probability, '1')

    @classmethod
    def map_to_win_2ht(cls, probability):
        return cls.map_to_win_ht(probability, '2')

    @classmethod
    def map_to_win_ht(cls, probability, ht):
        who = None
        if probability.order == "0":
            who = "1"
        elif probability.order == "1":
            who = "X"
        elif probability.order == "2":
            who = "2"

        if who is not None:
            return "HT%s_%s" % (ht, who)
        return None

    @classmethod
    def map_double_chance(cls, probability):
        if probability.name.find("Draw") == -1:
            return "DCFT_12"

        event = probability.get_parent(Bet365Event)
        if event.teams is None or (not "1" in event.teams.children and not "2" in event.teams.children):
            logging.warn("Event #%s: %s. No team info!", event.id, event.name)
            return None

        if probability.name.find(event.teams.children["1"].name) != -1:
            return "DCFT_1X"
        elif probability.name.find(event.teams.children["2"].name) != -1:
            return "DCFT_X2"

        return None

    @classmethod
    def map_full_time(cls, probability):
        who = None
        if probability.order == "0":
            who = "1"
        elif probability.order == "1":
            who = "X"
        elif probability.order == "2":
            who = "2"

        if who is not None:
            return "FT_%s" % who
        return None

    @classmethod
    def map_half_time(cls, probability):
        return cls.map_to_win_ht(
            probability,
            str(probability.get_parent(Bet365Event).event_part)
        )

    @classmethod
    def map_ft_corners(cls, probability):
        order = int(probability.order)
        if order > 2:  # system have a limit of max 3 corners totals
            return None
        if probability.parent.name == "EXACTLY":  # can't find coeff for this
            return None

        order = str(order + 1)

        if probability.parent.name == "OVER":
            return "FT_TOTAL%s_CORNERS_TG" % order
        if probability.parent.name == "UNDER":
            return "FT_TOTAL%s_CORNERS_TL" % order

        probability.mapped_attr = Bet365Probability.MAPPED_ATTR_NAME
        return "FT_TOTAL%s_CORNERS_T" % order

    #@classmethod
    #def map_time_of_goal(cls, probability):
    #    if probability.parent.parent.name.find("FIRST") == -1:  # map only first goal
    #        return None
    #    suffix = cls._time_to_coeff_suffix(probability.parent.name)

    #@classmethod
    #def _time_to_coeff_suffix(cls, time):


    # mapping functions lookup
    lookup = {
        "325": map_exact_score,
        "17": map_alt_goals,
        "421": map_total_goals,
        "10562": map_goals_odd_even,
        # TODO "": map_team_to_score_1ht, (hasn't seen at bet365 yet)
        "387": map_team_to_score_2ht,
        "16": map_ft_corners,
        "10560": map_halftime_fulltime,
        "1778": map_nth_goal,
        "10564": map_last_goal,
        "388": map_team_to_score_both_ht,
        "10565": map_both_team_to_score,
        "50390": map_both_team_to_score_1ht,
        "50391": map_both_team_to_score_2ht,
        # TODO "": map_to_win_1ht, (hasn't seen at bet365 yet)
        "10161": map_half_time,  # instead of map_to_win_1ht?
        "50246": map_to_win_2ht,
        "10115": map_double_chance,
        "1777": map_full_time,
        "5": map_goals_1ht,
        # TODO "": map_goals_2ht, (hasn't seen at bet365 yet)

        # two below is suspicious
        "1": map_team_total_home,
        "2": map_team_total_away,

        # untested below
        #"20": map_time_of_goal,
    }

class ProbabilityMapperTennis(ProbabilityMapperBase):
    event_type = Bet365TennisEvent

    @classmethod
    def map(cls, probability):
        return None


# Mappers dict population
Bet365Event.fill_mappers()
