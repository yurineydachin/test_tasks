<?php

/**
 * Логировать событие в файле logs/log.log
 * @param mixed $value
 */
function debugLog($value, $title = '', $id = null)
{
    if (1) {
        static $sessionRand = null;
        if ($sessionRand === null)
        {
            if (isset($_REQUEST['params']) && is_string($_REQUEST['params'])) {
                $sessionRand = substr(md5($_REQUEST['params'] . rand(10000,99999)), 0, 4);
            } else {
                $sessionRand = rand(10,99);
            }
        }
        $title = $title ? $title . ' ' : '';
        file_put_contents( '/www/logs/visualrian_services/engine/photo.log', date("[Y-m-d H:i:s ") . ($id ? $id . ' ' : '') . $sessionRand . ']  ' . $title . debugItem($value)."\n", FILE_APPEND );
    }
}

/**
 * Преобразовать значение для вывода отладочной информации
 * @param mixed $value
 * @param int   $level
 */
function debugItem($value, $level = 0)
{
    if ( is_array($value) ) {
        $value = "Array(".count($value).")" . (count($value) ? ":\n" . array2string($value, $level + 1) : '');
    }
    elseif ( is_object($value) ) {
        $value = get_class($value)
            . ( method_exists($value, '__toString') ? ":\n" . $value->__toString() : '' );
    }
    elseif ( gettype($value) != 'string' ) {
        $value = (string) $value;
    }
    return $value;
}

/**
 * Преобразование массива в строку
 * Because of 'PHP Fatal error:  print_r(): Cannot use output buffering in output buffering display handlers'
 * @param array $arr 
 */
function array2string(array $arr, $level = 0)
{
    $_ = array();
    foreach ( $arr as $key => $value) {
        $_[] = str_repeat(' ', $level) . "{$key} => " . debugItem($value, $level + 1);
    }
    return implode(PHP_EOL, $_);
}

function debug($msg)
{
    Bootstrap::initLogger()->debug(debugItem($msg));
}
