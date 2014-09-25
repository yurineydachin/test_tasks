<?php

require_once APPLICATION_PATH . '/common/Task.php';

class DebugTask extends Task
{
    protected function run()
    {
        $info = array(
            'getFilename',
            /*
            'getFormat',
            'getCompression',
            'getImageBackgroundColor',
            //'getImageColormapColor',
            'getImageColorspace',
            'getImageColors',
            'getImageDelay',
            'getImageCompose',
            //'getImageChannelDepth',
            'getImageChannelStatistics',
            'getImageDepth',
            'getImageDispose',
            'getImageExtrema',
            'getImageFilename',
            'getImageFormat',
            'getImageGamma',
            //'getImageHistogram',
            //'getImageIndex',
            'getImageMatteColor',
            //'getImageProfile',
            //'getImageProperty',
            //'getImageRegion',
            'getImageResolution',
            'getImageScene',
            'getImageSignature',
            'getImageTotalInkDensity',
            'getQuantumDepth',
            'getQuantumRange',
            'getSamplingFactors',
            'getSizeOffset',
            'getSize',
            */
            'getImageType',
            'getImageMimeType',
            'getImageLength',
        );
        $image = $this->getImage();
        foreach ($info as $method) {
            $this->plugin->log($image->$method(), $method);
        }
        //$this->plugin->log(array_keys($image->getImageProfiles()), 'getImageProfiles');

        $this->plugin->log($this->readExif(), 'exif');
    }
}
