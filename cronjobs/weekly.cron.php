<?php
/*
 File: weekly.cron.php
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

require_once (dirname(dirname(__FILE__)).'/lib/settings.inc.php');
require_once (DOCUMENT_ROOT.'/lib/fw/func.inc.php');
require_once (DOCUMENT_ROOT.'/lib/fw/FW_MySQLDataBaseLayer.class.php');

ini_set('max_execution_time', 3600);


$db = FW_MySQLDataBaseLayer::singleton(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PERSISTENT, SETTINGS_TIMEZONE, DEBUG_DB_QUERIES && IS_LOCALHOST);


$db->executeQuery('DELETE FROM tbl_user_remember WHERE insert_timestamp < NOW() - INTERVAL ? day', [SETTINGS_LOGIN_REMEMBER_EXPIRE]);
$db->executeQuery('DELETE FROM tbl_user_forgotten WHERE insert_timestamp < NOW() - INTERVAL ? MINUTE', [SETTINGS_FORGOTTEN_PASSWORD_EXPIRE]);
$db->executeQuery('DELETE FROM tbl_user_login_bruteforce WHERE insert_timestamp < NOW() - INTERVAL ? MINUTE', [SETTINGS_LOGIN_BRUTEFORCE_EXPIRE * 60]);

$db->optimizeTables([
    'tbl_user',
    'tbl_user_remember',
    'tbl_user_forgotten',
    'tbl_user_login_bruteforce',
]);

