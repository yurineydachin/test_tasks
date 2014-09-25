<?php

require_once APPLICATION_PATH . '/common/WaterMarkTask.php';

class AnnotateTask extends WaterMarkTask
{
    const FONT = '/usr/share/fonts/truetype/DejaVuSans.ttf';
    const FONT_SIZE = 12;
    const FONT_COLOR = 'black';

    protected function checkWaterMark()
    {
    }

    public function checkParams()
    {
        parent::checkParams();

        if (! $this->text) {
            throw new Exception('No text for annotate');
        }
        if (! $this->font) {
            $this->font = self::FONT;
        }
        if (! $this->fontSize || $this->fontSize <= 0 || ! is_numeric($this->fontSize)) {
            $this->fontSize = self::FONT_SIZE;
        }
        if (! $this->fontColor) {
            $this->fontColor = self::FONT_COLOR;
        }

        $this->degrees = (float) $this->degrees;
    }

    protected function run()
    {
        $image = $this->getImage();

        $meta = new ImagickDraw();
        $meta->setFont($this->getStorePath($this->font));
        $meta->setFontSize($this->fontSize);
        $meta->setFillColor($this->fontColor);
        $meta->setStrokeAntialias(true);
        $meta->setTextAntialias(true);

        $annotate = new Imagick();
        $metrics = $annotate->queryFontMetrics($meta, $this->text);
        $meta->annotation(0, $metrics['ascender'], $this->text);
        $annotate->newImage($metrics['textWidth'], $metrics['textHeight'], new ImagickPixel('none'));
        $annotate->setImageFormat('png');
        $annotate->drawImage($meta);
        if ($this->degrees) {
            $annotate->rotateImage(new ImagickPixel('none'), $this->degrees);
        }

        if ($this->type == 'position') {
            $this->waterMarkPosition($image, $annotate);
        } else {
            $this->waterMarkMosaic($image, $annotate);
        }

        $annotate->clear();
        $annotate->destroy();

        //$image = $this->getImage();
        //$image->compositeImage($annotate, Imagick::COMPOSITE_DEFAULT, $this->x, $this->y);

        //$image->annotateImage($meta, $this->x, $this->y, $this->degrees, $this->text);
    }
}
