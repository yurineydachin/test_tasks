<?php

abstract class Task
{
    protected $plugin;
    private $data   = array();

    public function __construct($params, $plugin)
    {
        $this->plugin = $plugin;
        if (isset($params['params'])) {
            $this->data = $params['params'];
        } else {
            $this->data = array();
        }
        if (isset($params['subTasks'])) {
            $this->data['subTasks'] = $params['subTasks'];
        } else {
            $this->data['subTasks'] = array();
        }
        if (isset($params['image'])) {
            $this->data['image'] = $params['image'];
        }
        if (isset($params['sourceFile'])) {
            $this->data['sourceFile'] = $params['sourceFile'];
        }
        if (isset($params['destinationFile'])) {
            $this->data['destinationFile'] = $params['destinationFile'];
        }
        if (isset($params['quality'])) {
            $this->data['quality'] = $params['quality'];
        }
    }

    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    protected function getData()
    {
        return $this->data;
    }

    protected function checkParams()
    {
        if (! $this->sourceFile) {
            throw new Exception('No sourceFile');
        }
        if (! $this->image instanceof Imagick && ! $this->sourceFile) {
            throw new Exception('No source');
        }
        if ($this->sourceFile && ! file_exists($this->getStorePath($this->sourceFile))) {
            throw new Exception('File does not exist: ' . $this->getStorePath($this->sourceFile));
        }
        if (! $this->quality || $this->quality <=0 || $this->quality >= 100) {
            $this->quality = 100;
        }
    }

    abstract protected function run();

    final public function peform()
    {
        $m = microtime(true);
        $this->checkParams();
        $this->run();
        $this->saveImage();
        $this->plugin->logExecutionTime(get_class($this), $m);

        $subTasks = $this->subTasks;
        $subTasksCount = count($subTasks);
        $i = 0;
        foreach ($subTasks as $taskInfo)
        {
            $this->peformTaskInfo($taskInfo, ++$i !== $subTasksCount);
        }

        return $this->cleanImage();
    }

    private function peformTaskInfo(array $taskInfo, $clone = true)
    {
        try
        {
            if (! isset($taskInfo['sourceFile']))
            {
                $taskInfo['sourceFile'] = $this->destinationFile ? $this->destinationFile : $this->sourceFile;
                $taskInfo['image'] = $this->image ? ($clone ? clone $this->image : $this->image) : null;
            }
            TaskFactory::prepare($taskInfo, $this->plugin)->peform();
        }
        catch (Exception $e)
        {
            $this->plugin->errorMail($e);
        }
    }

    protected function saveImage()
    {
        if ($this->destinationFile)
        {
            $destination = $this->getStorePath($this->destinationFile);
            if (! is_dir(dirname($destination))) {
                $this->plugin->log('mkdir ' . dirname($destination));
                mkdir(dirname($destination), 0777, true);
            }

            if ($this->image instanceof Imagick || $this->quality != 100)
            {
                $image = $this->getImage();
                $image->setImageCompressionQuality($this->quality);
                $image->writeImage($destination);
            }
            else
            {
                copy($this->getStorePath($this->sourceFile), $destination);
            }
            //chmod($destination, 0777);
        }
        return $this;
    }

    protected function getImage()
    {
        $image = $this->image;
        if (! $image instanceof Imagick && $this->sourceFile)
        {
            $m = microtime(true);
            $image = $this->image = new Imagick($this->getStorePath($this->sourceFile));
            $image->setImageCompressionQuality($this->quality);
            $this->plugin->logExecutionTime(__METHOD__, $m);
        }
        return $image;
    }

    protected function getImageType()
    {
        switch ($this->getImage()->getImageMimeType()) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/gif':
                return 'gif';
            case 'image/png':
                return 'png';
            default:
                throw new Exception('Unsupported MimeType: ' . $this->getImage()->getImageMimeType());
        }
    }

    protected function readExif()
    {
        if ($this->sourceFile)
        {
            $filePath = $this->getStorePath($this->sourceFile);
            $this->plugin->log('read exif from sourceFile: ' . $filePath);
            $exif = exif_read_data($filePath);
        }
        else
        {
            $filePath = TMP_PATH . '/' . uniqid('define_') . '.' . $this->getImageType();
            $this->getImage()->writeImage($filePath);
            $this->plugin->log('read exif from tmp Imagick writen file: ' . $filePath);
            $exif = exif_read_data($this->getStorePath($filePath));
            unlink($filePath);
        }
        return $exif;
    }

    protected function cleanImage()
    {
        if ($this->image instanceof Imagick)
        {
            $this->image->clear();
            $this->image->destroy();
            $this->image = null;
        }
        return $this;
    }

    public function getStorePath($path)
    {
        if (substr($path, 0, 1) === '/') {
            return $path;
        } else {
            return $this->plugin->getConfigZ()->store->path . $path;
        }
    }

    public static function calcPositionX($x, $externalWidth, $internalWidth)
    {
        $positionsHorz = array(
            'center',
            'left',
            'right',
        );
        if (in_array($x, $positionsHorz, true))
        {
            switch ($x)
            {
                case 'left' :
                    $x = 0;
                    break;
                case 'right' :
                    $x = $externalWidth - $internalWidth;
                    break;
                case 'center' :
                    $x = floor(($externalWidth - $internalWidth) / 2);
                    break;
                default:
                    throw new Exception('Unknow position: ' . $x);
            }
        }
        else
        {
            $x = (int) $x;
            if ($x < 0) {
                $x = $externalWidth - $internalWidth + $x;
            }
        }
        return $x;
    }

    public static function calcPositionY($y, $externalHeight, $internalHeight)
    {
        $positionsVert = array(
            'center',
            'top',
            'bottom',
        );
        if (in_array($y, $positionsVert, true))
        {
            switch ($y)
            {
                case 'top' :
                    $y = 0;
                    break;
                case 'bottom' :
                    $y = $externalHeight - $internalHeight;
                    break;
                case 'center' :
                    $y = floor(($externalHeight - $internalHeight) / 2);
                    break;
                default:
                    throw new Exception('Unknow position: ' . $y);
            }
        }
        else
        {
            $y = (int) $y;
            if ($y < 0) {
                $y = $externalHeight - $internalHeight + $y;
            }
        }
        return $y;
    }
}
