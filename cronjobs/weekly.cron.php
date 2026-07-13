<?php
/*
 File: weekly.cron.php
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

require_once (dirname(__FILE__).'/../lib/settings.inc.php');
require_once (DOCUMENT_ROOT.'/lib/fw/func.inc.php');
require_once (DOCUMENT_ROOT.'/lib/fw/FW_ErrorLogger.static.php');
require_once (DOCUMENT_ROOT.'/lib/fw/FW_MySQLDataBaseLayer.class.php');
require_once (DOCUMENT_ROOT.'/models/recipe.model.php');

ini_set('max_execution_time', 3600);


$db = FW_MySQLDataBaseLayer::singleton();
$recipe_obj = new Model_Recipe($db, 0);

// clean db 
$db->executeQuery('SELECT recipe_id, user_id FROM tbl_recipe WHERE deleted = 1 AND last_edited < NOW() - INTERVAL 14 day');
foreach($db->getAssocResults() as $row) {
    $recipe_obj->setUserId($row['user_id']);
    $recipe_obj->delete($row['recipe_id'], false);
}
$recipe_obj->clean_refs();

$db->executePreparedQuery('DELETE FROM tbl_user_remember WHERE insert_timestamp < NOW() - INTERVAL ? day', [SETTINGS_REMEMBER_LOGIN_EXPIRE]);
$db->executePreparedQuery('DELETE FROM tbl_user_forgotten WHERE insert_timestamp < NOW() - INTERVAL ? MINUTE', [SETTINGS_FORGOTTEN_PASSWORD_EXPIRE]);
$db->executePreparedQuery('DELETE FROM tbl_user_login_bruteforce WHERE insert_timestamp < NOW() - INTERVAL ? MINUTE', [SETTINGS_LOGIN_BRUTEFORCE_EXPIRE * 60]);

$db->optimizeTables([
    'tbl_user',
    'tbl_user_remember',
    'tbl_user_forgotten',
    'tbl_user_login_bruteforce',
    'tbl_category',
    'tbl_tag',
    'tbl_ingredients',
    'tbl_unit',
    'tbl_recipe',
    'tbl_recipe_step',
    'tbl_recipe_tag',
    'tbl_recipe_ingredients',
]);

// clean images 
$image_list = scandir(GALLERY_ROOT . '/recipes');
$orig_image_list = scandir(GALLERY_ROOT . '/recipes/_orig_' . HIDDEN_IMAGEFOLDER_SECURE);

sleep(5);

$db_list = [];
$db->executeQuery('SELECT orig_image_name, image_name FROM tbl_recipe WHERE orig_image_name != "";');
foreach($db->getAssocResults() as $row) {
    $db_list[$row['orig_image_name']] = true;
    $db_list[$row['image_name'].'_s.webp'] = true;
    $db_list[$row['image_name'].'.webp'] = true;
}

foreach($image_list as $img) {
    if (preg_match('#\.webp$#', $img) && empty($db_list[$img])) {
        unlink(GALLERY_ROOT . '/recipes/' .$img);
    }
}

foreach($orig_image_list as $img) {
    if (preg_match('#\.[a-zA-Z]{3,4}$#', $img) && empty($db_list[$img])) {
        unlink(GALLERY_ROOT . '/recipes/_orig_' . HIDDEN_IMAGEFOLDER_SECURE . '/' .$img);
    }
}
