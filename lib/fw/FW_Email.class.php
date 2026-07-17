<?php

class FW_Email extends PHPMailer\PHPMailer\PHPMailer
{

	function html2text($html, $ignore_error = false) {
		$html = preg_replace('#([\n\r\t ]*<br>|<br />)[\n\r\t ]*#', "\n", $html);
		return Soundasleep\Html2Text::convert(nl2br($html), $ignore_error);
	}

	function send($debug = false)
	{
		if ($debug) {
			$dirname = DOCUMENT_ROOT. '/_mails/';
			if (!file_exists($dirname)) {
				mkdir($dirname, 0777, true);
				file_put_contents($dirname . '.htaccess', 'deny from all');
			}
			$file_name = 'mail_.'.date("Y-m-d-H-i-s");

			file_put_contents($dirname . $file_name .'.html', $this->Body);
			if ($this->AltBody) {
				file_put_contents($dirname . $file_name .'.txt', $this->AltBody);
			}
		}
		parent::send();
	}
}