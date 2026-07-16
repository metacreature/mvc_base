<?php
/*
 File: index.php
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

$time_start = microtime(true);

function log_runtime() {
	global $time_start;

	if (DEBUG_EXECUTION_TIME) {
		$time_end = microtime(true);
		$time = round(($time_end - $time_start) * 1000);
		$url = str_replace('"', '', $_SERVER['REQUEST_URI']);
		$memory = round(memory_get_usage() / 1024);

		$dirname = DOCUMENT_ROOT. '/_logs/';
		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
			file_put_contents($dirname . '.htaccess', 'deny from all');
		}
		
		$file_name = 'execution_time.'.hash('sha256', SECURE_SALT).'.log.csv';
		$f = fopen($dirname .$file_name, "a+");
		fwrite($f, '"' . $url . '";' . $time . ';ms;' . $memory . ";kb\n");
		fclose($f );
	}
}

// FW-Includes
require_once ('lib/settings.inc.php');
require_once ('lib/languagelist.php');
require_once ('lib/fw/func.inc.php');
require_once ('lib/fw/FW_ErrorLogger.static.php');
require_once ('lib/fw/FW_MySQLDataBaseLayer.class.php');

// language
$selected_lang = SETTINGS_DEFAULT_LANG;
if (!empty($_COOKIE['selected_lang']) && in_array($_COOKIE['selected_lang'], SETTINGS_AVAILABLE_LANG)) {
	$selected_lang = $_COOKIE['selected_lang'];
}
define('SELECTED_LANG', $selected_lang);
require_once (DOCUMENT_ROOT . '/language/' . SELECTED_LANG . '.lang.php');

// database
$db = FW_MySQLDataBaseLayer::singleton(DEBUG_MODE);
ignore_user_abort(true);

// get request
$url = str_replace('\\', '/', $_SERVER['REQUEST_URI']);
$url = trim(preg_replace('/[\?#].*$/s', '', $url), " \t\r\n/");
$slashpos = strrpos($url, '/');

// get controller and function
$controller_request = substr($url, 0, $slashpos === false ? null : $slashpos);

if ($url && preg_match('#^[a-z_\/]+$#', $url) && file_exists('controller/' . $url  . '.cont.php')) {
	$controller_file = 'controller/' . $url;
	$function = 'view';
} elseif ($controller_request && preg_match('#^[a-z_\/]+$#', $controller_request) && file_exists('controller/' . $controller_request  . '.cont.php')) {
	$controller_file = 'controller/' . $controller_request;
	$function = $slashpos === false ? '' : substr($url, $slashpos + 1);
} else if (!$controller_request) {
	$controller_file = 'controller' . SETTINGS_LANDING_PAGE;
	$function = 'view';
} else {
	header('HTTP/1.0 403 Forbidden');
	require_once(DOCUMENT_ROOT . '/crawler.html');
	log_runtime();
    exit;
}

$controller_name = implode('_', array_map('ucfirst', preg_split('#[\/_]#', $controller_file)));


// execute
@require_once($controller_file  . '.cont.php');
$obj_controller = new $controller_name($db);

if ($function && preg_match('#^[a-z_]+$#', $function) && method_exists($obj_controller, $function)) {
	ob_start();

	$result = [$obj_controller, $function]();

	if (is_string($result)) {
		header('Location: ' . $result);
	} elseif (is_array($result)) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($result);
		ob_end_flush();
	} else {
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		ob_end_flush();
	}
	log_runtime();
    exit;
} else {
	header('HTTP/1.0 403 Forbidden');
	require_once(DOCUMENT_ROOT . '/crawler.html');
	log_runtime();
    exit;
}



