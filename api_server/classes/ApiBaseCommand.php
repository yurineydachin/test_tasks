<?
require_once(ROOTDIR.'classes/ApiExceptions.php');
require_once(ROOTDIR.'classes/IApiCommand.php');

/**
 * Абстрактный базовый класс команды API
 *
 * @uses IApiCommand
 * @abstract
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

abstract class ApiBaseCommand implements IApiCommand
{
    /**
     * Параметры команды
     *
     * @var array
     * @access protected
     */

    protected $params = array();

    /**
     * Результат работы команды
     *
     * @var array
     * @access protected
     */

    protected $result = array();

    /**
     * Параметры команды
     *
     * @var string
     * @access protected
     */

    protected $commandName = null;

    /**
     * проверить входные параметры на заполненность
     *
     * @access protected
     * @return array
     */

    protected function checkInput()
    {
        $signature = $this->getSignature();

        foreach($this->params as $name => $value)
        {
            if(!isset($signature[$name]))
            {
                throw new ApiCommandParamNotFoundException($name, $this);
            }
            switch($signature[$name]['type'])
            {
                case 'string' : $this->params[$name] = (string) $this->params[$name]; break;
                case 'int' : $this->params[$name] = (int) $this->params[$name]; break;
                case 'bool' : $this->params[$name] = (bool) $this->params[$name]; break;
                case 'object' : 
                    if(!($value instanceof $signature[$name]['class']))
                    {
                        throw new ApiCommandParamNotValidClassException(get_class($value), $name, $this);
                    }
                    break;
                default : 
                    throw new ApiCommandParamTypeUnknownException($type, $name, $this);
            }
        }
        foreach($signature as $name => $info)
        {
            if($info['required'] && !isset($this->params[$name]))
            {
                throw new ApiCommandParamRequiredException($name, $this);
            }
            if(isset($info['default']) && !isset($this->params[$name]))
            {
                $this->params[$name] = $info['default'];
            }
        }
    }

    /**
     * Установка стартовых параметров
     *
     * @param array $params
     * @access public
     * @return IApiCommand
     */

    public function initParams(array $params)
    {
        foreach ($params as $name => $value)
        {
            $this->setParam($name, $value);
        }
        return $this;
    }

    /**
     * Изменить значение параметра
     *
     * @param mixed $params
     * @access public
     * @return IApiCommand
     */

    protected function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Получить значение параметра
     *
     * @param mixed $name
     * @access public
     * @return mixed
     */

    protected function getParam($name)
    {
        return $this->params[$name];
    }

    /**
     * Собственно тело команды
     *
     * @abstract
     * @access protected
     * @return mixed
     */

    abstract protected function perform();

    /**
     * Выполнение команды
     *
     * @final
     * @access public
     * @return mixed
     */

    final public function run()
    {
        // Проверяем параметры на заполненность
        $this->checkInput();

        // Выполняем команду
        $this->perform();

        return $this->result;
    }
}

/**
 * ApiCommandParamNotFoundException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandParamNotFoundException extends ApiException
{
    function __construct($name, $command)
    {
        parent::__construct(sprintf('Парамерт %s не является входным для комманы %s', $name, get_class($command)), 1);
    }
}

/**
 * ApiCommandParamNotValidException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandParamNotValidClassException extends ApiException
{
    function __construct($type, $name, $command)
    {
        parent::__construct(sprintf('Несоответствует класс %s входного параметра %s команды %s', $type, $name, get_class($command)), 2);
    }
}

/**
 * ApiCommandParamNotValidException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandParamTypeUnknownExceptionn extends ApiException
{
    function __construct($type, $name, $command)
    {
        parent::__construct(sprintf('Неизвестный тип %s входного параметра %s команды %s', $type, $name, get_class($command)), 3);
    }
}

/**
 * ApiCommandParamNotValidException
 *
 * @uses ApiException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandParamRequiredException extends ApiException
{
    function __construct($name, $command)
    {
        parent::__construct(sprintf('Парамерт %s является обязательным команды %s', $name, get_class($command)), 4);
    }
}
