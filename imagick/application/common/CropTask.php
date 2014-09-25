<?php

require_once APPLICATION_PATH . '/common/Task.php';

class CropTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        $image = $this->getImage();
        if ($this->width == 'square' && $this->height == 'square')
        {
            $min = min($image->getImageWidth(), $image->getImageHeight());
            $this->width  = $min;
            $this->height = $min;
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
        $this->x = Task::calcPositionX($this->x, $image->getImageWidth(), $this->width);
        $this->y = Task::calcPositionY($this->y, $image->getImageHeight(), $this->height);
        $image->cropImage($this->width, $this->height, $this->x, $this->y);
        $image->setImagePage(0, 0, 0, 0);
    }
}
