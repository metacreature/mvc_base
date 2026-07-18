<?php

define('LANG_DUMMY', 'DUMMY');

define('LANG_PAGE_TITLE', 'MVC Base');
define('LANG_NAVIGATION_HOME', 'Home');
define('LANG_NAVIGATION_MENU', 'MENÜ');
define('LANG_NAVIGATION_LOGIN', 'login');
define('LANG_NAVIGATION_LOGOUT', 'logout');
define('LANG_NAVIGATION_REGISTER', 'registrieren');
define('LANG_NAVIGATION_LANGUAGE', 'Sprache: ');
define('LANG_NAVIGATION_LAYOUT', 'Layout: ');
define('LANG_NAVIGATION_LAYOUT_DARK', 'dunkel');
define('LANG_NAVIGATION_LAYOUT_LIGHT', 'hell');
define('LANG_NAVIGATION_LAYOUT_AUTO', 'auto');

define('LANG_FORMFIELD_ERRORS', array(
    'pattern' => 'Eingabe ungültig!',
    'external' => 'Eingabe ungültig!',
    'too_long' => 'Eingabe zu lang! (max {LENGTH}, aktuell {ACTUAL_LENGTH})',
    'too_short' => 'Eingabe zu kurz! (min {LENGTH}, aktuell {ACTUAL_LENGTH})',
    'min_number' => 'Wert darf nicht kleiner als {VALUE} sein!',
    'max_number' => 'Wert darf nicht größer als {VALUE} sein!',
    'mandatory' => 'Eingabe erforderlich!'
));
define('LANG_FORM_INVALID', 'Bitte überprüfen Sie ihre Eingabe!');
define('LANG_FORM_DEFAULT_ERROR', 'Es ist ein Fehler aufgetreten! Bitte versuche es später noch einmal!');

define('LANG_CAPTCHA_INVALID', 'Das Captcha wurde nicht gelöst!');
define('LANG_CAPTCHA_VERIFY', 'Sind Sie ein Mensch?');
define('LANG_CAPTCHA_LOADING', 'Lade die Aufgabe ...');
define('LANG_CAPTCHA_CHALLANGE', 'Wählen Sie das Bild aus, das am <u>seltensten</u> gezeigt wird');
define('LANG_CAPTCHA_SUCCESS', 'Sie sind ein Mensch!');
define('LANG_CAPTCHA_ERROR_HDL', 'Falsch');
define('LANG_CAPTCHA_ERROR_TEXT', 'Sie haben das falsche Bild gewählt!');
define('LANG_CAPTCHA_TIMEOUT_HDL', 'Bitte warten ...');
define('LANG_CAPTCHA_TIMEOUT_TEXT', 'Sie hatten zu viele Fehlversuche!');

define('LANG_FIELD_USER_USERNAME', 'Benutzername');
define('LANG_FIELD_USER_EMAIL', 'E-Mail');
define('LANG_FIELD_USER_PASSWORD', 'Passwort');
define('LANG_FIELD_USER_PASSWORD_ERROR', 'Passwort muß mindestens je einen Großbuchstaben, Kleinbuchstaben, Zahl und Sonderzeichen haben!');
define('LANG_FIELD_USER_REPEAT_PASSWORD', 'Passwort nochmal');
define('LANG_FIELD_USER_REPEAT_PASSWORD_ERROR', 'Passwort nicht identisch!');
define('LANG_FIELD_USER_ACTUAL_PASSWORD', 'Aktuelles Passwort');
define('LANG_GENEERATE_PASSWORD_BUTTON', 'Passwort generieren');
define('LANG_GENEERATE_PASSWORD_POPUP_TITLE', 'Passwort generieren');
define('LANG_GENEERATE_PASSWORD_POPUP_TEXT', 'Das Passwort wurde in die Zwischenablage kopiert!');
define('LANG_GENEERATE_PASSWORD_POPUP_BUTTON', 'OK');

define('LANG_REGISTER_HDL', 'Registrieren');
define('LANG_REGISTER_SAVE', 'Registrieren');
define('LANG_REGISTER_FAIL_EMAIL', 'E-Mail schon vorhanden!');
define('LANG_REGISTER_SUCCESS', 'Registrierung erfolgreich!');

define('LANG_PASSWORD_REQUEST_HDL', 'Passwort vergessen');
define('LANG_PASSWORD_REQUEST_BUTTON', 'Link anfordern');
define('LANG_PASSWORD_REQUEST_SUBJECT', 'Neues Passwort angefordert');
define('LANG_PASSWORD_REQUEST_SUCCESS', 'Der Link zum ändern des Passworts wurde an die E-Mail-Adresse verschick!');

define('LANG_PASSWORD_CHANGE_HDL', 'Passwort ändern');
define('LANG_PASSWORD_CHANGE_SAVE', 'Speichern');
define('LANG_PASSWORD_CHANGE_ERROR_TIME', 'Der Link ist abgelaufen! <br>Bitte fordern Sie einen neuen Link an!');
define('LANG_PASSWORD_CHANGE_SUCCESS', 'Passwort wurde erfolgreich geändert!');

define('LANG_PROFILE_SUCCESS', 'Erfolgreich gespeichert!');
define('LANG_PROFILE_SAVE', 'Speichern');
define('LANG_PROFILE_DATA_HDL', 'Profildaten ändern');
define('LANG_PROFILE_DATA_FAIL', 'Speichern fehlgeschlagen!');
define('LANG_PROFILE_EMAIL_HDL', 'E-Mail ändern');
define('LANG_PROFILE_EMAIL_FAIL', 'Speichern fehlgeschlagen!<br>Aktuelles Passwort ist falsch oder E-Mail existiert bereits!');
define('LANG_PROFILE_PASSWORD_HDL', 'Passwort ändern');
define('LANG_PROFILE_PASSWORD_FAIL', 'Speichern fehlgeschlagen!<br>Aktuelles Passwort ist falsch!');

define('CHECK_LOGIN_ERROR_NOT_LOGIN', 'Sie sind nicht eingeloggt');

define('LANG_LOGIN_HDL', 'Login');
define('LANG_LOGIN_SAVE', 'Login');
define('LANG_LOGIN_REMEMBER_LOGIN', 'Eingeloggt bleiben');
define('LANG_LOGIN_FORGOTTEN', 'Passwort vergessen');
define('LANG_LOGIN_FAIL', 'Login fehlgeschlagen!');
define('LANG_LOGIN_SUCCESS', 'Login erfolgreich!');
define('LANG_LOGIN_BRUTE_FORCE', 'Sie haben '.SETTINGS_LOGIN_BRUTEFORCE_CNT.' mal vergeblich versucht sich einzuloggen. Sie sind jetzt '.SETTINGS_LOGIN_BRUTEFORCE_EXPIRE.' Stunde(n) gesperrt!');
