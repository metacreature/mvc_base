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
        $this->_forbidden(!SETTINGS_LOGIN_ENABLED || !SETTINGS_REGISTER_ENABLED);
        parent::__construct($db);
    }

    protected function _getForm() {
        $form = new FW_Ajax_Form('register_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Text', 'user_name', true)
            ->setMinLength(6);
        $form->addField('Email', 'user_email', true);
        $this->_addPasswordFields($form);
        return $form;
    }

    function view() {
        $this->_logout();
        $form = $this->_getForm();
        require_once (DOCUMENT_ROOT . '/views/user/register.view.html');
    }

    function save() {
        $form = $this->_getForm();
        $form->resolveRequest();
        if ($this->_validatePasswordFields($form)) {
            if (!$this->_validateCaptcha($_POST)) {
                return $form->getFormError(LANG_CAPTCHA_INVALID); 
            }
            $user_obj = new Model_User($this->_db);
            $res = $user_obj->create($form->getValues());
            if ($res) {
                return $form->getFormSuccess(LANG_REGISTER_SUCCESS);
            }
            $error_msg = [];
            $db_error = $this->_db->getError();
            if (is_string($db_error)) {
                if (strpos($db_error, 'lower_user_name')) {
                    $error_msg[] = LANG_USER_DUPLICATE_NAME;
                }
                if (strpos($db_error, 'lower_user_email')) {
                    $error_msg[] = LANG_USER_DUPLICATE_EMAIL;
                }
            }
            $error_msg = count($error_msg) ? implode('<br>', $error_msg) : LANG_FORM_DEFAULT_ERROR;
            return $form->getFormError($error_msg, array('captcha_reset' => true));
        } 
        return $form->getFormError(LANG_FORM_INVALID); 
    }
}