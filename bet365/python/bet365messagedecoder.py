# -*- coding: utf-8 -*-

import zlib
import base64

import diffusion


class Bet365DecodedMessage(object):
    message = None
    message_body = None

    def __init__(self, message):
        if message is None:
            return
        self.message = message
        #logging.debug('Got message: type %d, encoding %d, topic %s, data length %d. Decoding' %
        #   (self.message.message_type, self.message.encoding, self.message.topic, self.message.data_length))

        self.message_body = self.decode().decode('utf-8')
        #logging.debug('Decoded message data: %s', self.message_body)

    # Low-level decoding
    def decode(self):
        #logging.debug('Trying to decode message with encoding %d', message.encoding)

        if self.message.encoding == diffusion.DIFFUSION_NONE_ENCODING:
            return self.message.data

        if self.message.encoding == diffusion.DIFFUSION_BASE64_ENCODING:
            # XXX 02.2014: base64 untested!
            return base64.b64decode(self.message.data)

        if self.message.encoding == diffusion.DIFFUSION_COMPRESSED_ENCODING:
            return zlib.decompress(buffer(self.message.data))

        if self.message.encoding == diffusion.DIFFUSION_ENCRYPTED_ENCODING:
            raise NotImplementedError()

        raise ValueError("Unknown encoding %d" % self.message.encoding)
