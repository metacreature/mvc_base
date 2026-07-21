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

// settings include
require_once ('lib/settings.inc.php');

function log_runtime() {
	global $time_start;

	if (DEBUG_EXECUTION_TIME) {
		$time_end = microtime(true);
		$time = round(($time_end - $time_start) * 1000, 2);
		$url = str_replace('"', '', $_SERVER['REQUEST_URI']);
		$memory = round(memory_get_usage() / 1024);

		$dirname = DOCUMENT_ROOT. '/_logs/';
		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
			file_put_contents($dirname . '.htaccess', 'deny from all');
		}
		
		$file_name = 'execution_time.'.hash('sha256', SECURITY_SALT).'.log.csv';
		$f = fopen($dirname .$file_name, "a+");
		fwrite($f, '"' . $url . '";' . $time . ';ms;' . $memory . ";kb\n");
		fclose($f );
	}
}

function my_ob_clean() {
	if (!IS_LOCALHOST) {
		@ob_clean();
	}
}

function error_forbidden() {
	header('HTTP/1.0 403 Forbidden');
	require_once(DOCUMENT_ROOT . '/403.html');
}

function error_notfound() {
	header('HTTP/1.0 404 Not Found');
	require_once(DOCUMENT_ROOT . '/404.html');
}

// get request
$url = str_replace('\\', '/', $_SERVER['REQUEST_URI']);
$url = trim(preg_replace('/[\?#].*$/s', '', $url), " \t\r\n/");
$slashpos = strrpos($url, '/');

// get controller and function
$controller_request = substr($url, 0, $slashpos === false ? null : $slashpos);

if ($url && preg_match('#^[a-z0-9_\/-]+$#', $url) && file_exists('controller/' . $url  . '.cont.php')) {
	$controller_file = 'controller/' . $url;
	$function = 'view';
} elseif ($controller_request && preg_match('#^[a-z0-9_\/]+$#', $controller_request) && file_exists('controller/' . $controller_request  . '.cont.php')) {
	$controller_file = 'controller/' . $controller_request;
	$function = $slashpos === false ? '' : substr($url, $slashpos + 1);
} else if (!$controller_request) {
	$controller_file = 'controller' . SETTINGS_LANDING_PAGE;
	$function = 'view';
} else {
	error_forbidden();
	log_runtime();
    exit;
}

/////////////////////////////////////////////////////////////////////////////////
///////////////////////////// legit request /////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

//  require here coz the script may exit earlier
require_once ('lib/fw/FW_Download.class.php'); 

ignore_user_abort(true);


// choose db and replace the hidden admin path for correct class-name
$tmp = preg_split('#[\/]#', $controller_file);
if (preg_match('#^'.SECURITY_ADMIN_CONTROLLER_FOLDER.'$#', $tmp[1]) === 1) {
	$tmp[1] = 'admin';
	define('DB_CREDENTIAL_KEY', 'ADMIN');
} else {
	define('DB_CREDENTIAL_KEY', 'USER');
}


// execute
$controller_name = implode('_', array_map('ucfirst', preg_split('#[\/_]#', implode('/', $tmp))));
@require_once($controller_file  . '.cont.php');
$obj_controller = new $controller_name(DB_CREDENTIAL_KEY);

if ($function && preg_match('#^[a-z0-9_]+$#', $function) && method_exists($obj_controller, $function)) {
	ob_start();

	$result = [$obj_controller, $function]();

	if (is_null($result)) {
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		ob_end_flush();
	} else if (is_array($result)) {
		my_ob_clean();
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($result);
		ob_end_flush();
	} else if (is_string($result)) {
		my_ob_clean();
		header('Location: ' . $result);
	} else if ($result instanceof FW_Download) {
		my_ob_clean();
		if (!$result->send()) {
			error_notfound();
		}
	} else {
		my_ob_clean();
		error_forbidden();
	}
	log_runtime();
    exit;
} else {
	error_forbidden();
	log_runtime();
    exit;
}



