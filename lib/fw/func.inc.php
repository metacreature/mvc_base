<?php
/*
 File: func.inc.php
 Copyright (c) 2014 Clemens K. (https://github.com/metacreature)
 
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

define('REGEX_EMAIL', '#^[a-z0-9]+([_\.-][a-z0-9]+)*@[a-z0-9]+([_\.-][a-z0-9]+)*\.[a-z]{2,6}$#i');
define('REGEX_URL', '#^((http|https|ftp)\://)?[a-z0-9\-\.]+\.[a-z]{2,6}/?([a-z0-9 \-\._\?\,\'/\\\+&amp;%\$\#\=~])*$#i');
define('REGEX_PROTOCOL', '#^(http|https|ftp):\/\/#i');

define('FORMAT_DATE', 'd.m.Y');
define('FORMAT_TIME', 'H:i:s');
define('FORMAT_DATETIME', 'd.m.Y H:i:s');

// session functions
function my_session_start()
{
    if (empty($GLOBALS['bSessionStarted'])) {
        session_start();
        ignore_user_abort(true);
        setcookie('PHPSESSID', session_id(), null, '/', WEB_DOMAIN);
        $GLOBALS['bSessionStarted'] = true;
    }
}

function my_session_destroy()
{
    if (! empty($GLOBALS['bSessionStarted'])) {
        session_destroy();
        ignore_user_abort(true);
        $GLOBALS['bSessionStarted'] = false;
    }
}

function iniUserKey()
{
    $sUserKey = ! empty($_COOKIE[APP_KEY . '_usk']) ? $_COOKIE[APP_KEY . '_usk'] : (! empty($_GET[APP_KEY . '_usk']) ? $_GET[APP_KEY . '_usk'] : '');
    if (! $sUserKey && empty($_SESSION[APP_KEY . '_user_key'])) {
        $_SESSION[APP_KEY . '_user_key'] = uniqid();
    } else if (preg_match('#^[a-zA-Z0-9]{5,30}$#', $sUserKey)) {
        if (empty($_SESSION[APP_KEY . '_user_key'])) {
            $_SESSION[APP_KEY . '_user_key'] = $sUserKey;
        }
    } else if (empty($_SESSION[APP_KEY . '_user_key'])) {
        $_SESSION[APP_KEY . '_user_key'] = uniqid();
    }
    setcookie(APP_KEY . '_usk', $_SESSION[APP_KEY . '_user_key'], time() + 3600 * 24 * 700, '/', WEB_DOMAIN);
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir . '/' . $object) == 'dir') {
                    rrmdir($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }
        }

        reset($objects);
        rmdir($dir);
    }
}

// security
function getIdSecureLink($id)
{
    return $id . '&id_secure=' . md5($id . SECURE_SALT . $id);
}

function checkIdSecureLink($id)
{
    if (empty($_GET['id_secure']) || $_GET['id_secure'] !== md5($id . SECURE_SALT . $id)) {
        return false;
    } else {
        return true;
    }
}

function replaceTrackingLinks($sText)
{
    return preg_replace_callback('#href="([^"]+)"#iU', '_replaceTrackingLinksCallback', $sText);
}

function _replaceTrackingLinksCallback($matches)
{
    return 'href="' . WEB_ROOT . '/l.php' . HOTLINK_PROTECTION . '&t=link&l=' . rawurlencode($matches[1]) . '"';
}

// other functions
function cloneObj($oObj)
{
    if (is_object($oObj)) {
        return clone $oObj;
    }
    return $oObj;
}

function mb_trim(&$sString)
{
    return trim($sString == null ? '' : $sString);
}

function xssProtect(&$sString)
{
    if (is_null($sString)) {
        return '';
    }
    return htmlspecialchars($sString, ENT_COMPAT, 'UTF-8');
}

function preint_r($mValue)
{
    echo '<pre>';
    print_r($mValue);
    echo '</pre>';
}

function uidmore() {
    $uid = uniqid('', true);
    $uid = str_replace('.', '', $uid);
    return substr($uid, 0, 22);
}

function create_user_token($dynamic_salt1 = '', $dynamic_salt2 = '') {
    $token = hash('sha512', uidmore() . SECURE_SALT . $dynamic_salt1 . mt_rand() . $dynamic_salt2 . mt_rand() . SECURE_SALT . mt_rand());
    $token_uid = uidmore();
    return $token.$token_uid;
}

function create_db_token($token, $dynamic_salt = '') {
    $token_uid = substr($token, -22);
    return hash('sha512', $token . SECURE_SALT . $dynamic_salt).$token_uid;
}