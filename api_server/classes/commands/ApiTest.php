<?

require_once(ROOTDIR.'classes/ApiBaseCommand.php');

/**
 * ApiTest
 *
 * @uses ApiBaseCommand
 * @version 1.0
 * @author Yuri Neudachin <yurineydachin@mail.ru>
 */

class ApiTest extends ApiBaseCommand
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
            'message' => array(
                'title'     => 'текст сообщения',
                'type'      => 'string',
                'default'   => 'Пустое сообщение',
                //'required'  => true,
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
        $this->result['result'] = sprintf('Ваще сообщение "%s"', $this->params['message']);
    }
}
