<?php
/*
 File: register.cont.php
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

class Controller_User_Register extends Controller_Base
{
    function __construct($db) {
        parent::__construct($db);
        if (!SETTINGS_ALLOW_REGISTER) {
            header('HTTP/1.0 403 Forbidden');
            require_once(DOCUMENT_ROOT . '/crawler.html');
            exit;
        }
    }

    protected function _get_form() {
        $form = new FW_Ajax_Form('register_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Text', 'user_name', true)
            ->setMinLength(6);
        $form->addField('Email', 'email', true);
        $this->_add_create_password_fields($form);
        return $form;
    }

    function view() {
        $this->_logout();
        $form = $this->_get_form();
        require_once (DOCUMENT_ROOT . '/views/user/register.view.html');
    }

    function save() {
        $form = $this->_get_form();
        $form->resolveRequest();
        if ($this->_validate_create_password_form($form)) {
            $user_obj = new Model_User($this->_db, 0);
            $res = $user_obj->create(
                $form->getValue('user_name'),
                $form->getValue('email'), 
                $form->getValue('password'));
            if ($res) {
                return $form->getFormSuccess(LANG_REGISTER_SUCCESS);
            } 
            return $form->getFormError(LANG_REGISTER_FAIL_EMAIL);
        } 
        return $form->getFormError(LANG_FORM_INVALID); 
    }
}