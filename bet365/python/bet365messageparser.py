# -*- coding: utf-8 -*-

import logging
import json
from util import dict_tree


class Bet365ParsedMessage(object):
    MESSAGE_CHUNK_DELIM = '|'
    MESSAGE_VAR_DELIM = ';'
    MESSAGE_KV_DELIM = '='

    MESSAGE_INITIAL = 'F'
    MESSAGE_UPDATE = 'U'
    MESSAGE_TOPIC_DELETE = 'D'
    MESSAGE_TOPIC_INSERT = 'I'

    MESSAGE_MARKER_SPORT_TYPE = 'CL'  # and first sport list data chunk
    MESSAGE_MARKER_MATCH = 'EV'  # and first match info data chunk
    MESSAGE_MARKER_MARKET_GROUP = 'MG'
    MESSAGE_MARKER_MARKET = 'MA'
    MESSAGE_MARKER_PROBABILITY = 'PA'
    MESSAGE_MARKER_TEAM_GROUP = 'TG'
    MESSAGE_MARKER_TEAM = 'TE'
    MESSAGE_MARKER_STAT_GROUP = 'SG'
    MESSAGE_MARKER_STAT = 'ST'

    _message = None
    _message_data = None

    topic = None
    type = None
    level = None  # sport list or match info
    data = None  # deserialized data

    def __init__(self, decoded_message):
        self._message = decoded_message
        self.topic = unicode(decoded_message.message.topic)
        self.parse()

    def parse(self):
        self._message_data = self._message.message_body.split(self.MESSAGE_CHUNK_DELIM)
        self.data = dict_tree()

        if len(self._message_data) < 2:
            raise Bet365ParseException("Message is empty")

        # get message type
        self.type = self._message_data.pop(0)

        if self.type == self.MESSAGE_INITIAL:
            self.message_deserialize_initial()
        elif self.type == self.MESSAGE_UPDATE:
            #logging.info('Got update message: %s', self._message.message_body)
            self.message_deserialize_update()
        elif self.type == self.MESSAGE_TOPIC_INSERT:
            self.message_deserialize_initial()
        elif self.type == self.MESSAGE_TOPIC_DELETE:
            #logging.info('Got delete message: %s', self._message.message_body)
            pass  # we really don't need to do anything here
        else:
            raise Bet365ParseException(
                "Unknown message type: `%s'. Message: %s" % (self.type, self._message.message_body))

        self._message = None
        self._message_data = None

    def message_deserialize_initial(self):
        parser_tree = []  # stack; last element always represents current container to add elements
        parser_state = [None]  # stack; last element = current state

        # get message level
        self.level = self._message_data[0].split(self.MESSAGE_VAR_DELIM)[0]
        #if self.level not in [self.MESSAGE_MARKER_SPORT_TYPE, self.MESSAGE_MARKER_MATCH]:
        #	raise Bet365ParseException("Unknown message level: `%s'" % self.level)

        parser_tree.append(self.data)

        hierarchy = {
            self.MESSAGE_MARKER_SPORT_TYPE: None,
            self.MESSAGE_MARKER_MATCH: [self.MESSAGE_MARKER_SPORT_TYPE],
            self.MESSAGE_MARKER_MARKET_GROUP: [self.MESSAGE_MARKER_MATCH],
            self.MESSAGE_MARKER_MARKET: [self.MESSAGE_MARKER_MARKET_GROUP, self.MESSAGE_MARKER_MATCH],
            self.MESSAGE_MARKER_PROBABILITY: [self.MESSAGE_MARKER_MARKET],
            self.MESSAGE_MARKER_TEAM_GROUP: [self.MESSAGE_MARKER_MATCH],
            self.MESSAGE_MARKER_TEAM: [self.MESSAGE_MARKER_TEAM_GROUP],
            self.MESSAGE_MARKER_STAT_GROUP: [self.MESSAGE_MARKER_MATCH],
            self.MESSAGE_MARKER_STAT: [self.MESSAGE_MARKER_STAT_GROUP],
        }

        for chunk in self._message_data:
            chunk_vars = chunk.split(self.MESSAGE_VAR_DELIM)
            chunk_type = chunk_vars.pop(0)
            if chunk_type not in hierarchy:
                if len(chunk_type) > 0:
                    logging.debug("Message parser: Unknown marker `%s'", chunk_type)
                continue

            if chunk_type != parser_state[-1]:
                if chunk_type not in parser_state:
                    parents = hierarchy[chunk_type]
                    if parents is not None:
                        new_state_index = None
                        for parent in parents:
                            if parent in parser_state:
                                new_state_index = parser_state.index(parent)
                                break
                        if new_state_index is not None:
                            del parser_state[new_state_index + 1:]
                            del parser_tree[new_state_index + 1:]
                        elif self.type == self.MESSAGE_INITIAL:
                            logging.debug("Strange: can't find parent for chunk_type `%s' in parser_state %s", chunk_type, unicode(parser_state))
                    parser_state.append(chunk_type)
                else:
                    # rollback trees
                    new_state_index = parser_state.index(chunk_type)
                    del parser_state[new_state_index + 1:]
                    del parser_tree[new_state_index:]
            else:
                # up tree one level
                parser_tree.pop()

            # add new tree item and append it to stack
            parser_tree.append(
                parser_tree[-1][
                    '%s_%d' % (parser_state[-1], len(parser_tree[-1]))
                ]
            )

            self.set_vars(parser_tree[-1]['vars'], chunk_vars)

        del parser_tree
        del parser_state

    def message_deserialize_update(self):
        for chunk in self._message_data:
            self.set_vars(self.data, chunk.split(self.MESSAGE_VAR_DELIM))

    def set_vars(self, target, source):
        for var in [x.split(self.MESSAGE_KV_DELIM, 1) for x in source]:
            if len(var) < 2:
                continue
            if var[0] == "TI":
                logging.debug("Server time: %s", var[1])
            #logging.info('Set var %s, oldval = %s, new val = %s', var[0], target[var[0]], var[1])
            target[var[0]] = var[1]

    def data_str(self):
        return json.dumps(self.data, indent=4)


class Bet365ParseException(Exception):
    pass
