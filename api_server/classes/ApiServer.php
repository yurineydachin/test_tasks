<?
require_once(ROOTDIR.'classes/ApiExceptions.php');
require_once(ROOTDIR.'classes/ApiCommandsFactory.php');

abstract class ApiServer
{
    /**
     * Параметры окружения
     *
     * Пригодятся в дальнейшем для авторизации пользователей и прочих более сложных команд
     *
     * @var array
     * @access protected
     */

    protected $params = array();

    /**
     * Команда, которую надо вызвать
     *
     * @var IApiCommand
     * @access protected
     */

    protected $command = null;

    /**
     * Результат выполнения комманды
     *
     * @var bool
     * @access protected
     */

    protected $isError = false;

    /**
     * Функция добавляет параметр в окружение серверу
     *
     * @param string $field
     * @param mixed $value
     * @access public
     * @return IApiServer
     */

    public function init($field, $value)
    {
        $this->params[$field] = $value;

        return $this;
    }

    /**
     * Получить параметры их внешней среды
     *
     * @access protected
     * @return mixed
     */

    abstract protected function obtainParams();

    /**
     * Выплевывает результат работы
     *
     * @param mixed $result
     * @abstract
     * @access protected
     * @return string
     */

    abstract protected function spitResult($result);

    /**
     * Запуск команды
     *
     * @access public
     * @return void
     */

    public function run()
    {
        $result = $this->runCommand();
        $response = $this->spitResult($result);

        echo $response;
    }

    /**
     * Запускает команды на выполнение
     *
     * @access protected
     * @return mixed
     */

    protected function runCommand()
    {
        try
        {
            $requestParams = (array) $this->obtainParams();
            $this->command = ApiCommandsFactory::createCommand($requestParams['command']);

            // Запускаем команду
            return $this->command->initParams($requestParams['params'])->run();
        }
        catch(Exception $e)
        {
            $this->isError = true;
            return ApiCommandsFactory::createCommand('error')->initParams(array('exception' => $e))->run();
        }
    }
}

/**
 * ApiServerException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiServerException extends ApiException {}
