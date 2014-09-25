<?php

require_once APPLICATION_PATH . '/common/Task.php';

class ExtentTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        $image = $this->getImage();
        if ($this->width == 'square' && $this->height == 'square')
        {
            $max = max($image->getImageWidth(), $image->getImageHeight());
            $this->width  = $max;
            $this->height = $max;
        }

        if (! $this->background) {
            $this->background = 'white';
        }
        if (! $this->width || $this->width < 0) {
            throw new Exception('Uncorrect width params');
        }
        if (! $this->height || $this->height < 0) {
            throw new Exception('Uncorrect height params');
        }
    }

    protected function run()
    {
        $image = $this->getImage();
        $this->x = Task::calcPositionX($this->x, $this->width,  $image->getImageWidth());
        $this->y = Task::calcPositionY($this->y, $this->height, $image->getImageHeight());

        $newImage = new Imagick();
        $newImage->newImage($this->width, $this->height, new ImagickPixel($this->background));
        $newImage->compositeImage($image, Imagick::COMPOSITE_DEFAULT, $this->x, $this->y);
        $this->cleanImage();
        $this->image = $newImage;

        //$image->extentImage($this->width, $this->height, $this->x, $this->y);
    }
}
