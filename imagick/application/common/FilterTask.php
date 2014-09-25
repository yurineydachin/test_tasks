<?php

require_once APPLICATION_PATH . '/common/Task.php';

class FilterTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        $this->radius = (int) $this->radius;
        if (! $this->radius || $this->radius < 0) {
            throw new Exception('Uncorrect filter params');
        }
    }

    protected function run()
    {
        $image = $this->getImage();
        $image->medianFilterImage($this->radius);
    }
}
