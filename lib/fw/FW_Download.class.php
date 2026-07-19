<?php


class FW_Download{

	protected $_data;

	protected $_filename;

	protected $_mime_type;

	protected $_is_attachment;

	function __construct($data, $filename, $mime_type = null, $is_attachment = true) {
		$this->_data = $data;
		$this->_filename = basename($filename);
		$this->_mime_type = $mime_type;
		$this->_is_attachment = $is_attachment;
	}

	static function getMimeType($string) {
		$extension = explode('.', $string);
		$extension = end($extension);
		$extension = strtolower($extension);
		if (!array_key_exists($extension, self::AVAILABLE_EXTENSIONS)) {
			return self::AVAILABLE_EXTENSIONS[$extension];
		}
		return 'application/octet-stream';
	}

	function send() {
		if (!$this->_mime_type) {
			$this->_mime_type = self::getMimeType($this->_filename);
		}
		if (is_string($this->_data)) {
			if (preg_match('#^([a-z0-9_.\\\\\/-]+)\\.([a-z0-9]+)$#i', $this->_data) !== false) {
				$orig_filename = basename($this->_data);
				if (preg_match('#^(\..+|.+\.(php|html|ini|inc|log))$#i', $orig_filename)) {
					return false;
				}
				if (file_exists($this->_data)) {
					$this->_sendHeaders();
					readfile($this->_data);
				    return true;
				}
			} else {
				$this->_sendHeaders();
				echo $this->_data;
				return true;
			}
		}
		return false;
	}

	protected function _sendHeaders() {
		if ($this->_mime_type) {
			header('Content-type: '.$this->_mime_type);
		}
		$disposit = $this->_is_attachment ? 'attachment' : 'inline';
		header('Content-Disposition: '.$disposit.'; filename='.$this->_filename);
	}

	const AVAILABLE_EXTENSIONS = array(
        'aac' => 'audio/aac',
        'abw' => 'application/x-abiword',
        'arc' => 'application/x-freearc',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'cda' => 'application/x-cdf',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'gz' => 'application/gzip',
        'gif' => 'image/gif',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/vnd.microsoft.icon',
        'ics' => 'text/calendar',
        'jar' => 'application/java-archive',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'jsonld' => 'application/ld+json',
        'mid' => 'audio/midi audio/x-midi',
        'midi' => 'audio/midi audio/x-midi',
        'mjs' => 'text/javascript',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mkv' => 'video/mkv',
        'mpkg' => 'application/vnd.apple.installer+xml',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'opus' => 'audio/opus',
        'otf' => 'font/otf',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'php' => 'application/x-httpd-php',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'sh' => 'application/x-sh',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'video/mp2t',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'vsd' => 'application/vnd.visio',
        'wav' => 'audio/wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip',
        '3gp' => 'video/3gpp',
        '3g2' => 'video/3gpp2',
        '7z' => 'application/x-7z-compressed'
    );
}