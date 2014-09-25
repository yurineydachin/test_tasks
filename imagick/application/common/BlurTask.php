<?php

require_once APPLICATION_PATH . '/common/Task.php';

class BlurTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        $this->radius = (int) $this->radius;
        if (! $this->radius || $this->radius < 0) {
            $this->radius = 0;
        }

        $this->sigma = (int) $this->sigma;
        if (! $this->sigma || $this->sigma < 0) {
            $this->sigma = 0;
        }

        if (! $this->sigma && ! $this->radius) {
            throw new Exception('Uncorrect blur params');
        }
    }

    protected function run()
    {
        $image = $this->getImage();
        $image->blurImage($this->radius, $this->sigma);
    }
}
