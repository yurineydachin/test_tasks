<?php

require_once APPLICATION_PATH . '/common/Task.php';

class MailTask extends Task
{
    public function checkParams()
    {
        parent::checkParams();

        if (! $this->to) {
            throw new Exception('No to param');
        }
        if (! $this->subject) {
            $this->subject = 'ImagickTaskMail';
        }
        if (! $this->from) {
            $this->from = $this->plugin->getConfigZ()->mail->from;
        }
    }

    protected function run()
    {
        $mail = new Zend_Mail($this->plugin->getConfigZ()->mail->charset);
        $mail->addTo($this->to);
        $mail->setFrom($this->from);
        $mail->setSubject($this->subject);

        $mail->setBodyText(
            date('Y-m-d H:i:s')
        );

        $image = $this->getImage();
        $mimeType = 'image/' . strtolower($image->getImageFormat());
        $mail->createAttachment($image, $mimeType, Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $this->filename);
        $mail->send();
    }
}
