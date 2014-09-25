<?php

require_once APPLICATION_PATH . '/common/Task.php';

class WaterMarkTask extends Task
{
    private $types = array(
        'mosaic',
        'position',
    );
    private $composites = array(
        Imagick::COMPOSITE_DEFAULT,
        Imagick::COMPOSITE_UNDEFINED,
        Imagick::COMPOSITE_NO,
        Imagick::COMPOSITE_ADD,
        Imagick::COMPOSITE_ATOP,
        Imagick::COMPOSITE_BLEND,
        Imagick::COMPOSITE_BUMPMAP,
        Imagick::COMPOSITE_CLEAR,
        Imagick::COMPOSITE_COLORBURN,
        Imagick::COMPOSITE_COLORDODGE,
        Imagick::COMPOSITE_COLORIZE,
        Imagick::COMPOSITE_COPYBLACK,
        Imagick::COMPOSITE_COPYBLUE,
        Imagick::COMPOSITE_COPY,
        Imagick::COMPOSITE_COPYCYAN,
        Imagick::COMPOSITE_COPYGREEN,
        Imagick::COMPOSITE_COPYMAGENTA,
        Imagick::COMPOSITE_COPYOPACITY,
        Imagick::COMPOSITE_COPYRED,
        Imagick::COMPOSITE_COPYYELLOW,
        Imagick::COMPOSITE_DARKEN,
        Imagick::COMPOSITE_DSTATOP,
        Imagick::COMPOSITE_DST,
        Imagick::COMPOSITE_DSTIN,
        Imagick::COMPOSITE_DSTOUT,
        Imagick::COMPOSITE_DSTOVER,
        Imagick::COMPOSITE_DIFFERENCE,
        Imagick::COMPOSITE_DISPLACE,
        Imagick::COMPOSITE_DISSOLVE,
        Imagick::COMPOSITE_EXCLUSION,
        Imagick::COMPOSITE_HARDLIGHT,
        Imagick::COMPOSITE_HUE,
        Imagick::COMPOSITE_IN,
        Imagick::COMPOSITE_LIGHTEN,
        Imagick::COMPOSITE_LUMINIZE,
        Imagick::COMPOSITE_MINUS,
        Imagick::COMPOSITE_MODULATE,
        Imagick::COMPOSITE_MULTIPLY,
        Imagick::COMPOSITE_OUT,
        Imagick::COMPOSITE_OVER,
        Imagick::COMPOSITE_OVERLAY,
        Imagick::COMPOSITE_PLUS,
        Imagick::COMPOSITE_REPLACE,
        Imagick::COMPOSITE_SATURATE,
        Imagick::COMPOSITE_SCREEN,
        Imagick::COMPOSITE_SOFTLIGHT,
        Imagick::COMPOSITE_SRCATOP,
        Imagick::COMPOSITE_SRC,
        Imagick::COMPOSITE_SRCIN,
        Imagick::COMPOSITE_SRCOUT,
        Imagick::COMPOSITE_SRCOVER,
        Imagick::COMPOSITE_SUBTRACT,
        Imagick::COMPOSITE_THRESHOLD,
        Imagick::COMPOSITE_XOR,
    );

    protected function checkWaterMark()
    {
        if (! $this->watermark || ! file_exists($this->getStorePath($this->watermark))) {
            throw new Exception('Uncorrect watermark params');
        }
    }

    public function checkParams()
    {
        parent::checkParams();

        if (! in_array($this->type, $this->types))
        {
            if (is_null($this->x) || is_null($this->y)) {
                $this->type = 'mosaic';
            } else {
                $this->type = 'position';
            }
        }

        if (! in_array($this->composite, $this->composites, true)) {
            $this->composite = Imagick::COMPOSITE_DEFAULT;
        }
    }

    protected function run()
    {
        $image = $this->getImage();
        $watermark = new Imagick($this->getStorePath($this->watermark));

        if ($this->type == 'position') {
            $this->waterMarkPosition($image, $watermark);
        } else {
            $this->waterMarkMosaic($image, $watermark);
        }

        $watermark->clear();
        $watermark->destroy();
    }

    protected function waterMarkPosition($image, $watermark)
    {
        $this->x = Task::calcPositionX($this->x, $image->getImageWidth(), $watermark->getImageWidth());
        $this->y = Task::calcPositionY($this->y, $image->getImageHeight(), $watermark->getImageHeight());

        $image->compositeImage($watermark, $this->composite, $this->x, $this->y);
    }

    protected function waterMarkMosaic($image, $watermark)
    {
        $imageWidth      = $image->getImageWidth();
        $imageHeight     = $image->getImageHeight();
        $watermarkWidth  = $watermark->getImageWidth();
        $watermarkHeight = $watermark->getImageHeight();

        for ($x = 0 ; $x < $imageWidth ; $x += $watermarkWidth)
        {
            for ($y = 0 ; $y < $imageHeight ; $y += $watermarkHeight)
            {
                $image->compositeImage($watermark, $this->composite, $x, $y);
            }
        }
    }
}
