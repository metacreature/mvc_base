<?php

define('LANG_PAGE_TITLE', 'Metacreature\'s Reciepes');
define('LANG_NAVIGATION_HOME', 'Home');

define('LANG_FORMFIELD_ERRORS', array(
    'pattern' => 'Invalid value!',
    'external' => 'Invalid value!',
    'too_long' => 'Value is too long! (max {LENGTH}, actual {ACTUAL_LENGTH})',
    'too_short' => 'Value is too short! (min {LENGTH}, actual {ACTUAL_LENGTH})',
    'min_number' => 'Value may not be smaller than {VALUE}!',
    'max_number' => 'Value may not be bigger than {VALUE}!',
    'mandatory' => 'Value required!'
));
define('LANG_FORM_INVALID', 'Please evaluate your input!');
define('LANG_FORM_DEFAULT_ERROR', 'Something went wrong! Please try it again later!');

define('LANG_FIELD_USER_USERNAME', 'Username');
define('LANG_FIELD_USER_EMAIL', 'E-Mail');
define('LANG_FIELD_USER_PASSWORD', 'Password');
define('LANG_FIELD_USER_PASSWORD_ERROR', 'Password must contain at minimum one uppercase, lowercase, number and special character!');
define('LANG_FIELD_USER_REPEAT_PASSWORD', 'Repeat password');
define('LANG_FIELD_USER_REPEAT_PASSWORD_ERROR', 'Passwords don\'t match!');
define('LANG_FIELD_USER_ACTUAL_PASSWORD', 'Actual password');
define('LANG_GENEERATE_PASSWORD_BUTTON', 'Generate password');
define('LANG_GENEERATE_PASSWORD_POPUP_TITLE', 'Generate password');
define('LANG_GENEERATE_PASSWORD_POPUP_TEXT', 'The password was copied to your clipboard!');
define('LANG_GENEERATE_PASSWORD_POPUP_BUTTON', 'OK');

define('LANG_NAVIGATION_LOGIN', 'login');
define('LANG_NAVIGATION_LOGOUT', 'logout');
define('LANG_NAVIGATION_REGISTER', 'register');

define('LANG_REGISTER_HDL', 'Registration');
define('LANG_REGISTER_SAVE', 'register');
define('LANG_REGISTER_FAIL_EMAIL', 'E-Mail already exists!');
define('LANG_REGISTER_SUCCESS', 'Registration was successfull!');

define('LANG_PASSWORD_REQUEST_HDL', 'Forggoten password');
define('LANG_PASSWORD_REQUEST_BUTTON', 'request link');
define('LANG_PASSWORD_REQUEST_SUBJECT', 'Request new password');
define('LANG_PASSWORD_REQUEST_SUCCESS', 'The link to change the password is sent to your e-mail address!');

define('LANG_PASSWORD_CHANGE_HDL', 'Change password');
define('LANG_PASSWORD_CHANGE_SAVE', 'save');
define('LANG_PASSWORD_CHANGE_ERROR_TIME', 'The link expired! <br>Please request a new link!');
define('LANG_PASSWORD_CHANGE_SUCCESS', 'The password was changed successfully!');

define('LANG_PROFILE_SUCCESS', 'Updated succeesfully!');
define('LANG_PROFILE_SAVE', 'update');
define('LANG_PROFILE_DATA_HDL', 'Update profile');
define('LANG_PROFILE_DATA_FAIL', 'Update failed!');
define('LANG_PROFILE_EMAIL_HDL', 'Update E-Mail');
define('LANG_PROFILE_EMAIL_FAIL', 'Update failed!<br>Actual password is wrong or E-Mail allready exists!');
define('LANG_PROFILE_PASSWORD_HDL', 'Update password');
define('LANG_PROFILE_PASSWORD_FAIL', 'Update failed!<br>Actual password is wrong!');

define('CHECK_LOGIN_ERROR_NOT_LOGIN', 'You are not loged in!');

define('LANG_LOGIN_HDL', 'Login');
define('LANG_LOGIN_SAVE', 'login');
define('LANG_LOGIN_REMEMBER_LOGIN', 'stay logged in');
define('LANG_LOGIN_FORGOTTEN', 'forgotten password');
define('LANG_LOGIN_FAIL', 'Login failed!');
define('LANG_LOGIN_SUCCESS', 'Login was successful!');
define('LANG_LOGIN_BRUTE_FORCE', 'You have tried to login for  '.SETTINGS_LOGIN_BRUTEFORCE_CNT.' times. You are now blocked for '.SETTINGS_LOGIN_BRUTEFORCE_EXPIRE.' hour(s)!');
