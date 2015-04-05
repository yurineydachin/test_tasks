# -*- coding: utf-8 -*-

from collections import defaultdict


def dict_tree():
    return defaultdict(dict_tree)


class Singleton(type):
    def __init__(cls, name, bases, class_dict):
        super(Singleton, cls).__init__(name, bases, class_dict)
        cls.instance = None

    def __call__(cls, *args, **kw):
        if cls.instance is None:
            # noinspection PyArgumentList
            cls.instance = super(Singleton, cls).__call__(*args, **kw)
        return cls.instance


def str_to_boolean(string_repr):
    if type(string_repr) is bool:
        return string_repr
    else:
        return unicode(string_repr).lower() in ['true', 'yes', '1', 'y', 't', 'on']
