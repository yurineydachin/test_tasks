<?php

ini_set('sendmail_path', '/usr/sbin/sendmail -i -t');
ini_set('date.timezone', 'Europe/Moscow');
mb_internal_encoding('UTF-8');

define('ROOT_PATH',         realpath(__DIR__));
define('APPLICATION_PATH',  ROOT_PATH . '/application');
define('HTDOCS_PATH',       realpath(ROOT_PATH . '/../../htdocs'));
//define('LIBRARY_PATH',      ROOT_PATH . '/library');
define('TMP_PATH',          '/tmp/ram');

require_once APPLICATION_PATH . '/functions.php';

/* Run Zend-Autoloader */
require_once 'Zend/Loader/Autoloader.php';
spl_autoload_register(array('Zend_Loader_Autoloader', 'autoload'), false, true);

require_once APPLICATION_PATH . '/Bootstrap.php';
