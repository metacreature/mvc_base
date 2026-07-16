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


require_once (DOCUMENT_ROOT . '/lib/fw/FW_Ajax_Form.class.php');

class Controller_Base
{
    protected $_db = null;

    function __construct($db) {
        $this->_db = $db;

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
                $user_obj = new Model_User($this->_db, Controller_Base::get_user_id());
                $data = $user_obj->loginRememberToken($_COOKIE['remember_token']);
                if ($data) {
                    $_SESSION['login'] = true;
                    $_SESSION['user_id'] = $data['user_id'];
                    $_SESSION['user_name'] = $data['user_name'];
                } else {
                    setcookie("remember_token", '', 1, "/", WEB_DOMAIN);
                }
            }
        } else {
            $_SESSION['login'] = false;
        }
    }

    protected function _forbidden($is_forbidden) {
        if ($is_forbidden) {
            header('HTTP/1.0 403 Forbidden');
            require_once(DOCUMENT_ROOT . '/crawler.html');
            exit;
        }
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

    static function is_login() {
        return !empty($_SESSION) && !empty($_SESSION['login']);
    }

    static function get_user_id() {
        return !empty($_SESSION) && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    }
    
    static function get_user_name() {
        return !empty($_SESSION) && !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
    }

    protected function _check_login() {
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

    protected function _add_create_password_fields($form) {
        $field_type = SETTINGS_REGISTER_USER_DEFINED_PASSWORDS ? 'Password' : 'Hidden';

        $form->addField($field_type, 'password', true)
            ->setMinLength(8)
            ->setFieldErrors(['external' => LANG_FIELD_USER_PASSWORD_ERROR]);
        $form->addField($field_type, 'password_confirmation', true)
            ->setFieldErrors(['external' => LANG_FIELD_USER_REPEAT_PASSWORD_ERROR]);
    }

    protected function _validate_create_password_form($form) {
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

    protected function _logout() {
        @session_destroy();
    }
}