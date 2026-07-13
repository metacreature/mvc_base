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

require_once(DOCUMENT_ROOT . '/lib/external_libs/phpmailer/src/Exception.php');
require_once(DOCUMENT_ROOT . '/lib/external_libs/phpmailer/src/PHPMailer.php');

class Controller_User_Password extends Controller_Base
{
    function __construct($db) {
        parent::__construct($db);
    }

    protected function _get_forgotten_form() {
        $form = new FW_Ajax_Form('forgotten_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Email', 'email', true);
        return $form;
    }

    protected function _get_change_form() {
        $form = new FW_Ajax_Form('password_change_form', false);
        $form->setFieldErrors(LANG_FORMFIELD_ERRORS);
        $form->addField('Hidden', 'token', true);
        $this->_add_create_password_fields($form);
        return $form;
    }
 
    function change() {
        $form = $this->_get_change_form();
        $form->resolveRequest();
        require_once (DOCUMENT_ROOT . '/views/user/password.change.view.html');
    }

    function change_submit() {
        $form = $this->_get_change_form();
        $form->resolveRequest();

        if (!$this->_validate_create_password_form($form)) {
            return $form->getFormError(LANG_FORM_INVALID);
        }

        sleep(rand(1, 5));
        usleep(rand(0, 900000));

        $user_obj = new Model_User($this->_db, 0);
        $res = $user_obj->forgotten_change($form->getValue('token'), $form->getValue('password'));
        if($res) {
            return $form->getFormSuccess(LANG_PASSWORD_CHANGE_SUCCESS);
        }
        return $form->getFormError(LANG_PASSWORD_CHANGE_ERROR_TIME);
    }


    function request() {
        $this->_logout();
        $form = $this->_get_forgotten_form();
        require_once (DOCUMENT_ROOT . '/views/user/password.request.view.html');
    }

    function request_submit() {
        $form = $this->_get_forgotten_form();
        $form->resolveRequest();

        sleep(rand(1, 5));
        usleep(rand(0, 900000));

        if ($form->validate($form)) {
            $user_obj = new Model_User($this->_db, 0);
            $data = $user_obj->forgotten($form->getValue('email'));
            if ($data) {

                $change_url = WEB_URL . '/user/password/change?token=' .$data['user_token'];         
                $user_name = $data['user_name'];

                require_once (DOCUMENT_ROOT . '/emails/password.request.email.'.SELECTED_LANG.'.html');
                $message = ob_get_contents();
                ob_clean();

                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer;
                    $mail->setLanguage(SELECTED_LANG);
                    $mail->From = EMAIL_FROM_MAIL;
                    $mail->FromName = EMAIL_FROM_NAME;
                    $mail->addAddress($data['email']);
                    $mail->isHTML(true);
                    $mail->Subject = LANG_PASSWORD_REQUEST_SUBJECT;
                    $mail->Body = $message;
                    $mail->AltBody = $mail->html2text($message, true);
                    $mail->CharSet = "UTF-8";
                    $mail->send();
                } catch (Exception $e) {}
            }
        }
        return $form->getFormSuccess(LANG_PASSWORD_REQUEST_SUCCESS);
        
    }
}