<?php
/*
 File: password.cont.php
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
require_once (DOCUMENT_ROOT . '/lib/fw/FW_Email.class.php');


class Controller_User_Password_Change extends Controller_Base
{
    function __construct($db_credential_key) {
        $this->_forbidden(!SETTINGS_LOGIN_ENABLED || empty($_REQUEST['token']));
        parent::__construct($db_credential_key);
    }

    protected function _getForm() {
        $form = new FW_Ajax_Form('password_change_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Hidden', 'token', true);
        $this->_addPasswordFields($form);
        return $form;
    }
 
    function view() {
        $this->_logout();
        $form = $this->_getForm();
        $form->resolveRequest();
        require_once (DOCUMENT_ROOT . '/views/user/password.change.view.html');
    }

    function submit() {
        $form = $this->_getForm();
        $form->resolveRequest();

        if (!$this->_validatePasswordFields($form)) {
            return $form->getFormError(LANG_FORM_INVALID);
        }
        if (!$this->_validateCaptcha($_POST)) {
            return $form->getFormError(LANG_CAPTCHA_INVALID); 
        }

        usleep(rand(2154755, 6367810));

        $user_obj = new Model_User($this->_db);
        $res = $user_obj->changeForgotten($form->getValue('token'), $form->getValue('password'));
        if($res) {
            return $form->getFormSuccess(LANG_PASSWORD_CHANGE_SUCCESS);
        }
        return $form->getFormError(LANG_PASSWORD_CHANGE_ERROR_TIME);
    }

}