<?php

$GLOBALS['api_commands'] = array (
    'test'              => 'ApiTest',
    'error'             => 'ApiError',
);

class AssertException extends Exception {}

function assert_handler($file, $line, $code)
{
        throw new AssertException("Assert failed on $file:$line ($code)");
}

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
assert_options(ASSERT_CALLBACK, 'assert_handler');

?>
