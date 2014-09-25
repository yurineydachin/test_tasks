<?php

require realpath(__DIR__) . '/index.php';
require_once APPLICATION_PATH . '/common/TaskFactory.php';

debugLog($_REQUEST, 'start plugin');

/**
 * Plugin for work with visualrian import from oldVR to orphea
 *
 * @author y.neudachin 
 */

class imagick_tasks extends plugin {

    protected $version = "1.0";
    private $cfg = null;
    private $_config = null;
    private $_time = null;
    private $logId = null;

    const DEFAULT_PROJECT = 'yorick';

    public function __construct($version, $config)
    {
        $this->_time = microtime(true);
        $this->checkVersion($version);
        $config = new Config($config);
        $cfg    = $config->GetObjectConfig();
        $this->cfg = $cfg;
    }

    public function getConfigZ()
    {
        return $this->_config;
    }

    public function getExecutionTime($time = null)
    {
        return microtime(true) - ($time ? $time : $this->_time);
    }

    public function logExecutionTime($title, $time = null)
    {
        $this->log(sprintf("%01.3f", $this->getExecutionTime($time)), $title . ($time ? '_time' : '_total_time'));
    }

    public function setLogId($id)
    {
        $this->logId = $id;
        $this->log(__METHOD__);
    }

    public function log($value, $title = null)
    {
        debugLog($value, $title, $this->logId);
    }

    protected function setProject($params)
    {
        try
        {
            $this->_config = Bootstrap::initConfig($params['project']);
        }
        catch (Zend_Config_Exception $e)
        {
            $this->_config = Bootstrap::initConfig(self::DEFAULT_PROJECT);
            $this->errorMail($e);
        }
    }

    public function peform(array $params)
    {
        $this->setProject($params);

        $required_fields = array(
            'task'   => true,
        );
        helper::checkParams($params, $required_fields);

        try
        {
            $task = TaskFactory::prepare($params['task'], $this)->peform();
        }
        catch (Exception $e)
        {
            $this->errorMail($e);
        }
        $this->logExecutionTime(__METHOD__);
    }

    public function errorMail($e)
    {
        $this->log($e);
        $mail = new Zend_Mail($this->_config->mail->charset);
        $mail->addTo(     $this->_config->mail->to);
        $mail->setFrom(   $this->_config->mail->from);
        $subjectError = is_object($e) ? get_class($e) : $e;
        $mail->setSubject(sprintf($this->_config->mail->error->subject . $subjectError, APPLICATION_ENV));

        $mail->setBodyText(
            date('Y-m-d H:i:s')
            . "\n\n" . debugItem($e)
            . "\n\nREQUEST: " . debugItem($_REQUEST)
            . "\n\nSERVER: "  . debugItem($_SERVER)
        );
        $mail->send();
    }

    /*
     * Посылает тестовый запрос в очередь
     */
    public function test($params)
    {
        $this->log('start', __METHOD__);
        $this->setProject($params);

        $task = array(
            'action'     => 'crop',
            'sourceFile' => 'imagick_test1.jpg',
            'destinationFile' => 'imagick_test1_crop.jpg',
            'params' => array(
                'width'   => 1300,
                'height'  => 1400,
                'x'       => 1500,
                'y'       => 1600,
            ),
            'subTasks' => array(
                array(
                    'action' => 'scale',
                    'destinationFile' => 'imagick_test1_crop_scale.jpg',
                    'params' => array(
                        'scale'   => 600,
                    ),
                    'subTasks' => array(
                        array(
                            'action' => 'extent',
                            'destinationFile' => 'imagick_test1_crop_scale_extent.jpg',
                            'params' => array(
                                'width'   => 1000,
                                'height'  => 700,
                                'x'       => 'center',
                                'y'       => 'center',
                                'background' => 'blue',
                            ),
                            'subTasks' => array(
                                array(
                                    'action' => 'rotate',
                                    'destinationFile' => 'imagick_test1_crop_scale_extent_rotate.jpg',
                                    'params' => array(
                                        'degrees' => 90,
                                    ),
                                    'subTasks' => array(
                                        array(
                                            'action' => 'filter',
                                            'destinationFile' => 'imagick_test1_crop_scale_extent_rotate_filter.jpg',
                                            'params' => array(
                                                'radius' => 2,
                                            ),
                                        ),
                                        array(
                                            'action' => 'blur',
                                            'destinationFile' => 'imagick_test1_crop_scale_extent_rotate_blur.jpg',
                                            'params' => array(
                                                'radius' => 2,
                                                'sigma'  => 20,
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'action' => 'watermark',
                            'params' => array(
                                'type'    => 'position', // mosaic, position
                                'x'       => 'right',
                                'y'       => 'bottom',
                                'watermark' => 'ria-watermark.png',
                            ),
                            'subTasks' => array(
                                array(
                                    'action'          => 'save',
                                    'destinationFile' => 'imagick_test1_crop_scale_watermark.jpg',
                                ),
                            ),
                        ),
                        array(
                            'action' => 'watermark',
                            'destinationFile' => 'imagick_test1_crop_scale_watermark2.jpg',
                            'params' => array(
                                'type'    => 'position', // mosaic, position
                                'x'       => -20,
                                'y'       => -20,
                                'watermark' => 'ria-watermark.png',
                            ),
                        ),
                        array(
                            'action' => 'annotate',
                            'destinationFile' => 'imagick_test1_crop_scale_annotate.jpg',
                            'params' => array(
                                'x'        => 'right',
                                'y'        => -50,
                                'text'     => 'Медведев и Ко',
                                'fontSize' => 20,
                                'fontColor' => 'blue',
                                'degrees'  => 45,
                            ),
                        ),
                        array(
                            'action' => 'annotate',
                            'destinationFile' => 'imagick_test1_crop_scale_annotateMosaic.jpg',
                            'params' => array(
                                'type'     => 'mosaic',
                                'text'     => '   Медведев и Ко   ',
                                'fontSize' => 20,
                                'fontColor' => '#00FF00',
                                'degrees'  => -45,
                                'composite' => Imagick::COMPOSITE_MODULATE,
                            ),
                            'subTasks' => array(
                                array(
                                    'action' => 'mail',
                                    'params' => array(
                                        'to' => 'yurineudachin@mail.ru',
                                        'subject'  => 'Imagick test result for Yuri',
                                        'filename' => 'imagick_test1_crop_scale_annotateMosaic.jpg',
                                    ),
                                ),
                                array(
                                    'action' => 'rabbit',
                                    'params' => array(
                                        'amqp' => array ( 
                                            "host"      => "host",
                                            "vhost"     => "vhost",
                                            "login"     => "bus",
                                            "password"  => "bus",
                                            "exchange"  => "bus-exchange-devel",
                                            "queue"     => "bus-queue-devel",
                                            "routingKey"=> "bus.key.devel",
                                        ),
                                        'message' => array(
                                            array(
                                                'version' => '1.0',
                                                'action'  => 'imagick_tasks', // имя workflow для Bus
                                                'project' => trim($params['project']),
                                                'task'    => array(
                                                    'action'          => 'crop',
                                                    'sourceFile'      => 'imagick_test1_crop_scale_annotateMosaic.jpg',
                                                    'destinationFile' => 'imagick_test1_crop_scale_annotateMosaic_crop.jpg',
                                                    'params' => array(
                                                        'width'   => 300,
                                                        'height'  => 400,
                                                        'x'       => 50,
                                                        'y'       => 50,
                                                    ),
                                                    'quality' => 80,
                                                ),
                                            ),
                                        )
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'action' => 'resize',
                    'destinationFile' => 'imagick_test1_crop_resizeFit.jpg',
                    'params' => array(
                        'x'   => 800,
                        'y'   => 800,
                        'bestfit' => true,
                    ),
                ),
                array(
                    'action' => 'resize',
                    'destinationFile' => 'imagick_test1_crop_resizeSinc.jpg',
                    'params' => array(
                        'x'   => 800,
                        'y'   => 800,
                        'filter' => Imagick::FILTER_SINC,
                    ),
                ),
                array(
                    'action' => 'resize',
                    'destinationFile' => 'imagick_test1_crop_resizeBlur8.jpg',
                    'params' => array(
                        'x'   => 800,
                        'y'   => 800,
                        'blur' => 8,
                    ),
                ),
                array(
                    'action' => 'resize',
                    'destinationFile' => 'imagick_test1_crop_resizeBlur08.jpg',
                    'params' => array(
                        'x'   => 800,
                        'y'   => 800,
                        'blur' => 0.8,
                        'quality' => 80,
                    ),
                ),
            ),
        );
        $messageBus = array(
            array(
                'version' => '1.0',
                'action'  => 'imagick_tasks', // имя workflow для Bus
                'project' => trim($params['project']),
                'task'    => $task,
            ),
        );
        $messageService = array(
            array(
                'version' => '1.0',
                'plugin'  => 'imagick_tasks', // имя сервиса
                'action'  => 'peform',        // имя метода сервиса
                'project' => trim($params['project']),
                'task'   => $task,
            ),
        );

/*
        $amqp = new amqp();
        $amqp->publish($messageBus);
        $this->log('send task to bus');
*/

/*
        $url = 'http://localhost:8205/index.php';
        $this->log('send http query to service to ' . $url);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('params' => json_encode($messageService)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if ($err = curl_error($ch)) {
            $this->errorMail(__METHOD__ . ' error: ' . $err);
        }
*/

        response::sendJSON($task);
    }

    /*
     * phpinfo
     */
    public function info($params)
    {
        phpinfo();
    }
}
