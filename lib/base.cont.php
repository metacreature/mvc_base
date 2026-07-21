<?php
/*
 File: base.cont.php
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

// require here coz it has to be global
require_once (DOCUMENT_ROOT . '/lib/fw/func.inc.php');
require_once (DOCUMENT_ROOT . '/lib/vendor/autoload.php');

class Controller_Base
{
    protected $_db = null;

    function __construct($db_credential_key) {

        // require here coz the script can be exited width _forbidden() before
        require_once (DOCUMENT_ROOT . '/lib/fw/FW_MySQL.class.php');
        require_once (DOCUMENT_ROOT . '/lib/fw/FW_Ajax_Form.class.php');
        require_once (DOCUMENT_ROOT . '/lib/fw/FW_Date.static.php');
        require_once (DOCUMENT_ROOT . '/lib/languagelist.inc.php');

        FW_Date::set_formats(SETTINGS_DATEFORMAT_DATETIME, SETTINGS_DATEFORMAT_DATE, SETTINGS_DATEFORMAT_TIME);

        // database
        FW_MySQL::setCredentials(DB_CREDENTIALS);
        FW_MySQL::setDebugMode(DEBUG_DB_QUERIES && IS_LOCALHOST);
        FW_MySQL::setTimezone(SETTINGS_TIMEZONE);

        $this->_db = FW_MySQL::singleton($db_credential_key);


        if (empty($_COOKIE['session']) || strlen($_COOKIE['session']) < 150) {
            $this->_sessionStart();
        } else {
            @session_name('session');
            @session_start();
            if (empty($_SESSION['session_started'])) {
                @session_destroy();
                $this->_sessionStart();
            }
        }
        
        if (SETTINGS_LOGIN_ENABLED) {
            if (SETTINGS_LOGIN_REMEMBER_ENABLED && empty($_SESSION['login']) && !empty($_COOKIE['remember_token'])) {
                
                // require here coz its not very often used
                require_once (DOCUMENT_ROOT . '/models/user.model.php');

                $user_obj = new Model_User($this->_db);
                $user_obj->setUserId(Controller_Base::getUserId());
                $data = $user_obj->loginRememberToken($_COOKIE['remember_token']);
                if ($data) {
                    $_SESSION = array_merge($_SESSION, $data);
                } else {
                    setcookie("remember_token", '', 1, "/", WEB_DOMAIN);
                }
            }
        } else {
            $_SESSION['login'] = false;
        }

        $this->_selectLanguage();
        $this->_selectTemplate();
    }
    
    protected function _selectLanguage() {
        // language
        $selected_lang = SETTINGS_LANG_DEFAULT;
        if (!empty($_COOKIE['selected_lang']) && in_array($_COOKIE['selected_lang'], SETTINGS_LANG_AVAILABLE)) {
            $selected_lang = $_COOKIE['selected_lang'];
        } else {
            $accept_lang = preg_split('#[,;]#', $_SERVER['HTTP_ACCEPT_LANGUAGE']); //de,en-US;q=0.9,en;q=0.8
            foreach ($accept_lang as $lang) {
                $lang = preg_replace('#^([a-z]+).*#', '$1',$lang);
                if (in_array($lang, SETTINGS_LANG_AVAILABLE)) {
                    $selected_lang = $lang;
                    break;
                }
            }
        }

        define('SELECTED_LANG', $selected_lang);
        require_once (DOCUMENT_ROOT . '/language/' . SELECTED_LANG . '.lang.php');
    }
    
    protected function _selectTemplate() {
        define('TEMPLATE_ROOT', DOCUMENT_ROOT . '/views');
    }

    protected function _forbidden($is_forbidden) {
        if ($is_forbidden) {
            header('HTTP/1.0 403 Forbidden');
            require_once(DOCUMENT_ROOT . '/403.html');
            exit;
        }
    }

    protected function _logout() {
        if (!empty($_COOKIE['remember_token'])) {
            $user_obj = new Model_User($this->_db);
            $user_obj->setUserId(Controller_Base::getUserId());
            $user_obj->removeRememberToken($_COOKIE['remember_token']);
            setcookie("remember_token", '', 1, "/", WEB_DOMAIN);
        }
        @session_destroy();
        $this->_sessionStart();
    }

    private function _sessionStart() {
        $ip = SECURITY_LOGIN_USE_IP ? $_SERVER['REMOTE_ADDR'] : '';
        $user_agent = SECURITY_LOGIN_USE_USER_AGENT ? $_SERVER['HTTP_USER_AGENT'] : '';
        $id = create_user_token(session_create_id(), $ip.$user_agent );
        @session_name('session');
        @session_id($id);
        @session_start();
        $_SESSION['session_started'] = true;
    }

    protected static function isLogin() {
        return !empty($_SESSION) && !empty($_SESSION['login']);
    }

    protected static function getUserId() {
        return !empty($_SESSION) && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    }
    
    protected static function getUserName() {
        return !empty($_SESSION) && !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
    }

    protected function _checkLogin() {
        if (!in_array('login', $_SESSION) || !$_SESSION['login']) {
            @session_destroy();
            if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
                $target = str_replace("?logout", "", $_SERVER['REQUEST_URI']);
                if ($target == '' || $target == '/') {
                    header('Location: ' . WEB_URL . '/user/login');
                } else {
                    header('Location: ' . WEB_URL . '/user/login?target='.urlencode($target));
                }
            } else {
                header('Content-Type: application/json; charset=utf-8');
		        echo json_encode(['error' => 1, 'message' => CHECK_LOGIN_ERROR_NOT_LOGIN]);
		        ob_end_flush();
            }
            exit;
        }
    }

    protected function _addPasswordFields($form) {
        $field_type = SETTINGS_REGISTER_USER_DEFINED_PASSWORDS ? 'Password' : 'Hidden';

        $form->addField($field_type, 'password', true)
            ->setMinLength(8)
            ->setFieldErrors(['external' => LANG_FIELD_USER_PASSWORD_ERROR]);
        $form->addField($field_type, 'password_confirmation', true)
            ->setHelper(true)
            ->setFieldErrors(['external' => LANG_FIELD_USER_REPEAT_PASSWORD_ERROR]);
    }

    protected function _validatePasswordFields($form) {
        $valid = $form->validate();
        $password = $form->getField('password');
        if ($password->isValid()) {
            foreach( ['[a-z]', '[A-Z]', '[0-9]', '[^a-zA-Z0-9 \t\r\n]'] as $regex) {
                if (!preg_match('#' . $regex . '#', $password->getValue())) {
                    $password->setErrorCode('external');
                    $valid = false;
                }
            }
        }
        $password_confirmation = $form->getField('password_confirmation');
        if ($password_confirmation->getValue() !== $password->getValue()) {
            $password_confirmation->setErrorCode('external');
            $valid = false;
        }
        return $valid;
    }

    protected function _validateCaptcha($data) {
        if (!SECURITY_ENABLE_CAPTCHA) {
            return true;
        }

        $options = require DOCUMENT_ROOT . '/lib/captcha.ini.php';
        $captcha = new \IconCaptcha\IconCaptcha($options);

        return $captcha->validate($data)->success() ? true : false;
    }
}