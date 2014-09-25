<?

/**
 * @file index.php
 *
 * Доступ через API
 */

header('Content-Type: text/html; charset=utf-8');
define('ROOTDIR', $_SERVER['DOCUMENT_ROOT'].'/');

require_once(ROOTDIR.'settings.php');

try
{
    //echo json_encode(array('command' => 'test', 'params' => array('message' => 'hi')))."<br/>\n";
    $serverType = $_REQUEST['format'] ? $_REQUEST['format'] : 'json';

    require_once(ROOTDIR.'classes/ApiServerFactory.php');

    $server  = ApiServerFactory::prepareServer($serverType);
    $server->run();
}
catch (Exception $e)
{
    header("HTTP/1.1 503 Service Temporarily Unavailable");

    echo "Critical API error \n";
    echo $e;
}
