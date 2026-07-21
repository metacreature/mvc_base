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


class Controller_User_Password_Request extends Controller_Base
{
    function __construct($db_credential_key) {
        $this->_forbidden(!SETTINGS_LOGIN_ENABLED);
        parent::__construct($db_credential_key);
    }

    protected function _getForm() {
        $form = new FW_Ajax_Form('forgotten_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Email', 'user_email', true);
        return $form;
    }

    function view() {
        $this->_logout();
        $form = $this->_getForm();
        require_once (TEMPLATE_ROOT . '/user/password.request.view.html');
    }

    function submit() {
        $form = $this->_getForm();
        $form->resolveRequest();

        if (!$form->validate($form)) {
            return $form->getFormError(LANG_FORM_INVALID);
        }
        if (!$this->_validateCaptcha($_POST)) {
            return $form->getFormError(LANG_CAPTCHA_INVALID); 
        }
        
        usleep(rand(2154755, 6367810));

        $user_obj = new Model_User($this->_db);
        $data = $user_obj->requestForgotten($form->getValue('user_email'));
        if ($data) {

            $change_url = WEB_URL . '/user/password/change?token=' .$data['user_token'];         
            $user_name = $data['user_name'];

            require_once (DOCUMENT_ROOT . '/emails/password.request.email.'.SELECTED_LANG.'.html');
            $message = ob_get_contents();
            ob_clean();

            try {
                $mail = new FW_Email();
                $mail->setLanguage(SELECTED_LANG);
                $mail->From = EMAIL_FROM_MAIL;
                $mail->FromName = EMAIL_FROM_NAME;
                $mail->addAddress($data['user_email']);
                $mail->isHTML(true);
                $mail->Subject = LANG_PASSWORD_REQUEST_SUBJECT;
                $mail->Body = $message;
                $mail->AltBody = $mail->html2text($message, true);
                $mail->CharSet = "UTF-8";
                $mail->send(DEBUG_EMAILS && IS_LOCALHOST);
            } catch (Exception $e) {}
        }
        return $form->getFormSuccess(LANG_PASSWORD_REQUEST_SUCCESS);
    }
}