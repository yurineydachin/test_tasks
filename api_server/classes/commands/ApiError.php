<?

require_once(ROOTDIR.'classes/ApiBaseCommand.php');

/**
 * ApiTest
 *
 * @uses ApiBaseCommand
 * @version 1.0
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiError extends ApiBaseCommand
{
    /**
     * Сигнатура команды
     *
     * @access protected
     * @return mixed
     */

    protected function getSignature()
    {
        return array(
            'exception' => array(
                'title' => 'Исключение пойманное при работе Апи',
                'type' => 'object',
                'class' => 'ApiException',
                'required' => true,
            ),
        );
    }

    /**
     * Тело команды
     *
     * @access protected
     * @return mixed
     */

    protected function perform()
    {
        $this->result['description']   = sprintf('При работе Апи произошла ошибка: "%s"', $this->params['exception']->getMessage());
        //$this->result['exception'] = get_class($this->params['exception']);
        $this->result['code']      = $this->params['exception']->getCode();
    }
}
