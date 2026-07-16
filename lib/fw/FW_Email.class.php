<?php

class FW_Email extends PHPMailer\PHPMailer\PHPMailer
{
	function send() 
	{
		if (TEST_SERVER) {
			$dirname = DOCUMENT_ROOT. '/_mails/';
			if (!file_exists($dirname)) {
				mkdir($dirname, 0777, true);
				file_put_contents($dirname . '.htaccess', 'deny from all');
			}
			$file_name = 'mail_.'.date("Y-m-d-H-i-s").'_'.hash('sha256', SECURE_SALT);
			
			file_put_contents($dirname . $file_name .'.html', $this->Body);
			if ($this->AltBody) {
				file_put_contents($dirname . $file_name .'.txt', $this->AltBody);
			}
		}
		parent::send();
	}

}