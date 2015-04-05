"""Wrapper for diffusion.h

Generated with:
/usr/local/bin/ctypesgen.py -ldiffusion diffusion.h diffusion_int.h -o diffusion.py

Do not modify this file.
"""

__docformat__ = 'restructuredtext'

# Begin preamble

import ctypes, os, sys
from ctypes import *

_int_types = (c_int16, c_int32)
if hasattr(ctypes, 'c_int64'):
    # Some builds of ctypes apparently do not have c_int64
    # defined; it's a pretty good bet that these builds do not
    # have 64-bit pointers.
    _int_types += (c_int64,)
for t in _int_types:
    if sizeof(t) == sizeof(c_size_t):
        c_ptrdiff_t = t
del t
del _int_types

class c_void(Structure):
    # c_void_p is a buggy return type, converting to int, so
    # POINTER(None) == c_void_p is actually written as
    # POINTER(c_void), so it can be treated as a real pointer.
    _fields_ = [('dummy', c_int)]

def POINTER(obj):
    p = ctypes.POINTER(obj)

    # Convert None to a real NULL pointer to work around bugs
    # in how ctypes handles None on 64-bit platforms
    if not isinstance(p.from_param, classmethod):
        def from_param(cls, x):
            if x is None:
                return cls()
            else:
                return x
        p.from_param = classmethod(from_param)

    return p

class UserString:
    def __init__(self, seq):
        if isinstance(seq, basestring):
            self.data = seq
        elif isinstance(seq, UserString):
            self.data = seq.data[:]
        else:
            self.data = str(seq)
    def __str__(self): return str(self.data)
    def __repr__(self): return repr(self.data)
    def __int__(self): return int(self.data)
    def __long__(self): return long(self.data)
    def __float__(self): return float(self.data)
    def __complex__(self): return complex(self.data)
    def __hash__(self): return hash(self.data)

    def __cmp__(self, string):
        if isinstance(string, UserString):
            return cmp(self.data, string.data)
        else:
            return cmp(self.data, string)
    def __contains__(self, char):
        return char in self.data

    def __len__(self): return len(self.data)
    def __getitem__(self, index): return self.__class__(self.data[index])
    def __getslice__(self, start, end):
        start = max(start, 0); end = max(end, 0)
        return self.__class__(self.data[start:end])

    def __add__(self, other):
        if isinstance(other, UserString):
            return self.__class__(self.data + other.data)
        elif isinstance(other, basestring):
            return self.__class__(self.data + other)
        else:
            return self.__class__(self.data + str(other))
    def __radd__(self, other):
        if isinstance(other, basestring):
            return self.__class__(other + self.data)
        else:
            return self.__class__(str(other) + self.data)
    def __mul__(self, n):
        return self.__class__(self.data*n)
    __rmul__ = __mul__
    def __mod__(self, args):
        return self.__class__(self.data % args)

    # the following methods are defined in alphabetical order:
    def capitalize(self): return self.__class__(self.data.capitalize())
    def center(self, width, *args):
        return self.__class__(self.data.center(width, *args))
    def count(self, sub, start=0, end=sys.maxint):
        return self.data.count(sub, start, end)
    def decode(self, encoding=None, errors=None): # XXX improve this?
        if encoding:
            if errors:
                return self.__class__(self.data.decode(encoding, errors))
            else:
                return self.__class__(self.data.decode(encoding))
        else:
            return self.__class__(self.data.decode())
    def encode(self, encoding=None, errors=None): # XXX improve this?
        if encoding:
            if errors:
                return self.__class__(self.data.encode(encoding, errors))
            else:
                return self.__class__(self.data.encode(encoding))
        else:
            return self.__class__(self.data.encode())
    def endswith(self, suffix, start=0, end=sys.maxint):
        return self.data.endswith(suffix, start, end)
    def expandtabs(self, tabsize=8):
        return self.__class__(self.data.expandtabs(tabsize))
    def find(self, sub, start=0, end=sys.maxint):
        return self.data.find(sub, start, end)
    def index(self, sub, start=0, end=sys.maxint):
        return self.data.index(sub, start, end)
    def isalpha(self): return self.data.isalpha()
    def isalnum(self): return self.data.isalnum()
    def isdecimal(self): return self.data.isdecimal()
    def isdigit(self): return self.data.isdigit()
    def islower(self): return self.data.islower()
    def isnumeric(self): return self.data.isnumeric()
    def isspace(self): return self.data.isspace()
    def istitle(self): return self.data.istitle()
    def isupper(self): return self.data.isupper()
    def join(self, seq): return self.data.join(seq)
    def ljust(self, width, *args):
        return self.__class__(self.data.ljust(width, *args))
    def lower(self): return self.__class__(self.data.lower())
    def lstrip(self, chars=None): return self.__class__(self.data.lstrip(chars))
    def partition(self, sep):
        return self.data.partition(sep)
    def replace(self, old, new, maxsplit=-1):
        return self.__class__(self.data.replace(old, new, maxsplit))
    def rfind(self, sub, start=0, end=sys.maxint):
        return self.data.rfind(sub, start, end)
    def rindex(self, sub, start=0, end=sys.maxint):
        return self.data.rindex(sub, start, end)
    def rjust(self, width, *args):
        return self.__class__(self.data.rjust(width, *args))
    def rpartition(self, sep):
        return self.data.rpartition(sep)
    def rstrip(self, chars=None): return self.__class__(self.data.rstrip(chars))
    def split(self, sep=None, maxsplit=-1):
        return self.data.split(sep, maxsplit)
    def rsplit(self, sep=None, maxsplit=-1):
        return self.data.rsplit(sep, maxsplit)
    def splitlines(self, keepends=0): return self.data.splitlines(keepends)
    def startswith(self, prefix, start=0, end=sys.maxint):
        return self.data.startswith(prefix, start, end)
    def strip(self, chars=None): return self.__class__(self.data.strip(chars))
    def swapcase(self): return self.__class__(self.data.swapcase())
    def title(self): return self.__class__(self.data.title())
    def translate(self, *args):
        return self.__class__(self.data.translate(*args))
    def upper(self): return self.__class__(self.data.upper())
    def zfill(self, width): return self.__class__(self.data.zfill(width))

class MutableString(UserString):
    """mutable string objects

    Python strings are immutable objects.  This has the advantage, that
    strings may be used as dictionary keys.  If this property isn't needed
    and you insist on changing string values in place instead, you may cheat
    and use MutableString.

    But the purpose of this class is an educational one: to prevent
    people from inventing their own mutable string class derived
    from UserString and than forget thereby to remove (override) the
    __hash__ method inherited from UserString.  This would lead to
    errors that would be very hard to track down.

    A faster and better solution is to rewrite your program using lists."""

    def __init__(self, seq, string=""):
        UserString.__init__(self, seq)
        self.data = string
    def __hash__(self):
        raise TypeError("unhashable type (it is mutable)")
    def __setitem__(self, index, sub):
        if index < 0:
            index += len(self.data)
        if index < 0 or index >= len(self.data): raise IndexError
        self.data = self.data[:index] + sub + self.data[index+1:]
    def __delitem__(self, index):
        if index < 0:
            index += len(self.data)
        if index < 0 or index >= len(self.data): raise IndexError
        self.data = self.data[:index] + self.data[index+1:]
    def __setslice__(self, start, end, sub):
        start = max(start, 0); end = max(end, 0)
        if isinstance(sub, UserString):
            self.data = self.data[:start]+sub.data+self.data[end:]
        elif isinstance(sub, basestring):
            self.data = self.data[:start]+sub+self.data[end:]
        else:
            self.data =  self.data[:start]+str(sub)+self.data[end:]
    def __delslice__(self, start, end):
        start = max(start, 0); end = max(end, 0)
        self.data = self.data[:start] + self.data[end:]
    def immutable(self):
        return UserString(self.data)
    def __iadd__(self, other):
        if isinstance(other, UserString):
            self.data += other.data
        elif isinstance(other, basestring):
            self.data += other
        else:
            self.data += str(other)
        return self
    def __imul__(self, n):
        self.data *= n
        return self


class String(MutableString, Union):

    _fields_ = [('raw', POINTER(c_char)),
                ('data', c_char_p)]

    def __init__(self, obj=""):
        super(String, self).__init__()
        if isinstance(obj, (str, unicode, UserString)):
            self.data = str(obj)
        else:
            self.raw = obj

    def __len__(self):
        return self.data and len(self.data) or 0

    def from_param(cls, obj):
        # Convert None or 0
        if obj is None or obj == 0:
            return cls(POINTER(c_char)())

        # Convert from String
        elif isinstance(obj, String):
            return obj

        # Convert from str
        elif isinstance(obj, str):
            return cls(obj)

        # Convert from c_char_p
        elif isinstance(obj, c_char_p):
            return obj

        # Convert from POINTER(c_char)
        elif isinstance(obj, POINTER(c_char)):
            return obj

        # Convert from raw pointer
        elif isinstance(obj, int):
            return cls(cast(obj, POINTER(c_char)))

        # Convert from object
        else:
            return String.from_param(obj._as_parameter_)
    from_param = classmethod(from_param)

def ReturnString(obj, func=None, arguments=None):
    return String.from_param(obj)

# As of ctypes 1.0, ctypes does not support custom error-checking
# functions on callbacks, nor does it support custom datatypes on
# callbacks, so we must ensure that all callbacks return
# primitive datatypes.
#
# Non-primitive return values wrapped with UNCHECKED won't be
# typechecked, and will be converted to c_void_p.
def UNCHECKED(type):
    if (hasattr(type, "_type_") and isinstance(type._type_, str)
        and type._type_ != "P"):
        return type
    else:
        return c_void_p

# ctypes doesn't have direct support for variadic functions, so we have to write
# our own wrapper class
class _variadic_function(object):
    def __init__(self,func,restype,argtypes):
        self.func=func
        self.func.restype=restype
        self.argtypes=argtypes
    def _as_parameter_(self):
        # So we can pass this variadic function as a function pointer
        return self.func
    def __call__(self,*args):
        fixed_args=[]
        i=0
        for argtype in self.argtypes:
            # Typecheck what we can
            fixed_args.append(argtype.from_param(args[i]))
            i+=1
        return self.func(*fixed_args+list(args[i:]))

# End preamble

_libs = {}
_libdirs = []

# Begin loader

# ----------------------------------------------------------------------------
# Copyright (c) 2008 David James
# Copyright (c) 2006-2008 Alex Holkner
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
#
#  * Redistributions of source code must retain the above copyright
#    notice, this list of conditions and the following disclaimer.
#  * Redistributions in binary form must reproduce the above copyright
#    notice, this list of conditions and the following disclaimer in
#    the documentation and/or other materials provided with the
#    distribution.
#  * Neither the name of pyglet nor the names of its
#    contributors may be used to endorse or promote products
#    derived from this software without specific prior written
#    permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
# COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
# ----------------------------------------------------------------------------

import os.path, re, sys, glob
import ctypes
import ctypes.util

def _environ_path(name):
    if name in os.environ:
        return os.environ[name].split(":")
    else:
        return []

class LibraryLoader(object):
    def __init__(self):
        self.other_dirs=[]

    def load_library(self,libname):
        """Given the name of a library, load it."""
        paths = self.getpaths(libname)

        for path in paths:
            if os.path.exists(path):
                return self.load(path)

        raise ImportError("%s not found." % libname)

    def load(self,path):
        """Given a path to a library, load it."""
        try:
            # Darwin requires dlopen to be called with mode RTLD_GLOBAL instead
            # of the default RTLD_LOCAL.  Without this, you end up with
            # libraries not being loadable, resulting in "Symbol not found"
            # errors
            if sys.platform == 'darwin':
                return ctypes.CDLL(path, ctypes.RTLD_GLOBAL)
            else:
                return ctypes.cdll.LoadLibrary(path)
        except OSError,e:
            raise ImportError(e)

    def getpaths(self,libname):
        """Return a list of paths where the library might be found."""
        if os.path.isabs(libname):
            yield libname

        else:
            for path in self.getplatformpaths(libname):
                yield path

            path = ctypes.util.find_library(libname)
            if path: yield path

    def getplatformpaths(self, libname):
        return []

# Darwin (Mac OS X)

class DarwinLibraryLoader(LibraryLoader):
    name_formats = ["lib%s.dylib", "lib%s.so", "lib%s.bundle", "%s.dylib",
                "%s.so", "%s.bundle", "%s"]

    def getplatformpaths(self,libname):
        if os.path.pathsep in libname:
            names = [libname]
        else:
            names = [format % libname for format in self.name_formats]

        for dir in self.getdirs(libname):
            for name in names:
                yield os.path.join(dir,name)

    def getdirs(self,libname):
        """Implements the dylib search as specified in Apple documentation:

        http://developer.apple.com/documentation/DeveloperTools/Conceptual/
            DynamicLibraries/Articles/DynamicLibraryUsageGuidelines.html

        Before commencing the standard search, the method first checks
        the bundle's ``Frameworks`` directory if the application is running
        within a bundle (OS X .app).
        """

        dyld_fallback_library_path = _environ_path("DYLD_FALLBACK_LIBRARY_PATH")
        if not dyld_fallback_library_path:
            dyld_fallback_library_path = [os.path.expanduser('~/lib'),
                                          '/usr/local/lib', '/usr/lib']

        dirs = []

        if '/' in libname:
            dirs.extend(_environ_path("DYLD_LIBRARY_PATH"))
        else:
            dirs.extend(_environ_path("LD_LIBRARY_PATH"))
            dirs.extend(_environ_path("DYLD_LIBRARY_PATH"))

        dirs.extend(self.other_dirs)
        dirs.append(".")

        if hasattr(sys, 'frozen') and sys.frozen == 'macosx_app':
            dirs.append(os.path.join(
                os.environ['RESOURCEPATH'],
                '..',
                'Frameworks'))

        dirs.extend(dyld_fallback_library_path)

        return dirs

# Posix

class PosixLibraryLoader(LibraryLoader):
    _ld_so_cache = None

    def _create_ld_so_cache(self):
        # Recreate search path followed by ld.so.  This is going to be
        # slow to build, and incorrect (ld.so uses ld.so.cache, which may
        # not be up-to-date).  Used only as fallback for distros without
        # /sbin/ldconfig.
        #
        # We assume the DT_RPATH and DT_RUNPATH binary sections are omitted.

        directories = []
        for name in ("LD_LIBRARY_PATH",
                     "SHLIB_PATH", # HPUX
                     "LIBPATH", # OS/2, AIX
                     "LIBRARY_PATH", # BE/OS
                    ):
            if name in os.environ:
                directories.extend(os.environ[name].split(os.pathsep))
        directories.extend(self.other_dirs)
        directories.append(".")

        try: directories.extend([dir.strip() for dir in open('/etc/ld.so.conf')])
        except IOError: pass

        directories.extend(['/lib', '/usr/lib', '/lib64', '/usr/lib64'])

        cache = {}
        lib_re = re.compile(r'lib(.*)\.s[ol]')
        ext_re = re.compile(r'\.s[ol]$')
        for dir in directories:
            try:
                for path in glob.glob("%s/*.s[ol]*" % dir):
                    file = os.path.basename(path)

                    # Index by filename
                    if file not in cache:
                        cache[file] = path

                    # Index by library name
                    match = lib_re.match(file)
                    if match:
                        library = match.group(1)
                        if library not in cache:
                            cache[library] = path
            except OSError:
                pass

        self._ld_so_cache = cache

    def getplatformpaths(self, libname):
        if self._ld_so_cache is None:
            self._create_ld_so_cache()

        result = self._ld_so_cache.get(libname)
        if result: yield result

        path = ctypes.util.find_library(libname)
        if path: yield os.path.join("/lib",path)

# Windows

class _WindowsLibrary(object):
    def __init__(self, path):
        self.cdll = ctypes.cdll.LoadLibrary(path)
        self.windll = ctypes.windll.LoadLibrary(path)

    def __getattr__(self, name):
        try: return getattr(self.cdll,name)
        except AttributeError:
            try: return getattr(self.windll,name)
            except AttributeError:
                raise

class WindowsLibraryLoader(LibraryLoader):
    name_formats = ["%s.dll", "lib%s.dll", "%slib.dll"]

    def load_library(self, libname):
        try:
            result = LibraryLoader.load_library(self, libname)
        except ImportError:
            result = None
            if os.path.sep not in libname:
                for name in self.name_formats:
                    try:
                        result = getattr(ctypes.cdll, name % libname)
                        if result:
                            break
                    except WindowsError:
                        result = None
            if result is None:
                try:
                    result = getattr(ctypes.cdll, libname)
                except WindowsError:
                    result = None
            if result is None:
                raise ImportError("%s not found." % libname)
        return result

    def load(self, path):
        return _WindowsLibrary(path)

    def getplatformpaths(self, libname):
        if os.path.sep not in libname:
            for name in self.name_formats:
                dll_in_current_dir = os.path.abspath(name % libname)
                if os.path.exists(dll_in_current_dir):
                    yield dll_in_current_dir
                path = ctypes.util.find_library(name % libname)
                if path:
                    yield path

# Platform switching

# If your value of sys.platform does not appear in this dict, please contact
# the Ctypesgen maintainers.

loaderclass = {
    "darwin":   DarwinLibraryLoader,
    "cygwin":   WindowsLibraryLoader,
    "win32":    WindowsLibraryLoader
}

loader = loaderclass.get(sys.platform, PosixLibraryLoader)()

def add_library_search_dirs(other_dirs):
    loader.other_dirs = other_dirs

load_library = loader.load_library

del loaderclass

# End loader

add_library_search_dirs([])

# Begin libraries

_libs["diffusion"] = load_library("diffusion")

# 1 libraries
# End libraries

# No modules

# /usr/include/x86_64-linux-gnu/bits/pthreadtypes.h: 61
class struct___pthread_internal_list(Structure):
    pass

struct___pthread_internal_list.__slots__ = [
    '__prev',
    '__next',
]
struct___pthread_internal_list._fields_ = [
    ('__prev', POINTER(struct___pthread_internal_list)),
    ('__next', POINTER(struct___pthread_internal_list)),
]

__pthread_list_t = struct___pthread_internal_list # /usr/include/x86_64-linux-gnu/bits/pthreadtypes.h: 65

# /usr/include/x86_64-linux-gnu/bits/pthreadtypes.h: 78
class struct___pthread_mutex_s(Structure):
    pass

struct___pthread_mutex_s.__slots__ = [
    '__lock',
    '__count',
    '__owner',
    '__nusers',
    '__kind',
    '__spins',
    '__list',
]
struct___pthread_mutex_s._fields_ = [
    ('__lock', c_int),
    ('__count', c_uint),
    ('__owner', c_int),
    ('__nusers', c_uint),
    ('__kind', c_int),
    ('__spins', c_int),
    ('__list', __pthread_list_t),
]

# /usr/include/x86_64-linux-gnu/bits/pthreadtypes.h: 104
class union_anon_4(Union):
    pass

union_anon_4.__slots__ = [
    '__data',
    '__size',
    '__align',
]
union_anon_4._fields_ = [
    ('__data', struct___pthread_mutex_s),
    ('__size', c_char * 40),
    ('__align', c_long),
]

pthread_mutex_t = union_anon_4 # /usr/include/x86_64-linux-gnu/bits/pthreadtypes.h: 104

# /usr/include/string.h: 65
if hasattr(_libs['diffusion'], 'memset'):
    memset = _libs['diffusion'].memset
    memset.argtypes = [POINTER(None), c_int, c_size_t]
    memset.restype = POINTER(None)

# /home/comnimh/nas/Diffusion/c-api/llist.h: 6
class struct__lnode(Structure):
    pass

struct__lnode.__slots__ = [
    'data',
    'data_length',
    'prev',
    'next',
]
struct__lnode._fields_ = [
    ('data', POINTER(None)),
    ('data_length', c_long),
    ('prev', POINTER(struct__lnode)),
    ('next', POINTER(struct__lnode)),
]

# /home/comnimh/nas/Diffusion/c-api/llist.h: 18
class struct__llist(Structure):
    pass

struct__llist.__slots__ = [
    'size',
    'first',
    'last',
    '_mutex',
]
struct__llist._fields_ = [
    ('size', c_int),
    ('first', POINTER(struct__lnode)),
    ('last', POINTER(struct__lnode)),
    ('_mutex', pthread_mutex_t),
]

LLIST = struct__llist # /home/comnimh/nas/Diffusion/c-api/llist.h: 18

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 73
class struct_anon_25(Structure):
    pass

struct_anon_25.__slots__ = [
    'principal',
    'credentials',
]
struct_anon_25._fields_ = [
    ('principal', String),
    ('credentials', String),
]

SECURITY_CREDENTIALS = struct_anon_25 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 73

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 80
class struct_anon_26(Structure):
    pass

struct_anon_26.__slots__ = [
    'protocol_byte',
    'protocol_version',
    'response',
    'message_length_size',
]
struct_anon_26._fields_ = [
    ('protocol_byte', c_ubyte),
    ('protocol_version', c_ubyte),
    ('response', c_ubyte),
    ('message_length_size', c_ubyte),
]

DIFFUSION_CONNECTION_RESPONSE = struct_anon_26 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 80

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 91
class struct_anon_27(Structure):
    pass

struct_anon_27.__slots__ = [
    'type',
    'current_page_number',
    'last_page_number',
    'total_number_of_lines',
]
struct_anon_27._fields_ = [
    ('type', c_char),
    ('current_page_number', c_int),
    ('last_page_number', c_int),
    ('total_number_of_lines', c_int),
]

DIFFUSION_PAGE_NOTIFICATION = struct_anon_27 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 91

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 102
class struct_anon_28(Structure):
    pass

struct_anon_28.__slots__ = [
    'protocol',
    'hostname',
    'port',
    'security_credentials',
    'topic_set',
    'retry_count',
    'retry_delay',
    'connection_attempt_count',
]
struct_anon_28._fields_ = [
    ('protocol', String),
    ('hostname', String),
    ('port', c_int),
    ('security_credentials', POINTER(SECURITY_CREDENTIALS)),
    ('topic_set', POINTER(LLIST)),
    ('retry_count', c_int),
    ('retry_delay', c_int),
    ('connection_attempt_count', c_int),
]

DIFFUSION_SERVER_DETAILS = struct_anon_28 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 102

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 120
class struct_anon_29(Structure):
    pass

struct_anon_29.__slots__ = [
    'fd',
    'protocol_byte',
    'protocol_version',
    'response',
    'message_length_size',
    'client_id',
    'server_details',
    'free_server_details',
    'last_interaction_ms',
    'topic_alias_list',
    'fragmented_message_list',
    'topic_listener_list',
    'service_listener_list',
    '_mutex_write',
    '_mutex_read',
]
struct_anon_29._fields_ = [
    ('fd', c_int),
    ('protocol_byte', c_ubyte),
    ('protocol_version', c_ubyte),
    ('response', c_ubyte),
    ('message_length_size', c_ubyte),
    ('client_id', String),
    ('server_details', POINTER(DIFFUSION_SERVER_DETAILS)),
    ('free_server_details', c_int),
    ('last_interaction_ms', c_long),
    ('topic_alias_list', POINTER(LLIST)),
    ('fragmented_message_list', POINTER(LLIST)),
    ('topic_listener_list', POINTER(LLIST)),
    ('service_listener_list', POINTER(LLIST)),
    ('_mutex_write', pthread_mutex_t),
    ('_mutex_read', pthread_mutex_t),
]

DIFFUSION_CONNECTION = struct_anon_29 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 120

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 132
class struct_anon_30(Structure):
    pass

struct_anon_30.__slots__ = [
    'message_type',
    'encoding',
    'is_fragment',
    'fragment_size',
    'ack_id',
    'topic',
    'header_list',
    'data',
    'data_length',
]
struct_anon_30._fields_ = [
    ('message_type', c_ubyte),
    ('encoding', c_ubyte),
    ('is_fragment', c_int),
    ('fragment_size', c_int),
    ('ack_id', String),
    ('topic', String),
    ('header_list', POINTER(LLIST)),
    ('data', POINTER(c_char)),
    ('data_length', c_ulong),
]

DIFFUSION_MESSAGE = struct_anon_30 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 132

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 148
class struct_anon_31(Structure):
    pass

DIFFUSION_CALLBACK_MESSAGE_PASSED = CFUNCTYPE(UNCHECKED(None), POINTER(DIFFUSION_MESSAGE))
DIFFUSION_CALLBACK_CONNECTION_PASSED = CFUNCTYPE(UNCHECKED(None), POINTER(DIFFUSION_CONNECTION))
DIFFUSION_CALLBACK_NOTHING_PASSED = CFUNCTYPE(UNCHECKED(None), )

struct_anon_31.__slots__ = [
    'on_initial_load',
    'on_delta',
    'on_ping_server',
    'on_ping_client',
    'on_fetch_reply',
    'on_ack',
    'on_fragment',
    'on_fragment_cancel',
    'on_unhandled_message',
    'on_disconnect',
    'on_connect',
    'on_command_topic_load',
    'on_command_topic_notification',
]
struct_anon_31._fields_ = [
    ('on_initial_load', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_delta', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_ping_server', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_ping_client', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_fetch_reply', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_ack', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_fragment', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_fragment_cancel', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_unhandled_message', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_disconnect', DIFFUSION_CALLBACK_NOTHING_PASSED),
    ('on_connect', DIFFUSION_CALLBACK_CONNECTION_PASSED),
    ('on_command_topic_load', DIFFUSION_CALLBACK_MESSAGE_PASSED),
    ('on_command_topic_notification', DIFFUSION_CALLBACK_MESSAGE_PASSED),
]

DIFFUSION_CALLBACKS = struct_anon_31 # /home/comnimh/nas/Diffusion/c-api/diffusion.h: 148

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 152
try:
    _diffusion_debug = (c_int).in_dll(_libs['diffusion'], '_diffusion_debug')
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 167
if hasattr(_libs['diffusion'], 'diff_connect'):
    diff_connect = _libs['diffusion'].diff_connect
    diff_connect.argtypes = [String, c_int, POINTER(SECURITY_CREDENTIALS)]
    diff_connect.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 175
if hasattr(_libs['diffusion'], 'diff_connect_server'):
    diff_connect_server = _libs['diffusion'].diff_connect_server
    diff_connect_server.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS)]
    diff_connect_server.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 188
if hasattr(_libs['diffusion'], 'diff_reconnect_server'):
    diff_reconnect_server = _libs['diffusion'].diff_reconnect_server
    diff_reconnect_server.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS), String]
    diff_reconnect_server.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 203
if hasattr(_libs['diffusion'], 'diff_connect_cascade'):
    diff_connect_cascade = _libs['diffusion'].diff_connect_cascade
    diff_connect_cascade.argtypes = [POINTER(LLIST), c_int, String]
    diff_connect_cascade.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 211
if hasattr(_libs['diffusion'], 'diff_evt_pub_connect_server'):
    diff_evt_pub_connect_server = _libs['diffusion'].diff_evt_pub_connect_server
    diff_evt_pub_connect_server.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS)]
    diff_evt_pub_connect_server.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 219
if hasattr(_libs['diffusion'], 'diff_connect_request'):
    diff_connect_request = _libs['diffusion'].diff_connect_request
    diff_connect_request.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_connect_request.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 228
if hasattr(_libs['diffusion'], 'diff_evt_pub_connect_request'):
    diff_evt_pub_connect_request = _libs['diffusion'].diff_evt_pub_connect_request
    diff_evt_pub_connect_request.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_evt_pub_connect_request.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 236
if hasattr(_libs['diffusion'], 'diff_disconnect'):
    diff_disconnect = _libs['diffusion'].diff_disconnect
    diff_disconnect.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_disconnect.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 244
if hasattr(_libs['diffusion'], 'diff_reconnect'):
    diff_reconnect = _libs['diffusion'].diff_reconnect
    diff_reconnect.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_reconnect.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 251
if hasattr(_libs['diffusion'], 'diff_free_connection'):
    diff_free_connection = _libs['diffusion'].diff_free_connection
    diff_free_connection.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_free_connection.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 269
if hasattr(_libs['diffusion'], 'diff_subscribe'):
    diff_subscribe = _libs['diffusion'].diff_subscribe
    diff_subscribe.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_subscribe.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 282
if hasattr(_libs['diffusion'], 'diff_unsubscribe'):
    diff_unsubscribe = _libs['diffusion'].diff_unsubscribe
    diff_unsubscribe.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_unsubscribe.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 290
if hasattr(_libs['diffusion'], 'diff_ping'):
    diff_ping = _libs['diffusion'].diff_ping
    diff_ping.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_ping.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 299
if hasattr(_libs['diffusion'], 'diff_ping_response'):
    diff_ping_response = _libs['diffusion'].diff_ping_response
    diff_ping_response.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    diff_ping_response.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 308
if hasattr(_libs['diffusion'], 'diff_ack_response'):
    diff_ack_response = _libs['diffusion'].diff_ack_response
    diff_ack_response.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    diff_ack_response.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 319
if hasattr(_libs['diffusion'], 'diff_send_message'):
    diff_send_message = _libs['diffusion'].diff_send_message
    diff_send_message.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    diff_send_message.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 329
if hasattr(_libs['diffusion'], 'diff_send_data'):
    diff_send_data = _libs['diffusion'].diff_send_data
    diff_send_data.argtypes = [POINTER(DIFFUSION_CONNECTION), String, String]
    diff_send_data.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 340
if hasattr(_libs['diffusion'], 'diff_send_data_length'):
    diff_send_data_length = _libs['diffusion'].diff_send_data_length
    diff_send_data_length.argtypes = [POINTER(DIFFUSION_CONNECTION), String, String, c_long]
    diff_send_data_length.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 349
if hasattr(_libs['diffusion'], 'diff_fetch'):
    diff_fetch = _libs['diffusion'].diff_fetch
    diff_fetch.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_fetch.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 360
if hasattr(_libs['diffusion'], 'diff_fetch_correlated'):
    diff_fetch_correlated = _libs['diffusion'].diff_fetch_correlated
    diff_fetch_correlated.argtypes = [POINTER(DIFFUSION_CONNECTION), String, POINTER(LLIST)]
    diff_fetch_correlated.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 369
if hasattr(_libs['diffusion'], 'diff_send_credentials'):
    diff_send_credentials = _libs['diffusion'].diff_send_credentials
    diff_send_credentials.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(SECURITY_CREDENTIALS)]
    diff_send_credentials.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 380
if hasattr(_libs['diffusion'], 'diff_send_command'):
    diff_send_command = _libs['diffusion'].diff_send_command
    diff_send_command.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE), String, String]
    diff_send_command.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 391
if hasattr(_libs['diffusion'], 'diff_page_open'):
    diff_page_open = _libs['diffusion'].diff_page_open
    diff_page_open.argtypes = [POINTER(DIFFUSION_CONNECTION), String, c_int, c_int]
    diff_page_open.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 400
if hasattr(_libs['diffusion'], 'diff_page_refresh'):
    diff_page_refresh = _libs['diffusion'].diff_page_refresh
    diff_page_refresh.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_page_refresh.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 409
if hasattr(_libs['diffusion'], 'diff_page_next'):
    diff_page_next = _libs['diffusion'].diff_page_next
    diff_page_next.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_page_next.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 418
if hasattr(_libs['diffusion'], 'diff_page_prior'):
    diff_page_prior = _libs['diffusion'].diff_page_prior
    diff_page_prior.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_page_prior.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 427
if hasattr(_libs['diffusion'], 'diff_page_first'):
    diff_page_first = _libs['diffusion'].diff_page_first
    diff_page_first.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_page_first.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 436
if hasattr(_libs['diffusion'], 'diff_page_last'):
    diff_page_last = _libs['diffusion'].diff_page_last
    diff_page_last.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_page_last.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 448
if hasattr(_libs['diffusion'], 'diff_page_number'):
    diff_page_number = _libs['diffusion'].diff_page_number
    diff_page_number.argtypes = [POINTER(DIFFUSION_CONNECTION), String, c_int]
    diff_page_number.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 457
if hasattr(_libs['diffusion'], 'diff_page_close'):
    diff_page_close = _libs['diffusion'].diff_page_close
    diff_page_close.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_page_close.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 473
if hasattr(_libs['diffusion'], 'diff_wait_for_message'):
    diff_wait_for_message = _libs['diffusion'].diff_wait_for_message
    diff_wait_for_message.argtypes = [POINTER(DIFFUSION_CONNECTION), c_long]
    diff_wait_for_message.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 485
if hasattr(_libs['diffusion'], 'diff_read_message'):
    diff_read_message = _libs['diffusion'].diff_read_message
    diff_read_message.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    diff_read_message.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 501
if hasattr(_libs['diffusion'], 'diff_decode_message'):
    diff_decode_message = _libs['diffusion'].diff_decode_message
    diff_decode_message.argtypes = [c_int, String, POINTER(DIFFUSION_CONNECTION)]
    diff_decode_message.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 513
if hasattr(_libs['diffusion'], 'diff_create_message'):
    diff_create_message = _libs['diffusion'].diff_create_message
    diff_create_message.argtypes = [POINTER(LLIST), String]
    diff_create_message.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 526
if hasattr(_libs['diffusion'], 'diff_create_message_length'):
    diff_create_message_length = _libs['diffusion'].diff_create_message_length
    diff_create_message_length.argtypes = [POINTER(LLIST), String, c_long]
    diff_create_message_length.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 534
if hasattr(_libs['diffusion'], 'diff_dup_message'):
    diff_dup_message = _libs['diffusion'].diff_dup_message
    diff_dup_message.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    diff_dup_message.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 541
if hasattr(_libs['diffusion'], 'diff_free_message'):
    diff_free_message = _libs['diffusion'].diff_free_message
    diff_free_message.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    diff_free_message.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 551
if hasattr(_libs['diffusion'], 'diff_create_page_notification'):
    diff_create_page_notification = _libs['diffusion'].diff_create_page_notification
    diff_create_page_notification.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    diff_create_page_notification.restype = POINTER(DIFFUSION_PAGE_NOTIFICATION)

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 558
if hasattr(_libs['diffusion'], 'diff_free_page_notification'):
    diff_free_page_notification = _libs['diffusion'].diff_free_page_notification
    diff_free_page_notification.argtypes = [POINTER(DIFFUSION_PAGE_NOTIFICATION)]
    diff_free_page_notification.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 566
if hasattr(_libs['diffusion'], 'diff_msg_add_header'):
    diff_msg_add_header = _libs['diffusion'].diff_msg_add_header
    diff_msg_add_header.argtypes = [POINTER(DIFFUSION_MESSAGE), String]
    diff_msg_add_header.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 573
if hasattr(_libs['diffusion'], 'diff_msg_request_ack'):
    diff_msg_request_ack = _libs['diffusion'].diff_msg_request_ack
    diff_msg_request_ack.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    diff_msg_request_ack.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 580
if hasattr(_libs['diffusion'], 'diff_debug_message'):
    diff_debug_message = _libs['diffusion'].diff_debug_message
    diff_debug_message.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    diff_debug_message.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 590
if hasattr(_libs['diffusion'], 'diff_loop'):
    diff_loop = _libs['diffusion'].diff_loop
    diff_loop.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_CALLBACKS)]
    diff_loop.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 611
if hasattr(_libs['diffusion'], 'diff_main'):
    diff_main = _libs['diffusion'].diff_main
    diff_main.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(LLIST), POINTER(DIFFUSION_CALLBACKS), c_int]
    diff_main.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 620
if hasattr(_libs['diffusion'], 'diff_dispatch'):
    diff_dispatch = _libs['diffusion'].diff_dispatch
    diff_dispatch.argtypes = [POINTER(DIFFUSION_MESSAGE), POINTER(DIFFUSION_CALLBACKS), POINTER(DIFFUSION_CONNECTION)]
    diff_dispatch.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 632
if hasattr(_libs['diffusion'], 'diff_add_topic_listener'):
    diff_add_topic_listener = _libs['diffusion'].diff_add_topic_listener
    diff_add_topic_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), String, CFUNCTYPE(UNCHECKED(c_int), POINTER(DIFFUSION_MESSAGE))]
    if sizeof(c_int) == sizeof(c_void_p):
        diff_add_topic_listener.restype = ReturnString
    else:
        diff_add_topic_listener.restype = String
        diff_add_topic_listener.errcheck = ReturnString

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 641
if hasattr(_libs['diffusion'], 'diff_remove_topic_listener'):
    diff_remove_topic_listener = _libs['diffusion'].diff_remove_topic_listener
    diff_remove_topic_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_remove_topic_listener.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 653
if hasattr(_libs['diffusion'], 'diff_add_service_listener'):
    diff_add_service_listener = _libs['diffusion'].diff_add_service_listener
    diff_add_service_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), String, CFUNCTYPE(UNCHECKED(c_int), POINTER(DIFFUSION_MESSAGE))]
    if sizeof(c_int) == sizeof(c_void_p):
        diff_add_service_listener.restype = ReturnString
    else:
        diff_add_service_listener.restype = String
        diff_add_service_listener.errcheck = ReturnString

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 661
if hasattr(_libs['diffusion'], 'diff_remove_service_listener'):
    diff_remove_service_listener = _libs['diffusion'].diff_remove_service_listener
    diff_remove_service_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), String]
    diff_remove_service_listener.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 9
class struct__pair(Structure):
    pass

struct__pair.__slots__ = [
    'key',
    'value',
]
struct__pair._fields_ = [
    ('key', POINTER(None)),
    ('value', POINTER(None)),
]

PAIR = struct__pair # /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 9

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 14
class struct__fragment(Structure):
    pass

struct__fragment.__slots__ = [
    'data',
    'data_length',
]
struct__fragment._fields_ = [
    ('data', POINTER(None)),
    ('data_length', c_ulong),
]

FRAGMENT = struct__fragment # /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 14

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 16
for _lib in _libs.itervalues():
    if not hasattr(_lib, '_compare_keys'):
        continue
    _compare_keys = _lib._compare_keys
    _compare_keys.argtypes = [POINTER(None), POINTER(None)]
    _compare_keys.restype = c_int
    break

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 18
if hasattr(_libs['diffusion'], '_diff_open_connection'):
    _diff_open_connection = _libs['diffusion']._diff_open_connection
    _diff_open_connection.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS), String]
    _diff_open_connection.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 20
if hasattr(_libs['diffusion'], '_diff_connect_request'):
    _diff_connect_request = _libs['diffusion']._diff_connect_request
    _diff_connect_request.argtypes = [POINTER(DIFFUSION_CONNECTION), c_char]
    _diff_connect_request.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 22
if hasattr(_libs['diffusion'], '_diff_ping'):
    _diff_ping = _libs['diffusion']._diff_ping
    _diff_ping.argtypes = [POINTER(DIFFUSION_CONNECTION), c_char, String]
    _diff_ping.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 23
if hasattr(_libs['diffusion'], '_diff_page_command'):
    _diff_page_command = _libs['diffusion']._diff_page_command
    _diff_page_command.argtypes = [POINTER(DIFFUSION_CONNECTION), String, String, POINTER(LLIST)]
    _diff_page_command.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 25
if hasattr(_libs['diffusion'], '_diff_open_socket'):
    _diff_open_socket = _libs['diffusion']._diff_open_socket
    _diff_open_socket.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    _diff_open_socket.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 26
if hasattr(_libs['diffusion'], '_diff_enc_msg_length_size'):
    _diff_enc_msg_length_size = _libs['diffusion']._diff_enc_msg_length_size
    _diff_enc_msg_length_size.argtypes = [String, c_int, c_int]
    _diff_enc_msg_length_size.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 27
if hasattr(_libs['diffusion'], '_diff_write'):
    _diff_write = _libs['diffusion']._diff_write
    _diff_write.argtypes = [POINTER(DIFFUSION_CONNECTION), String, c_int]
    _diff_write.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 28
if hasattr(_libs['diffusion'], '_diff_read'):
    _diff_read = _libs['diffusion']._diff_read
    _diff_read.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(None), c_int]
    _diff_read.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 29
if hasattr(_libs['diffusion'], '_diff_read_to_delim'):
    _diff_read_to_delim = _libs['diffusion']._diff_read_to_delim
    _diff_read_to_delim.argtypes = [POINTER(DIFFUSION_CONNECTION), c_char]
    if sizeof(c_int) == sizeof(c_void_p):
        _diff_read_to_delim.restype = ReturnString
    else:
        _diff_read_to_delim.restype = String
        _diff_read_to_delim.errcheck = ReturnString

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 30
if hasattr(_libs['diffusion'], '_generate_ack_id'):
    _generate_ack_id = _libs['diffusion']._generate_ack_id
    _generate_ack_id.argtypes = []
    if sizeof(c_int) == sizeof(c_void_p):
        _generate_ack_id.restype = ReturnString
    else:
        _generate_ack_id.restype = String
        _generate_ack_id.errcheck = ReturnString

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 32
if hasattr(_libs['diffusion'], '_diff_header_size'):
    _diff_header_size = _libs['diffusion']._diff_header_size
    _diff_header_size.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    _diff_header_size.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 33
if hasattr(_libs['diffusion'], '_diff_is_fragmented_type'):
    _diff_is_fragmented_type = _libs['diffusion']._diff_is_fragmented_type
    _diff_is_fragmented_type.argtypes = [c_ubyte]
    _diff_is_fragmented_type.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 34
if hasattr(_libs['diffusion'], '_diff_standard_msg_type'):
    _diff_standard_msg_type = _libs['diffusion']._diff_standard_msg_type
    _diff_standard_msg_type.argtypes = [c_ubyte]
    _diff_standard_msg_type.restype = c_ubyte

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 35
if hasattr(_libs['diffusion'], '_diff_fragmented_msg_type'):
    _diff_fragmented_msg_type = _libs['diffusion']._diff_fragmented_msg_type
    _diff_fragmented_msg_type.argtypes = [c_ubyte]
    _diff_fragmented_msg_type.restype = c_ubyte

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 36
if hasattr(_libs['diffusion'], '_diff_requires_fragmentation'):
    _diff_requires_fragmentation = _libs['diffusion']._diff_requires_fragmentation
    _diff_requires_fragmentation.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    _diff_requires_fragmentation.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 38
if hasattr(_libs['diffusion'], '_diff_fragment'):
    _diff_fragment = _libs['diffusion']._diff_fragment
    _diff_fragment.argtypes = [POINTER(DIFFUSION_MESSAGE)]
    _diff_fragment.restype = POINTER(LLIST)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 39
if hasattr(_libs['diffusion'], '_process_fragment'):
    _process_fragment = _libs['diffusion']._process_fragment
    _process_fragment.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    _process_fragment.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 40
if hasattr(_libs['diffusion'], '_stitch_fragments'):
    _stitch_fragments = _libs['diffusion']._stitch_fragments
    _stitch_fragments.argtypes = [POINTER(LLIST)]
    _stitch_fragments.restype = POINTER(DIFFUSION_MESSAGE)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 41
if hasattr(_libs['diffusion'], '_cancel_fragment'):
    _cancel_fragment = _libs['diffusion']._cancel_fragment
    _cancel_fragment.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    _cancel_fragment.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 43
if hasattr(_libs['diffusion'], '_load_topic_aliases'):
    _load_topic_aliases = _libs['diffusion']._load_topic_aliases
    _load_topic_aliases.argtypes = [POINTER(LLIST), POINTER(DIFFUSION_MESSAGE)]
    _load_topic_aliases.restype = POINTER(LLIST)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 44
if hasattr(_libs['diffusion'], '_alias_table_add'):
    _alias_table_add = _libs['diffusion']._alias_table_add
    _alias_table_add.argtypes = [POINTER(LLIST), String, String]
    _alias_table_add.restype = POINTER(LLIST)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 45
if hasattr(_libs['diffusion'], '_alias_table_clear'):
    _alias_table_clear = _libs['diffusion']._alias_table_clear
    _alias_table_clear.argtypes = [POINTER(LLIST)]
    _alias_table_clear.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 46
if hasattr(_libs['diffusion'], '_alias_table_lookup'):
    _alias_table_lookup = _libs['diffusion']._alias_table_lookup
    _alias_table_lookup.argtypes = [POINTER(LLIST), String]
    if sizeof(c_int) == sizeof(c_void_p):
        _alias_table_lookup.restype = ReturnString
    else:
        _alias_table_lookup.restype = String
        _alias_table_lookup.errcheck = ReturnString

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 48
if hasattr(_libs['diffusion'], '_diff_next_server'):
    _diff_next_server = _libs['diffusion']._diff_next_server
    _diff_next_server.argtypes = [POINTER(LLIST), c_int]
    _diff_next_server.restype = POINTER(DIFFUSION_SERVER_DETAILS)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 50
if hasattr(_libs['diffusion'], '_diff_dup_connection'):
    _diff_dup_connection = _libs['diffusion']._diff_dup_connection
    _diff_dup_connection.argtypes = [POINTER(DIFFUSION_CONNECTION)]
    _diff_dup_connection.restype = POINTER(DIFFUSION_CONNECTION)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 51
if hasattr(_libs['diffusion'], '_diff_dup_server_details'):
    _diff_dup_server_details = _libs['diffusion']._diff_dup_server_details
    _diff_dup_server_details.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS)]
    _diff_dup_server_details.restype = POINTER(DIFFUSION_SERVER_DETAILS)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 52
if hasattr(_libs['diffusion'], '_diff_dup_security_credentials'):
    _diff_dup_security_credentials = _libs['diffusion']._diff_dup_security_credentials
    _diff_dup_security_credentials.argtypes = [POINTER(SECURITY_CREDENTIALS)]
    _diff_dup_security_credentials.restype = POINTER(SECURITY_CREDENTIALS)

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 54
if hasattr(_libs['diffusion'], '_diff_free_security_credentials'):
    _diff_free_security_credentials = _libs['diffusion']._diff_free_security_credentials
    _diff_free_security_credentials.argtypes = [POINTER(SECURITY_CREDENTIALS)]
    _diff_free_security_credentials.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 56
if hasattr(_libs['diffusion'], '_diff_free_server_details'):
    _diff_free_server_details = _libs['diffusion']._diff_free_server_details
    _diff_free_server_details.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS)]
    _diff_free_server_details.restype = None

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 58
if hasattr(_libs['diffusion'], '_diff_server_details_to_string'):
    _diff_server_details_to_string = _libs['diffusion']._diff_server_details_to_string
    _diff_server_details_to_string.argtypes = [POINTER(DIFFUSION_SERVER_DETAILS)]
    if sizeof(c_int) == sizeof(c_void_p):
        _diff_server_details_to_string.restype = ReturnString
    else:
        _diff_server_details_to_string.restype = String
        _diff_server_details_to_string.errcheck = ReturnString

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 60
if hasattr(_libs['diffusion'], '_diff_dispatch_listener'):
    _diff_dispatch_listener = _libs['diffusion']._diff_dispatch_listener
    _diff_dispatch_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    _diff_dispatch_listener.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 61
if hasattr(_libs['diffusion'], '_diff_dispatch_service_listener'):
    _diff_dispatch_service_listener = _libs['diffusion']._diff_dispatch_service_listener
    _diff_dispatch_service_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    _diff_dispatch_service_listener.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 62
if hasattr(_libs['diffusion'], '_diff_dispatch_topic_listener'):
    _diff_dispatch_topic_listener = _libs['diffusion']._diff_dispatch_topic_listener
    _diff_dispatch_topic_listener.argtypes = [POINTER(DIFFUSION_CONNECTION), POINTER(DIFFUSION_MESSAGE)]
    _diff_dispatch_topic_listener.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 64
if hasattr(_libs['diffusion'], '_diff_check_protocol_version'):
    _diff_check_protocol_version = _libs['diffusion']._diff_check_protocol_version
    _diff_check_protocol_version.argtypes = [c_ubyte, c_ubyte]
    _diff_check_protocol_version.restype = c_int

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 20
try:
    _diffusion_h_ = 1
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 26
try:
    DIFFUSION_SUCCESS = 0
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 27
try:
    DIFFUSION_ERROR = (-1)
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 28
try:
    DIFFUSION_INVALID_PROTOCOL_VERSION = (-2)
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 30
try:
    DIFFUSION_MD = 0
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 31
try:
    DIFFUSION_RD = 1
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 32
try:
    DIFFUSION_FD = 2
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 34
try:
    DIFFUSION_MSG_FRAGMENT_MASK = 64
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 36
try:
    DIFFUSION_MSG_TOPIC_LOAD = 20
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 37
try:
    DIFFUSION_MSG_DELTA = 21
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 38
try:
    DIFFUSION_MSG_SUBSCRIBE = 22
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 39
try:
    DIFFUSION_MSG_UNSUBSCRIBE = 23
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 40
try:
    DIFFUSION_MSG_PING_SERVER = 24
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 41
try:
    DIFFUSION_MSG_PING_CLIENT = 25
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 42
try:
    DIFFUSION_MSG_CREDENTIALS = 26
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 43
try:
    DIFFUSION_MSG_CREDENTIALS_REJECTED = 27
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 44
try:
    DIFFUSION_ABORT_NOTIFICATION = 28
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 45
try:
    DIFFUSION_CLOSE_REQUEST = 29
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 46
try:
    DIFFUSION_MSG_TOPIC_LOAD_ACK = 30
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 47
try:
    DIFFUSION_MSG_DELTA_ACK = 31
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 48
try:
    DIFFUSION_MSG_ACK = 32
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 49
try:
    DIFFUSION_MSG_FETCH = 33
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 50
try:
    DIFFUSION_MSG_FETCH_REPLY = 34
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 51
try:
    DIFFUSION_MSG_TOPIC_STATUS_NOTIFICATION = 35
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 52
try:
    DIFFUSION_MSG_COMMAND = 36
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 53
try:
    DIFFUSION_MSG_COMMAND_TOPIC_LOAD = 40
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 54
try:
    DIFFUSION_MSG_COMMAND_TOPIC_NOTIFICATION = 41
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 55
try:
    DIFFUSION_MSG_CANCEL_FRAGMENTED = 48
except:
    pass

# message encoding
try:
    DIFFUSION_NONE_ENCODING = 0
    DIFFUSION_ENCRYPTED_ENCODING = 17
    DIFFUSION_COMPRESSED_ENCODING = 18
    DIFFUSION_BASE64_ENCODING = 19
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 57
try:
    DIFFUSION_FLAG_RECONNECT = 1
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 58
try:
    DIFFUSION_FLAG_LOAD_BALANCE = 2
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 60
try:
    DIFFUSION_COMMAND_ID_SERVICE_TOPIC = '0'
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 61
try:
    DIFFUSION_COMMAND_ID_PAGED_TOPIC = '1'
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 63
try:
    DIFFUSION_PAGINATED_DATA_TYPE_STRING = 'PS'
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 64
try:
    DIFFUSION_PAGINATED_DATA_TYPE_RECORD = 'PR'
except:
    pass

# /home/comnimh/nas/Diffusion/c-api/diffusion.h: 150
def DIFF_CB_ZERO(cb):
    return (memset (pointer(cb), 0, sizeof(cb)))

# /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 2
try:
    _diffusion_int_h_ = 1
except:
    pass

_pair = struct__pair # /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 9

_fragment = struct__fragment # /home/comnimh/nas/Diffusion/c-api/diffusion_int.h: 14

# No inserted files

