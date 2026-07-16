<?php
/*
 File: login.cont.php
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


require_once (DOCUMENT_ROOT . '/lib/base.cont.php');
require_once (DOCUMENT_ROOT . '/models/user.model.php');

class Controller_User_Login extends Controller_Base
{
    function __construct($db) {
        $this->_forbidden(!SETTINGS_LOGIN_ENABLED);
        parent::__construct($db);
    }

    protected function _get_form() {
        $form = new FW_Ajax_Form('login_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Email', 'email', true);
        $form->addField('Password', 'password', true);
        $form->addField('Checkbox', 'remember_login', false);
        return $form;
    }

    function view() {
        $this->_logout();
        $form = $this->_get_form();
        require_once (DOCUMENT_ROOT . '/views/user/login.view.html');
    }

    function save() {
        $form = $this->_get_form();
        $form->resolveRequest();
        if ($form->validate($form)) {
            $user_obj = new Model_User($this->_db, 0);
            $data = $user_obj->login($form->getValue('email'), $form->getValue('password'));
            if ($data) {
                $_SESSION['login'] = true;
                $_SESSION['user_id'] = $data['user_id'];
                $_SESSION['user_name'] = $data['user_name'];
                if (SETTINGS_LOGIN_REMEMBER_ENABLED && $form->getValue('remember_login')) {
                    $user_token = $user_obj->addRememberToken($form->getValue('password'));
                    setcookie("remember_token", $user_token, time() + SETTINGS_LOGIN_REMEMBER_EXPIRE * 86400, "/", WEB_DOMAIN);
                }
                return $form->getFormSuccess(LANG_LOGIN_SUCCESS);
            } else if ($data === false)  {
                return $form->getFormError(LANG_LOGIN_BRUTE_FORCE);
            }
        }
        return $form->getFormError(LANG_LOGIN_FAIL);
        
    }

    function logout() {
        if (!empty($_COOKIE['remember_token'])) {
            $user_obj = new Model_User($this->_db, Controller_Base::get_user_id());
            $user_obj->removeRememberToken($_COOKIE['remember_token']);
            setcookie("remember_token", '', 1, "/", WEB_DOMAIN);
        }
        @session_destroy();
        return WEB_URL . '?logout';
    }
}