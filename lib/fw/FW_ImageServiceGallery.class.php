<?php
/*
 File: FW_ImageServiceGallery.class.php
 Copyright (c) 2014 Clemens K. (https://github.com/metacreature)
 
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

require_once ('FW_ImageServiceBase.class.php');

class FW_ImageServiceGallery extends FW_ImageServiceBase
{
    protected $_base_dirname;
    protected $_orig_subfolder_name = '_orig_';
    protected $_subfolder_names = [];
    
    protected $_logo_light_name = '';
    protected $_logo_dark_name = '';
    
    function __construct($base_dirname, $secure_key, $subfolder_names = array('_hidden_' => true)) 
    {
        $this->_base_dirname = self::trim_dir($base_dirname);

        $this->_orig_subfolder_name .= $secure_key;
        foreach($subfolder_names as $name => $hidden) {
            if ($hidden) {
                $this->_subfolder_names[$name . $secure_key] = $hidden;
            } else {
                $this->_subfolder_names[$name] = $hidden;
            }
        }
        
        $this->_create_directories($this->_base_dirname);
    }

    function getBaseDirName(){
        return $this->_base_dirname;
    }
    
    protected function _create_directories($base_dirname) 
    {
        $base_dirname = self::trim_dir($base_dirname);
        
        $tmp_base_dirname = explode('/', $base_dirname);
        $create_name = '';
        foreach ($tmp_base_dirname as $name) {
            $create_name .= '/' .$name;
            self::create_dir($create_name, false);
        }
        
        self::create_dir($base_dirname . '/' . $this->_orig_subfolder_name , true);
        
        foreach ($this->_subfolder_names as $create_name => $hidden) {
            self::create_dir($base_dirname . '/' . $create_name , $hidden);
        }
    }
    
    function move_uploaded_image($upload_file_name) 
    {
        $target_name = $this->_base_dirname . '/' . $this->_orig_subfolder_name . '/' . uidmore();
        $ret = $this->raw_move_uploaded_image($upload_file_name, $target_name);
        return self::trim_img_dir($ret);
    }
    
    function load_orig_image($orig_image_name) 
    {
        $orig_image_name = self::trim_img_dir($orig_image_name);
        return $this->raw_load_orig_image($this->_base_dirname . '/' . $this->_orig_subfolder_name . '/' . $orig_image_name);
    }
    
    protected function _create_images($image_name, $overwrite, $attach_logo = true, $image_data = null) 
    {
        $image_name = self::trim_img($image_name);
        if (!$overwrite || !preg_match('#_[0-9]+$#', $image_name)) {
            $image_name = self::get_next_image_name($image_name, $this->_base_dirname, array_keys($this->_subfolder_names));
        }
        
        if (!$this->_logo_images_loaded && $attach_logo) {
            $logo_light_name = self::trim_dir($this->_logo_light_name);
            $logo_dark_name = self::trim_dir($this->_logo_dark_name);
            $ret = $this->raw_load_logo_images($logo_light_name, $logo_dark_name);
            if ($ret !== true) {
                return $ret;
            }
        }
        
        return ['image_name' => $image_name];
    }
    
    function upload($upload_file_name, $image_name, $overwrite, $attach_logo = true, $image_data = null)
    {
        $ret_upload = $this->move_uploaded_image($upload_file_name);
        if (strpos($ret_upload, 'error#') === 0) {
            return $ret_upload;
        }
        
        $ret = $this->_create_images($image_name, $overwrite, $attach_logo, $image_data);
        if (!is_array($ret)) {
            return $ret;
        }
        
        $ret['orig_image_name'] = $ret_upload;
        return $ret;
    }
    
    function recreate($orig_image_name, $image_name, $attach_logo = true, $image_data = null)
    {
        $orig_image_name = self::trim_img_dir($orig_image_name);
        $ret_load = $this->load_orig_image($orig_image_name);
        if ($ret_load !== true) {
            return $ret_load;
        }
        
        $ret = $this->_create_images($image_name, true, $attach_logo, $image_data);
        if (!is_array($ret)) {
            return $ret;
        }
        
        $ret['orig_image_name'] = $orig_image_name;
        return $ret;
    }
    
    protected function _move_images($dirname, $target_dirname, $image_name, $target_image_name)
    {
        $dirname = self::GALLERY_ROOT . '/' . $dirname . '/';
        $target_dirname = self::GALLERY_ROOT . '/' . $target_dirname . '/';
        foreach (scandir($dirname) as $entry) {
            if (is_file($dirname . $entry) && self::trim_img($entry) == $image_name) {
                $new_name = $target_dirname . str_replace($image_name, $target_image_name, $entry);
                if (file_exists($new_name)) {
                    unlink($new_name);
                }
                rename($dirname . $entry, $new_name);
            }
        }
    }
    
    function move($target_base_dirname, $orig_image_name, $image_name, $target_image_name, $overwrite = false) 
    {
        $target_base_dirname = self::trim_dir($target_base_dirname);
        if ($target_base_dirname != $this->_base_dirname) {
            $this->_create_directories($target_base_dirname);
        }
        
        $base_dirname = $this->_base_dirname;
        
        if ($base_dirname != $target_base_dirname) {            
            $orig_image_name = self::trim_img_dir($orig_image_name);
            $source = self::GALLERY_ROOT . '/' . $base_dirname . '/' . $this->_orig_subfolder_name . '/' . $orig_image_name;
            $target = self::GALLERY_ROOT . '/' . $target_base_dirname . '/' . $this->_orig_subfolder_name . '/' . $orig_image_name;
            if (!file_exists($source) || file_exists($target)) {
                return 'error#move_orig';
            }
            rename($source, $target);
        }
        
        $image_name = self::trim_img($image_name);
        $target_image_name = self::trim_img($target_image_name);
        
        if ($target_base_dirname != $this->_base_dirname 
            || self::trim_img_num($image_name) != self::trim_img_num($target_image_name)) {
                if (!$overwrite || !preg_match('#_[0-9]+$#', $target_image_name)) {
                $target_image_name = self::get_next_image_name($target_image_name, $target_base_dirname, array_keys($this->_subfolder_names));
            }
            
            $this->_move_images($base_dirname, $target_base_dirname, $image_name, $target_image_name);
            foreach (array_keys($this->_subfolder_names) as $subfolder) {
                $this->_move_images($base_dirname . '/' . $subfolder, $target_base_dirname . '/' . $subfolder, $image_name, $target_image_name);
            }
        }
        
        return $target_image_name;
    }
    
    protected function _remove_images($dirname, $image_name)
    {
        $dirname = self::GALLERY_ROOT . '/' . $dirname . '/';
        foreach (scandir($dirname) as $entry) {
            if (is_file($dirname . $entry) && self::trim_img($entry) == $image_name) {
                unlink($dirname . $entry);
            }
        }
    }
    
    function remove($orig_image_name = null, $image_name = null) 
    {
        $base_dirname = $this->_base_dirname;
        
        if ($orig_image_name) {
            $orig_image_name = self::trim_img_dir($orig_image_name);
            $file_name = self::GALLERY_ROOT . '/' . $base_dirname . '/' . $this->_orig_subfolder_name . '/' . $orig_image_name;
            if (file_exists($file_name)) {
                unlink($file_name);
            }
        }
        if ($image_name) {
            $image_name = self::trim_img($image_name);
            if (!preg_match('#_[0-9]+$#', $image_name)) {
                return false;
            }
            $this->_remove_images($base_dirname, $image_name);
            foreach (array_keys($this->_subfolder_names) as $subfolder) {
                $this->_remove_images($base_dirname . '/' . $subfolder, $image_name);
            }
        }
        return true;
    }
}
