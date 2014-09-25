<?

/**
 * Интерфейс, который должны реализовывать любая команда из API
 *
 * @version 1.0
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

interface IApiCommand
{
    /**
     * Установка стартовых параметров функции
     *
     * @param array $params
     * @access public
     * @return IApiCommand
     */

    public function initParams(array $params);

    /**
     * Функция которая начинает выполнение команды
     *
     * @access public
     * @return mixed
     */

    public function run();
}
