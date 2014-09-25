<?php

require_once APPLICATION_PATH . '/common/Task.php';

class ScaleTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        if (! $this->x || $this->x < 0) {
            $this->x = 0;
        }
        if (! $this->y || $this->y < 0) {
            $this->y = 0;
        }
        if (! $this->scale || $this->scale < 0) {
            $this->scale = 0;
        }
        if (! $this->x && ! $this->y && ! $this->scale) {
            throw new Exception('Uncorrect resize params');
        }
        if ($this->x == 0 && $this->y == 0) {
            $this->x = $this->y = $this->scale;
        }
        $this->bestfit = (bool) $this->bestfit; // by default = false
    }

    protected function run()
    {
        $image = $this->getImage();
        $image->scaleImage($this->x, $this->y, $this->bestfit);
    }
}
