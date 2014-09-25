<?php

class Bootstrap
{
    static private $config  = null;
    static private $logger  = null;
    static private $db      = null;

    static public function initConfig($env)
    {
        if (self::$config === null)
        {
            if (!defined('APPLICATION_ENV')) {
                define('APPLICATION_ENV', $env);
            }

            self::$config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $env);
            Zend_Registry::set('Zend_Config', self::$config);
        }

        return self::$config;
    }

    static public function initLogger()
    {
        if (! self::$logger && self::$config)
        {
            $logger = new Zend_Log();
            $formatter = new Zend_Log_Formatter_Simple('[%timestamp% - '.rand(100,999).'] : %message%'.PHP_EOL);
            $errorWriter = new Zend_Log_Writer_Stream(self::$config->enginelogs->path . '/error.log', 'a');
            $errorWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::ERR));
            $errorWriter->setFormatter($formatter);
            $logger->addWriter($errorWriter);
            
            if (DEBUG)
            {
                $infoWriter = new Zend_Log_Writer_Stream(self::$config->enginelogs->path . '/info.log', 'a');
                $infoWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::INFO));
                $infoWriter->setFormatter($formatter);
                $logger->addWriter($infoWriter);
                
                $debugWriter = new Zend_Log_Writer_Stream(self::$config->enginelogs->path . '/photo.log', 'a');
                $debugWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::DEBUG));
                $debugWriter->setFormatter($formatter);
                $logger->addWriter($debugWriter);
            }

            Zend_Registry::set('Zend_Log', $logger);
            self::$logger = $logger;
        }
        return self::$logger;
    }

    static public function initDb()
    {
        if (self::$db === null)
        {
            self::$db = Zend_Db::factory(self::$config->database->adapter, self::$config->database->params);
            Zend_Registry::set("Zend_Db", self::$db);
            Zend_Db_Table::setDefaultAdapter(self::$db);

            self::$db->query("ALTER SESSION SET NLS_DATE_FORMAT = 'yyyy-mm-dd hh24:mi:ss'");
        }
        return self::$db;
    }
}
