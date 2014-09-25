<?
require_once(ROOTDIR.'classes/IApiCommand.php');

/**
 * ApiServerFactory
 *
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiServerFactory
{
    /**
     * Функция создания сервера в зависимости от его типа
     *
     * @param mixed $serverType
     * @param IApiCommand $command
     * @static
     * @access public
     * @return IApiServer
     */

    static public function prepareServer($serverType)
    {
        switch ($serverType)
        {
            case 'json':
                require_once(ROOTDIR.'classes/ApiJsonServer.php');
                return new ApiJsonServer();
            case 'xml':
                require_once(ROOTDIR.'classes/ApiXmlServer.php');
                return new ApiXmlServer();
        }

        assert(false);
    }
}
