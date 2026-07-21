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

class Controller_Captcha extends Controller_Base
{
    function __construct($db_credential_key) {
        $this->_forbidden(!SECURITY_ENABLE_CAPTCHA);
        parent::__construct($db_credential_key);
    }

    function view() {
        try {
            $options = require DOCUMENT_ROOT . '/lib/captcha.ini.php';

            $captcha = new \IconCaptcha\IconCaptcha($options);
            $captcha->handleCors();
            $captcha->request()->process();

            http_response_code(400);
        } catch (Throwable $exception) {
            http_response_code(500);
        }
        
        exit;
    }
}