<?
require_once(ROOTDIR.'classes/ApiExceptions.php');
require_once(ROOTDIR.'classes/IApiCommand.php');

/**
 * Класс который занимается разборкой между именем команды
 * и классом который должен быть подгружен
 *
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandsFactory
{
    /**
     * По имени команды возвращает объект IApiCommand
     *
     * @param string $commandName
     * @access public
     * @return IApiCommand
     */

    public static function createCommand($commandName)
    {
        $className = self::getClassName($commandName);
        self::loadClass($className);
        return new $className();
    }

    /**
     * Функция по имени команды возвращает имя класса отвечающего за ее работу
     *
     * @param string $commandName
     * @static
     * @access protected
     * @return string
     */

    protected static function getClassName($commandName)
    {
        if(!isset($GLOBALS['api_commands'][$commandName]))
        {
            throw new ApiCommandUnknownException($commandName);
        }

        return $GLOBALS['api_commands'][$commandName];
    }

    /**
     * Команда грузит файл с командой
     *
     * @param string $className
     * @static
     * @access protected
     * @return void
     */

    protected static function loadClass($className)
    {
        // Значит ранее уже грузили эту команду
        if (class_exists($className))
            return;

        $filePath = ROOTDIR.'classes/commands/'.$className.'.php';
        // Проверяем команду на существование
        if (!file_exists($filePath))
        {
            throw new ApiCommandWithoutClassException($className);
        }

        include($filePath);

        // Класс команды обязательно должен существовать
        assert(class_exists($className));
    }
}

/**
 * ApiCommandUnknownException
 *
 * @uses ApiCommandsFactoryException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandUnknownException extends ApiException
{
    public function __construct($commandName, $code = 0)
    {
        parent::__construct(sprintf('Неизвестная комманда %s', $commandName), $code);
    }
}

/**
 * ApiCommandsFactoryNoClassException
 *
 * @uses ApiCommandsFactoryException
 * @version 0.1
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiCommandWithoutClassException extends ApiException
{
    public function __construct($className, $code = 0)
    {
        parent::__construct(sprintf('Не найдена команда с классом %s', $className), $code);
    }
}
