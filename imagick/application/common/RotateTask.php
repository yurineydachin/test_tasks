<?php

require_once APPLICATION_PATH . '/common/Task.php';

class RotateTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        $this->degrees = (float) $this->degrees;
    }

    protected function run()
    {
        $image = $this->getImage();
        $image->rotateImage(new ImagickPixel('none'), $this->degrees);
    }
}
