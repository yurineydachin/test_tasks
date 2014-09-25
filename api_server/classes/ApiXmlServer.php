<?

require_once(ROOTDIR.'classes/ApiServer.php');

/**
 * ApiJsonServer
 *
 * @uses ApiServer
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiXmlServer extends ApiServer
{
    /**
     * Получить параметры их внешней среды
     *
     * @access protected
     * @return mixed
     */

    protected function obtainParams()
    {
        $commandName = null;
        $params = null;

        if (isset($_REQUEST['command'] && isset($_REQUEST['params'])))
        {
            $commandName = $_REQUEST['command'];
            $params = $_REQUEST['params'];
        }
        elseif (isset($_REQUEST['command'] && isset($_REQUEST['xml']))
        {
            $params = xmlrpc_decode_request($_REQUEST['xml'], $commandName);
            $commandName = $_REQUEST['command'];
        }

        // Есть вероятность, что нам пришел кривой запрос
        if (!$commandName || $params === null)
        {
            throw new Api3XmlServerInvalidException();
        }

        return array(
            'command' => $commandName,
            'params' => (array) $params,
        );
    }

    /**
     * Выплевывает результат работы
     *
     * @param mixed $result
     * @access protected
     * @return string
     */

    protected function spitResult($result)
    {
        $res = array();
        if(!$this->isError)
        {
            $res['response'] = $result;
        }
        else
        {
            $res['error'] = $result;
        }
        header('Content-type: text/xml');
        return xmlrpc_encode($res);
    }
}

/**
 * ApiXmpServerInvalidException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiXmlServerInvalidException extends ApiException
{
    public function __construct(Exception $e = null)
    {
        parent::__construct('Parse error.', -32700, $e);
    }
}
