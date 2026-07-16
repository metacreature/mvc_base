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

    function get_user_list_with_recipes() {
        $where = '';
        if ($this->_user_id) {
            $where = ' OR user_id = '. intval($this->_user_id);
        }

        $res = $this->_db->executeUnbufferedQuery('SELECT 
            tbl_user.user_id as user_id,
            tbl_user.user_name as user_name
            FROM tbl_user
            INNER JOIN tbl_recipe USING (user_id)
            WHERE tbl_recipe.public = 1 ' . $where . '
            ORDER BY tbl_user.user_name ASC;');

        $row_list = $this->_db->getAssocResults();
        $data = [];
        foreach ($row_list as $row) {
            $data[$row['user_id']] = $row['user_name'];
        }
        return $data;
    }

    protected function _crypt_password($password) {
        return hash('sha512', SECURITY_SALT . $password . SECURITY_SALT . $password);
    }

    protected function _calc_remember_token($user_token) {
        $ip = SECURITY_LOGIN_USE_IP ? $_SERVER['REMOTE_ADDR'] : '';
        $user_agent = SECURITY_LOGIN_USE_USER_AGENT ? $_SERVER['HTTP_USER_AGENT'] : '';
        return create_db_token($user_token, $_SERVER['HTTP_ACCEPT_LANGUAGE']. $user_agent . $ip);
    }

    function addRememberToken($password) {
        $user_token = create_user_token($this->_crypt_password($password),  $_SERVER['REMOTE_ADDR']);
        $db_token = $this->_calc_remember_token($user_token);

        $res = $this->_db->executePreparedQuery(
            'INSERT INTO tbl_user_remember (user_id, db_token, insert_timestamp) VALUES (?, ?, NOW());',
            [$this->_user_id, $db_token]
        );
        return $user_token;
    }

    function removeRememberToken($user_token) {
        $db_token = $this->_calc_remember_token($user_token);
        $res = $this->_db->executePreparedQuery(
            'DELETE FROM tbl_user_remember WHERE db_token = ?;',
            [$db_token]
        );
    }

    function loginRememberToken($user_token) {
        $db_token = $this->_calc_remember_token($user_token);
        $res = $this->_db->executePreparedQuery(
            'SELECT * FROM tbl_user WHERE user_id IN (SELECT user_id FROM tbl_user_remember WHERE db_token = ? AND insert_timestamp > NOW() - INTERVAL ? day);',
            [$db_token, SETTINGS_LOGIN_REMEMBER_EXPIRE]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {
                $this->setUserId($data['user_id']);
                return $data;
            }
        }
    }

    function get() {
        $res = $this->_db->executePreparedQuery(
            'SELECT * FROM tbl_user WHERE user_id = ?;',
            [$this->_user_id]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {
                return $data;
            }
        }
    }

    function login($email, $password) {

        $email = strtolower($email);

        $cnt_bruteforce = 0;
        $res = $this->_db->executePreparedQuery(
            'SELECT count(*) as cnt FROM tbl_user_login_bruteforce WHERE LOWER(email) = ? AND insert_timestamp > NOW() - INTERVAL ? MINUTE;',
            [$email, SETTINGS_LOGIN_BRUTEFORCE_EXPIRE * 60]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            $cnt_bruteforce = $data['cnt'];
            if ($cnt_bruteforce >= SETTINGS_LOGIN_BRUTEFORCE_CNT) {
                return false;
            }
        }

        $res = $this->_db->executePreparedQuery(
            'SELECT * FROM tbl_user WHERE email = ? AND password = ?;',
            [$email, $this->_crypt_password($password)]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {
                $this->setUserId($data['user_id']);
                
                $this->_db->executePreparedQuery(
                    'DELETE FROM tbl_user_login_bruteforce WHERE email = ?;',
                    [$email]);
                    
                return $data;
            }
        }

        $this->_db->executePreparedQuery(
            'INSERT INTO tbl_user_login_bruteforce SET email = ?, insert_timestamp = NOW();',
            [$email]);
        
        if ($cnt_bruteforce + 1 >= SETTINGS_LOGIN_BRUTEFORCE_CNT) {
            return false;
        }
    }

    function create($user_name, $email, $password) {
        return $this->_db->executePreparedQuery(
            'INSERT INTO tbl_user (user_name, email, password, last_edited) VALUES (?,?,?, NOW())',
            [$user_name, $email, $this->_crypt_password($password)]);
    }

    
    function forgotten($email) {

        $email = strtolower($email);

        $res = $this->_db->executePreparedQuery(
            'SELECT user_id, user_name, email, password FROM tbl_user WHERE LOWER(email) = ?;',
            [$email]);
        if ($res) {
            $data = $this->_db->fetchAssoc();
            if ($data) {

                $res = $this->_db->executePreparedQuery(
                    'SELECT count(*) as cnt FROM tbl_user_forgotten WHERE user_id = ? AND insert_timestamp > NOW() - INTERVAL ? MINUTE;',
                    [$data['user_id'], SETTINGS_FORGOTTEN_PASSWORD_EXPIRE]);
                $data2 = $this->_db->fetchAssoc();
                if ($data2['cnt'] >= 1) {
                    return false;
                }

                $user_token = create_user_token($data['password'],  $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
                $db_token = create_db_token($user_token, $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
                $res = $this->_db->executePreparedQuery(
                    'INSERT INTO tbl_user_forgotten (user_id, db_token, insert_timestamp) VALUES (?, ?, NOW());',
                    [$data['user_id'], $db_token]
                );

                return array_merge($data, ['user_token'  => $user_token]);
            }
        }
    }

    function forgotten_change($user_token, $password) {

        $db_token = create_db_token($user_token, $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        $res = $this->_db->executePreparedQuery(
            'UPDATE tbl_user SET 
                password = ?,
                last_edited = NOW(),
                cnt_update = cnt_update + 1
            WHERE user_id IN (SELECT user_id FROM tbl_user_forgotten WHERE db_token = ? AND insert_timestamp > NOW() - INTERVAL ? MINUTE);',
            [$this->_crypt_password($password), $db_token, SETTINGS_FORGOTTEN_PASSWORD_EXPIRE]);
        if ($res) {
            if ($this->_db->getAffectedRows() == 1) {

                $this->_db->executePreparedQuery(
                    'DELETE FROM tbl_user_login_bruteforce WHERE email IN
                    (SELECT LOWER(email) FROM tbl_user WHERE user_id IN (SELECT user_id FROM tbl_user_forgotten WHERE db_token = ?));',
                    [$db_token]);

                $this->_db->executePreparedQuery(
                    'DELETE FROM tbl_user_forgotten WHERE db_token = ?;',
                    [$db_token]);
                    
                return true;
            }
        }
        return false;
    }

    function update_profile($user_name) {
        $res = $this->_db->executePreparedQuery(
            'UPDATE tbl_user SET 
                user_name = ?,
                last_edited = NOW(),
                cnt_update = cnt_update + 1
            WHERE user_id = ?;',
            [$user_name, $this->_user_id]);
        if ($res) {
            if ($this->_db->getAffectedRows() == 1) {
                return true;
            }
        }
        return false;
    }

    function update_email($actual_password, $email) {
        $res = $this->_db->executePreparedQuery(
            'UPDATE tbl_user SET 
                email = ?,
                last_edited = NOW(),
                cnt_update = cnt_update + 1
            WHERE user_id = ? AND password = ?;',
            [$email, $this->_user_id, $this->_crypt_password($actual_password)]);
        if ($res) {
            if ($this->_db->getAffectedRows() == 1) {
                return true;
            }
        }
        return false;
    }

    function update_password($actual_password, $password) {
        $res = $this->_db->executePreparedQuery(
            'UPDATE tbl_user SET 
                password = ?,
                last_edited = NOW(),
                cnt_update = cnt_update + 1
            WHERE user_id = ? AND password = ?;',
            [$this->_crypt_password($password), $this->_user_id, $this->_crypt_password($actual_password)]);
        if ($res) {
            if ($this->_db->getAffectedRows() == 1) {
                return true;
            }
        }
        return false;
    }
}