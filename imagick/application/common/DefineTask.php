<?php

require_once APPLICATION_PATH . '/common/Task.php';

class DefineTask extends Task
{
    protected function run()
    {
        $exif = $this->readExif();
        $isAdobeRGB = isset($exif['InteroperabilityIndex']) && $exif['InteroperabilityIndex'] == 'R03';
        $image = $this->getImage();

        if (! $isAdobeRGB)
        {
            $profiles = $image->getImageProfiles();
            $this->plugin->log(implode(', ', array_keys($profiles)), 'getImageProfiles');

            if (isset($profiles['icc']) && strpos($profiles['icc'], 'Adobe RGB') !== false) {
                $isAdobeRGB = true;
            }
        }

        if ($isAdobeRGB)
        {
            $this->plugin->log('icc is Adobe RGB');
            $image->profileImage('icc', file_get_contents(HTDOCS_PATH . '/stuff/AdobeRGB1998.icc'));
        }

        $image->profileImage('icc', file_get_contents(HTDOCS_PATH . '/stuff/sRGB_v4_ICC_preference.icc'));

        $image->stripImage();
        $image->setImageResolution(72,72);
    }
}
