<?php

require_once APPLICATION_PATH . '/common/SaveTask.php';
require_once APPLICATION_PATH . '/common/ResizeTask.php';
require_once APPLICATION_PATH . '/common/ScaleTask.php';
require_once APPLICATION_PATH . '/common/CropTask.php';
require_once APPLICATION_PATH . '/common/RotateTask.php';
require_once APPLICATION_PATH . '/common/BlurTask.php';
require_once APPLICATION_PATH . '/common/FilterTask.php';
require_once APPLICATION_PATH . '/common/ExtentTask.php';
require_once APPLICATION_PATH . '/common/WaterMarkTask.php';
require_once APPLICATION_PATH . '/common/AnnotateTask.php';
require_once APPLICATION_PATH . '/common/DebugTask.php';
require_once APPLICATION_PATH . '/common/MailTask.php';
require_once APPLICATION_PATH . '/common/AmqpTask.php';
require_once APPLICATION_PATH . '/common/DefineTask.php';

class TaskFactory
{
    private static $tasks = array(
        'save'      => 'SaveTask',
        'resize'    => 'ResizeTask',
        'scale'     => 'ScaleTask',
        'crop'      => 'CropTask',
        'rotate'    => 'RotateTask',
        'blur'      => 'BlurTask',
        'filter'    => 'FilterTask',
        'extent'    => 'ExtentTask',
        'watermark' => 'WaterMarkTask',
        'annotate'  => 'AnnotateTask',
        'debug'     => 'DebugTask',
        'mail'      => 'MailTask',
        'rabbit'    => 'AmqpTask',
        'amqp'      => 'AmqpTask',
        'define'    => 'DefineTask',
    );

    public static function prepare($params, $plugin)
    {
        if (! isset($params['action'])) {
            throw new Exception('No action for task');
        }
        if (isset(self::$tasks[$params['action']]))
        {
            $class = self::$tasks[$params['action']];
            return new $class($params, $plugin);
        }
        else {
            throw new Exception('Unsupported task action: ' . $params['action']);
        }
    }
}
