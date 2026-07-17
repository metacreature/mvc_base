<?php
/*
 File: settings.inc.php
 Copyright (c) 2025 Clemens K. (https://github.com/metacreature)

 MIT License

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all
 copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 SOFTWARE.
*/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

if (!empty($_SERVER['HTTP_HOST'])) {
    define('IS_LOCALHOST', preg_match('#localhost$#', $_SERVER['HTTP_HOST']) !== false);
} else {
    define('IS_LOCALHOST', false);
}

define('WEB_ROOT', '');
define('WEB_DOMAIN', $_SERVER['HTTP_HOST']);
define('WEB_URL', $_SERVER['REQUEST_SCHEME'].'://'.WEB_DOMAIN);
define('DOCUMENT_ROOT', dirname(dirname(__FILE__)));

$ini_data = parse_ini_file(DOCUMENT_ROOT.'/.env.'.strtolower($_SERVER['SERVER_NAME']), false, INI_SCANNER_TYPED);

if ($ini_data['SECURITY_FORCE_HTTPS'] && strtolower($_SERVER['REQUEST_SCHEME']) !== 'https') {
	header('Location: https://'.WEB_DOMAIN);
}

foreach($ini_data as $key => $value) {
	try{
		define($key, $value);
	} catch (Exception $e) {}
}

ini_set('error_reporting', intval(PHP_ERROR_REPORTING));
ini_set('display_errors', PHP_DISPLAY_ERRORS);

if (SETTINGS_TIMEZONE) {
	date_default_timezone_set(SETTINGS_TIMEZONE);
}

unset($ini_data);

require './lib/vendor/autoload.php';