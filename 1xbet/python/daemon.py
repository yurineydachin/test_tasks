# -*- coding: utf-8 -*-

import sys
import os
import time
import atexit
import logging
import signal
import fcntl
import subprocess


SIGTERM_SENT = False


# noinspection PyTypeChecker
class Pidfile(object):
    def __init__(self, pidfile, procname):
        self.pid = None
        try:
            self.fd = os.open(pidfile, os.O_CREAT | os.O_RDWR)
        except IOError, e:
            logging.critical("Failed to open pidfile: %s", unicode(e))
            sys.exit("Failed to open pidfile: %s" % unicode(e))
        self.pidfile = pidfile
        self.procname = procname
        try:
            fcntl.flock(self.fd, fcntl.LOCK_EX)
        except IOError, e:
            logging.critical("Failed to lock pidfile `%s': %s", self.pidfile, unicode(e))
            sys.exit("Failed to lock pidfile `%s': %s" % (self.pidfile, unicode(e)))
        logging.debug("Pidfile `%s'", self.pidfile)

    def unlock(self):
        fcntl.flock(self.fd, fcntl.LOCK_UN)

    def delete(self):
        self.unlock()
        os.remove(self.pidfile)
        logging.debug("Pidfile removed: `%s'", self.pidfile)

    def write(self, pid):
        os.ftruncate(self.fd, 0)
        os.write(self.fd, "%d" % int(pid))
        os.fsync(self.fd)

    def kill(self):
        pid = int(os.read(self.fd, 4096))
        os.lseek(self.fd, 0, os.SEEK_SET)

        try:
            os.kill(pid, signal.SIGTERM)
            time.sleep(1)
        except OSError, err:
            err = unicode(err)
            if err.find("No such process") > 0:
                if os.path.exists(self.pidfile):
                    os.remove(self.pidfile)
            else:
                return err

        if self.is_running():
            return "Failed to kill %d" % pid

    def is_running(self):
        contents = os.read(self.fd, 4096)
        os.lseek(self.fd, 0, os.SEEK_SET)

        if not contents:
            return False
        self.pid = contents

        p = subprocess.Popen(["ps", "-o", "cmd", "-p", unicode(int(contents))],
                             stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        stdout, stderr = p.communicate()
        if stdout == "CMD\n":
            return False

        if self.procname in stdout[stdout.find("\n") + 1:]:
            return True

        return False


class Daemon(object):
    def __init__(self, pidfile, stdin='/dev/null', stdout='/dev/null', stderr='/dev/null', debug=False):
        self.stdin = stdin
        self.stdout = stdout
        self.stderr = stderr
        self.debug = debug
        self.pidfile = Pidfile(pidfile, sys.argv[0])

    def daemonize(self):
        if not self.debug:
            try:
                pid = os.fork()
                if pid > 0:
                    sys.exit(0)
            except OSError, e:
                sys.stderr.write("fork #1 failed: %d (%s)\n" % (e.errno, e.strerror))
                sys.exit(1)

            os.chdir("/")
            # noinspection PyArgumentList
            os.setsid()
            os.umask(0)

            try:
                pid = os.fork()
                if pid > 0:
                    sys.exit(0)
            except OSError, e:
                sys.stderr.write("fork #2 failed: %d (%s)\n" % (e.errno, e.strerror))
                sys.exit(1)

        atexit.register(self.pidfile.delete)
        atexit.register(logging.shutdown)
        pid = unicode(os.getpid())
        self.pidfile.write(pid)
        signal.signal(signal.SIGTERM, self.signal_terminate)

        if not self.debug:
            try:
                sys.stdout.flush()
                sys.stderr.flush()
                si = file(self.stdin, 'r')
                so = file(self.stdout, 'a+')
                se = file(self.stderr, 'a+', 0)
                os.dup2(si.fileno(), sys.stdin.fileno())
                os.dup2(so.fileno(), sys.stdout.fileno())
                os.dup2(se.fileno(), sys.stderr.fileno())
            except Exception, e:
                sys.exit("streams tuning failed: %s" % unicode(e))

    def start(self):
        if self.pidfile.is_running():
            self.pidfile.unlock()
            sys.stderr.write("Daemon already running.\n")
            sys.exit(2)

        self.daemonize()
        self.pidfile.unlock()
        logging.info("%s started", sys.argv[0])
        self.run()

    def stop(self):
        if not self.pidfile.is_running():
            self.pidfile.unlock()
            sys.stderr.write("Daemon not running.\n")
            return

        self.shutdown()
        error = self.pidfile.kill()
        if error:
            self.pidfile.unlock()
            sys.exit(error)
        logging.info("%s stopped", sys.argv[0])

    def run(self):
        raise NotImplementedError()

    # noinspection PyUnusedLocal
    def signal_terminate(self, signum, frame):
        logging.info("Got SIGTERM, cleaning up")
        self.shutdown()
        global SIGTERM_SENT
        if not SIGTERM_SENT:
            SIGTERM_SENT = True
            os.killpg(0, signal.SIGTERM)
        logging.info("Terminating")
        sys.exit(0)

    def shutdown(self):
        pass
