<?

require_once(ROOTDIR.'classes/ApiServer.php');

/**
 * ApiJsonServer
 *
 * @uses ApiServer
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiJsonServer extends ApiServer
{
    /**
     * Получить параметры их внешней среды
     *
     * @access protected
     * @return mixed
     */

    protected function obtainParams()
    {
        //return $_GET;
        $json = null;
        if (isset($_REQUEST['command']) && isset($_REQUEST['params']))
        {
            $commandName = $_REQUEST['command'];
            $params = $_REQUEST['params'];

            $json = array(
                'command' => $commandName,
                'params' => (array) $params,
            );
        }
        elseif (isset($_REQUEST['command']) && isset($_REQUEST['json_params']))
        {
            $commandName = $_REQUEST['command'];
            $params = json_decode($_REQUEST['json_params'], true);

            $json = array(
                'command' => $commandName,
                'params' => (array) $params,
            );
        }
        elseif (isset($_REQUEST['json']))
        {
            $json = json_decode($_REQUEST['json'], true);
        }

        // Есть вероятность, что нам пришел кривой запрос
        if ($json === null)
        {
            throw new ApiJsonServerInvalidJsonException();
        }

        // Проверяем на соответствие JSON-RPC спецификации
        if (!isset($json['command']) || !isset($json['params']) || count($json) != 2)
        {
            throw new ApiJsonServerInvalidJsonRpcException();
        }

        return $json;
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

        @header('Content-type: application/x-javascript');
        return print_r($res, true);
        return json_encode($res);
    }
}

/**
 * ApiJsonServerInvalidJsonException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiJsonServerInvalidJsonException extends ApiException
{
    public function __construct(Exception $e = null)
    {
        parent::__construct('Parse error.', -32700, $e);
    }
}

/**
 * ApiJsonServerInvalidJsonRpcException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiJsonServerInvalidJsonRpcException extends ApiException
{
    public function __construct(Exception $e = null)
    {
        parent::__construct('Invalid Request.', -32600, $e);
    }
}
