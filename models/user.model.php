<?php
/*
 File: user.model.php
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


require_once (DOCUMENT_ROOT . '/lib/base.model.php');

class Model_User extends Model_Base{

    protected $_user_id = null;

    function setUserId($user_id) {
        $this->_user_id = $user_id ? (int)$user_id : 0;
        return $this; 
    }

    protected function _cryptPassword($password) {
        return hash('sha512', SECURITY_SALT . $password . SECURITY_SALT . $password);
    }

    protected function _calcRememberToken($user_token) {
        $ip = SECURITY_LOGIN_USE_IP ? $_SERVER['REMOTE_ADDR'] : '';
        $user_agent = SECURITY_LOGIN_USE_USER_AGENT ? $_SERVER['HTTP_USER_AGENT'] : '';
        return create_db_token($user_token, $_SERVER['HTTP_ACCEPT_LANGUAGE']. $user_agent . $ip);
    }

    protected function _cleanGetData(&$data) {
        FW_MySQL::cleanHelper($data, ['password']);
    }

    function get($user_id = null) {
        $res = $this->_db->executeQuery(
            'SELECT * FROM tbl_user WHERE user_id = ?;',
            [$user_id ? $user_id : $this->_user_id]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {
                $this->_cleanGetData($data);
                return $data;
            }
        }
    }

    function create($data) {
        if (empty($data['user_name'])
         || empty($data['user_email'])
         || empty($data['password'])) {
            return false;
        }

        FW_MySQL::cleanHelper($data, ['login_timestamp']);

        $data['lower_user_name'] = strtolower($data['user_name']);
        $data['lower_user_email'] = strtolower($data['user_email']);
        $data['password'] = $this->_cryptPassword($data['password']);

        return $this->_db->insertHelper('tbl_user', 'user_id', $data);
    }

    function update($data, $user_id) {
        FW_MySQL::cleanHelper($data, ['login_timestamp']);

        if (array_key_exists('user_name', $data)) {
            $data['lower_user_name'] = strtolower($data['user_name']);
        }
        if (array_key_exists('user_email', $data)) {
            $data['lower_user_email'] = strtolower($data['user_email']);
        }
        if (array_key_exists('password', $data)) {
            $data['password'] = $this->_cryptPassword($data['password']);
        }

        $res = $this->_db->updateHelper('tbl_user', 'user_id', $data, 'user_id = ?', [$user_id]);
        return $res === 1;
    }

    function updateProfile($data) {
        FW_MySQL::cleanHelper($data, [
            'password',
            'user_email',
            'lower_user_email',
            'is_admin',
            'block_login',
            'login_timestamp'
        ]);

        if (array_key_exists('user_name', $data)) {
            $data['lower_user_name'] = strtolower($data['user_name']);
        }

        $res = $this->_db->updateHelper('tbl_user', 'user_id', $data, 'user_id = ?', [$this->_user_id]);
        return $res === 1;
    }

    function updateEmail($actual_password, $user_email) {
        $data = [
            'user_email' => $user_email,
            'lower_user_email' => strtolower($user_email)
        ];

        $res = $this->_db->updateHelper('tbl_user', 'user_id', $data, 
            'user_id = ? AND password = ?', [$this->_user_id, $this->_cryptPassword($actual_password)]);
        return $res === 1;
    }

    function updatePassword($actual_password, $password) {
        $data = ['password' => $this->_cryptPassword($password)];

        $res = $this->_db->updateHelper('tbl_user', 'user_id', $data, 
            'user_id = ? AND password = ?', [$this->_user_id, $this->_cryptPassword($actual_password)]);
        return $res === 1;
    }

    function login($user_email, $password, $as_admin = false) {

        $cnt_bruteforce = 0;
        $res = $this->_db->executeQuery(
            'SELECT count(*) as cnt FROM tbl_user_login_bruteforce WHERE lower_user_email = ? AND insert_timestamp > NOW() - INTERVAL ? MINUTE;',
            [strtolower($user_email), SETTINGS_LOGIN_BRUTEFORCE_EXPIRE * 60]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            $cnt_bruteforce = $data['cnt'];
            if ($cnt_bruteforce >= SETTINGS_LOGIN_BRUTEFORCE_CNT) {
                return false;
            }
        }

        $sql_admin = $as_admin ? ' AND is_admin = 1 ' : '';
        $res = $this->_db->executeQuery(
            'SELECT * FROM tbl_user WHERE block_login = 0 AND lower_user_email = ? AND password = ? '.$sql_admin.';',
            [strtolower($user_email), $this->_cryptPassword($password)]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {
                
                $this->setUserId($data['user_id']);
                $this->_cleanGetData($data);

                if (SETTINGS_LOGIN_LOG_TIME) {
                    $this->_db->executeQuery('UPDATE tbl_user SET last_login = NOW() WHERE user_id = ?;', array($this->_user_id));
                }

                if (!$as_admin) {
                    $data['is_admin'] = false;
                } else {
                    $data['is_admin'] = $data['is_admin'] == 1;
                }
                $data['login'] = true;

                $this->_db->executeQuery(
                    'DELETE FROM tbl_user_login_bruteforce WHERE lower_user_email = ?;',
                    [strtolower($user_email)]);
                    
                return $data;
            }
        }

        $this->_db->executeQuery(
            'INSERT INTO tbl_user_login_bruteforce SET lower_user_email = ?;',
            [strtolower($user_email)]);
        
        if ($cnt_bruteforce + 1 >= SETTINGS_LOGIN_BRUTEFORCE_CNT) {
            return false;
        }
    }

    function addRememberToken($password) {
        $user_token = create_user_token($this->_cryptPassword($password),  $_SERVER['REMOTE_ADDR']);
        $db_token = $this->_calcRememberToken($user_token);

        $res = $this->_db->executeQuery(
            'INSERT INTO tbl_user_remember SET user_id = ?, db_token = ?;',
            [$this->_user_id, $db_token]
        );
        return $user_token;
    }

    function removeRememberToken($user_token) {
        $db_token = $this->_calcRememberToken($user_token);
        $res = $this->_db->executeQuery(
            'DELETE FROM tbl_user_remember WHERE db_token = ?;',
            [$db_token]
        );
    }

    function loginRememberToken($user_token) {
        $db_token = $this->_calcRememberToken($user_token);
        $res = $this->_db->executeQuery(
            'SELECT * FROM tbl_user WHERE user_id IN (SELECT user_id FROM tbl_user_remember WHERE db_token = ? AND insert_timestamp > NOW() - INTERVAL ? day);',
            [$db_token, SETTINGS_LOGIN_REMEMBER_EXPIRE]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {
                $this->setUserId($data['user_id']);
                $this->_cleanGetData($data);

                if (SETTINGS_LOGIN_LOG_TIME) {
                    $this->_db->executeQuery('UPDATE tbl_user SET last_login = NOW() WHERE user_id = ?;', array($this->_user_id));
                }

                $data['is_admin'] = false;
                $data['login'] = true;

                return $data;
            }
        }
    }
    
    function requestForgotten($user_email) {

        $res = $this->_db->executeQuery(
            'SELECT user_id, user_name, user_email, password FROM tbl_user WHERE lower_user_email = ?;',
            [strtolower($user_email)]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {

                $res = $this->_db->executeQuery(
                    'SELECT count(*) as cnt FROM tbl_user_forgotten WHERE user_id = ? AND insert_timestamp > NOW() - INTERVAL ? MINUTE;',
                    [$data['user_id'], SETTINGS_FORGOTTEN_PASSWORD_EXPIRE]);
                $data2 = $this->_db->fetchAssoc();
                if ($data2['cnt'] >= 1) {
                    return false;
                }

                $user_token = create_user_token($data['password'],  $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
                $db_token = create_db_token($user_token, $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
                $res = $this->_db->executeQuery(
                    'INSERT INTO tbl_user_forgotten SET user_id = ?, db_token = ?;',
                    [$data['user_id'], $db_token]
                );

                return array_merge($data, ['user_token'  => $user_token]);
            }
        }
    }

    function changeForgotten($user_token, $password) {

        $db_token = create_db_token($user_token, $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        $res = $this->_db->executeQuery(
            'UPDATE tbl_user SET 
                password = ?,
                update_timestamp = NOW(),
                cnt_update = cnt_update + 1
            WHERE user_id IN (SELECT user_id FROM tbl_user_forgotten WHERE db_token = ? AND insert_timestamp > NOW() - INTERVAL ? MINUTE);',
            [$this->_cryptPassword($password), $db_token, SETTINGS_FORGOTTEN_PASSWORD_EXPIRE]);
        if ($res) {
            if ($this->_db->getAffectedRows() == 1) {

                $this->_db->executeQuery(
                    'DELETE FROM tbl_user_login_bruteforce WHERE lower_user_email IN
                    (SELECT lower_user_email FROM tbl_user WHERE user_id IN (SELECT user_id FROM tbl_user_forgotten WHERE db_token = ?));',
                    [$db_token]);

                $this->_db->executeQuery(
                    'DELETE FROM tbl_user_forgotten WHERE db_token = ?;',
                    [$db_token]);
                    
                return true;
            }
        }
        return false;
    }
}