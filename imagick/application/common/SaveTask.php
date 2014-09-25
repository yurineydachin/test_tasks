<?php

require_once APPLICATION_PATH . '/common/Task.php';

class SaveTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        if (! $this->destinationFile) {
            throw new Exception('No destinationFile');
        }
    }

    protected function run()
    {
        // @see Task::run, Task::saveImage
    }
}
