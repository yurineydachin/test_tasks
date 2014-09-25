<?php

require_once APPLICATION_PATH . '/common/ScaleTask.php';

class ResizeTask extends ScaleTask
{
    private $filters = array(
        Imagick::FILTER_UNDEFINED,
        Imagick::FILTER_POINT,
        Imagick::FILTER_BOX,
        Imagick::FILTER_TRIANGLE,
        Imagick::FILTER_HERMITE,
        Imagick::FILTER_HANNING,
        Imagick::FILTER_HAMMING,
        Imagick::FILTER_BLACKMAN,
        Imagick::FILTER_GAUSSIAN,
        Imagick::FILTER_QUADRATIC,
        Imagick::FILTER_CUBIC,
        Imagick::FILTER_CATROM,
        Imagick::FILTER_MITCHELL,
        Imagick::FILTER_LANCZOS,
        Imagick::FILTER_BESSEL,
        Imagick::FILTER_SINC,
    );

    public function checkParams()
    {
        parent::checkParams();

        if (! in_array($this->filter, $this->filters, true)) {
            $this->filter = Imagick::FILTER_UNDEFINED;
        }
        $this->blur = (float) $this->blur;
        if (! $this->blur || $this->blur < 0) {
            $this->blur = 1;
        }
    }

    protected function run()
    {
        $image = $this->getImage();
        $image->resizeImage($this->x, $this->y, $this->filter, $this->blur, $this->bestfit);
    }
}
