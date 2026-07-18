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


// xxsProtect
function outT($sString) {
    if (!is_null($sString)) {
        echo htmlspecialchars($sString, ENT_COMPAT, 'UTF-8');
    }
}

function outHtml($sString) {
    if (!is_null($sString)) {
        echo $sString;
    }
}
function outLang($sString) {
    if (!is_null($sString)) {
        echo $sString;
    }
}
function outLink($sString) {
    if (!is_null($sString)) {
    	echo str_replace('&amp;', '&', htmlspecialchars($sString, ENT_QUOTES, 'UTF-8'));
    }
}
function outJS($sString) {
    if (!is_null($sString)) {
        echo str_replace(array('&quot;', '&#039;'), array('\\"', "\\'"), htmlspecialchars($sString, ENT_QUOTES, 'UTF-8'));
    }
}
function outLangJS($sString) {
    if (!is_null($sString)) {
        echo str_replace(array('"', "'"), array('\\"', "\\'"), $sString);
    }
}

function xssProtect($sString)
{
    if (is_null($sString)) {
        return '';
    }
    return htmlspecialchars($sString, ENT_COMPAT, 'UTF-8');
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
    return $id . '&id_secure=' . md5($id . SECURITY_SALT . $id);
}

function checkIdSecureLink($id)
{
    if (empty($_GET['id_secure']) || $_GET['id_secure'] !== md5($id . SECURITY_SALT . $id)) {
        return false;
    } else {
        return true;
    }
}

// other functions
function cloneObj($oObj)
{
    if (is_object($oObj)) {
        return clone $oObj;
    }
    return $oObj;
}

function mb_trim($sString)
{
    return trim($sString == null ? '' : $sString);
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
    $token = hash('sha512', uidmore() . SECURITY_SALT . $dynamic_salt1 . mt_rand() . $dynamic_salt2 . mt_rand() . SECURITY_SALT . mt_rand());
    $token_uid = uidmore();
    return $token.$token_uid;
}

function create_db_token($token, $dynamic_salt = '') {
    $token_uid = substr($token, -22);
    return hash('sha512', $token . SECURITY_SALT . $dynamic_salt).$token_uid;
}