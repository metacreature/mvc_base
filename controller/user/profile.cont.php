<?php
/*
 File: profile.cont.php
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

class Controller_User_Profile extends Controller_Base
{
    function __construct($db) {
        $this->_forbidden(!SETTINGS_LOGIN_ENABLED);
        parent::__construct($db);
        $this->_check_login();
    }

    protected function _get_profile_form() {
        $form = new FW_Ajax_Form('profile_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Text', 'user_name', true)
            ->setMinLength(6);
        return $form;
    }

    protected function _get_email_form() {
        $form = new FW_Ajax_Form('email_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Password', 'actual_password', true);
        $form->addField('Email', 'user_email', true);
        return $form;
    }

    protected function _get_password_form() {
        $form = new FW_Ajax_Form('password_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Password', 'actual_password', true);
        $this->_add_create_password_fields($form);
        return $form;
    }

    function view() {
        $user_obj = new Model_User($this->_db, Controller_Base::get_user_id());
        $data = $user_obj->get();
        $profile_form = $this->_get_profile_form();
        $profile_form->resolveRequest($data);
        $email_form = $this->_get_email_form();
        $email_form->resolveRequest($data);
        $password_form = $this->_get_password_form();
        require_once (DOCUMENT_ROOT . '/views/user/profile.view.html');
    }

    function update_profile() {
        $form = $this->_get_profile_form();
        $form->resolveRequest();
        if ($form->validate()) {
            $user_obj = new Model_User($this->_db, Controller_Base::get_user_id());
            $res = $user_obj->update_profile(
                $form->getValue('user_name'));
            if ($res) {
                $_SESSION['user_name'] = $form->getValue('user_name');
                return $form->getFormSuccess(LANG_PROFILE_SUCCESS);
            }
            return $form->getFormError(LANG_PROFILE_DATA_FAIL);
        }
        return $form->getFormError(LANG_FORM_INVALID);
    }

    function update_email() {
        $form = $this->_get_email_form();
        $form->resolveRequest();
        if ($form->validate()) {
            $user_obj = new Model_User($this->_db, Controller_Base::get_user_id());
            $res = $user_obj->update_email(
                $form->getValue('actual_password'),
                $form->getValue('user_email'));
            if ($res) {
                $_SESSION['user_email'] = $form->getValue('user_email');
                return $form->getFormSuccess(LANG_PROFILE_SUCCESS);
            }
            return $form->getFormError(LANG_PROFILE_EMAIL_FAIL);
        }
        return $form->getFormError(LANG_FORM_INVALID);
    }

    function update_password() {
        $form = $this->_get_password_form();
        $form->resolveRequest();
        if ($this->_validate_create_password_form($form)) {
            $user_obj = new Model_User($this->_db, Controller_Base::get_user_id());
            $res = $user_obj->update_password(
                $form->getValue('actual_password'),
                $form->getValue('password'));
            if ($res) {
                return $form->getFormSuccess(LANG_PROFILE_SUCCESS);
            }
            return $form->getFormError(LANG_PROFILE_PASSWORD_FAIL);
        }
        return $form->getFormError(LANG_FORM_INVALID);
    }
}