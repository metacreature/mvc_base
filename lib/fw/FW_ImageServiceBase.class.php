<?php
/*
 File: FW_ImageServiceBase.class.php
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


class FW_ImageServiceBase
{
    const GALLERY_ROOT = GALLERY_ROOT;
    
    const RESIZE_MODE_MAX = 1;
    const RESIZE_MODE_BOX = 2;
    const RESIZE_MODE_STRETCH = 3;
    const RESIZE_MODE_COVER = 4;

    static $available_image_types = array(
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    );
 
    protected $_orig_image_width = null;
    protected $_orig_image_height = null;
    protected $_orig_image = null;

    protected $_logo_light_width = null;
    protected $_logo_light_height = null;
    protected $_logo_light = null;
    
    protected $_logo_dark_width = null;
    protected $_logo_dark_height = null;
    protected $_logo_dark = null;
    
    protected $_logo_images_loaded = false;

    function __destruct()
    {
        $this->destruct();
    }

    function destruct()
    {
        $this->_destroy_orig_image();
        $this->_destroy_logo_images();
    }
    
    protected function _destroy_orig_image() 
    {
        if ($this->_orig_image) {
            imagedestroy($this->_orig_image);
        }
        $this->_orig_image_width = null;
        $this->_orig_image_height = null;
        $this->_orig_image = null;
    }
    
    protected function _destroy_logo_images()
    {
        if ($this->_logo_light) {
            imagedestroy($this->_logo_light);
        }
        $this->_logo_light_width = null;
        $this->_logo_light_height = null;
        $this->_logo_light = null;
        
        if ($this->_logo_dark) {
            imagedestroy($this->_logo_dark);
        }
        $this->_logo_dark_width = null;
        $this->_logo_dark_height = null;
        $this->_logo_dark = null;
        
        $this->_logo_images_loaded = false;
    }

    function raw_move_uploaded_image($upload_file_name, $target_name)
    {
        $this->_destroy_orig_image();
        
        if (! is_uploaded_file($upload_file_name)) {
            return 'error#is_uploaded_file';
        }

        $target_name = '/' . self::trim_dir($target_name);
        $target_name = self::trim_img_ext($target_name);
        
        
        if (! move_uploaded_file($upload_file_name, self::GALLERY_ROOT . $target_name)) {
            return 'error#upload_move';
        }

        $image_info = getimagesize(self::GALLERY_ROOT . $target_name);
        if (! $image_info || ! array_key_exists($image_info['mime'], self::$available_image_types)) {
            unlink(self::GALLERY_ROOT . $target_name);
            return 'error#upload_invalid_filetype';
        }
        
        $orig_image_name  = $target_name . '.' . self::$available_image_types[$image_info['mime']];
        
        if (file_exists(self::GALLERY_ROOT . $orig_image_name)) {
            unlink(self::GALLERY_ROOT . $orig_image_name);
        }
        if (! rename(self::GALLERY_ROOT . $target_name, self::GALLERY_ROOT . $orig_image_name)) {
            unlink(self::GALLERY_ROOT . $target_name);
            return 'error#upload_rename';
        }
        chmod(self::GALLERY_ROOT . $orig_image_name, 0777);

        $res = $this->load_orig_image($orig_image_name);
        if (strpos($res, 'error#') === 0) {
            unlink(self::GALLERY_ROOT . $orig_image_name);
            return $res;
        }
        
        return $orig_image_name;
    }
    
    protected function _load_image($image_name) {
        
        $image_name = '/'. self::trim_dir($image_name);
        
        if (!file_exists(self::GALLERY_ROOT . $image_name) || !is_file(self::GALLERY_ROOT . $image_name)) {
            return 'file_not_found';
        }
        
        $image_info = getimagesize(self::GALLERY_ROOT . $image_name);
        if (! $image_info || ! array_key_exists($image_info['mime'], self::$available_image_types)) {
            return invalid_filetype;
        }
        switch (self::$available_image_types[$image_info['mime']]) {
            case 'jpg':
                $image = @imagecreatefromjpeg(self::GALLERY_ROOT . $image_name);
                break;
            case 'png':
                $image = @imagecreatefrompng(self::GALLERY_ROOT . $image_name);
                break;
            case 'webp':
                $image = @imagecreatefromwebp(self::GALLERY_ROOT . $image_name);
        }
        if (!$image) {
            return 'load_image';
        }
        return array(
            'resource' => $image, 
            'width' => $image_info[0],
            'height' => $image_info[1]
            );
    }

    function raw_load_orig_image($orig_image_name)
    {
        $this->_destroy_orig_image();
        
        $res = $this->_load_image($orig_image_name);
        if (is_string($res)) {
            return 'error#origimage_'.$res;
        }
        
        $this->_orig_image_width = $res['width'];
        $this->_orig_image_height = $res['height'];
        $this->_orig_image = $res['resource'];
        
        return true;
    }
    
    
    function raw_load_logo_images($logo_light_name, $logo_dark_name)
    {
        $this->_destroy_logo_images();
        
        if ($logo_light_name) {
            $res = $this->_load_image($logo_light_name);
            if (is_string($res)) {
                return 'error#logolight_'.$res;
            }
            $this->_logo_light_width = $res['width'];
            $this->_logo_light_height = $res['height'];
            $this->_logo_light = $res['resource'];
        }
        if ($logo_dark_name) {
            $res = $this->_load_image($logo_dark_name);
            if (is_string($res)) {
                return 'error#logodark_'.$res;
            }
            $this->_logo_dark_width = $res['width'];
            $this->_logo_dark_height = $res['height'];
            $this->_logo_dark = $res['resource'];
        }
        
        $this->_logo_images_loaded = true;
        return true;
    }
    
    protected function _calc_avg_color($logo_width, $logo_height, $target_width, $target_height, $target_image) {
        $avg_color = 0;
        $devider = $logo_width * $logo_height;
        $cnt_x = $logo_width;
        while ($cnt_x) {
            $cnt_y = $logo_height;
            while ($cnt_y) {
                $avg_color += imagecolorat($target_image, $target_width - $cnt_x, $target_height - $cnt_y) / $devider;
                $cnt_y --;
            }
            $cnt_x --;
        }
        return $avg_color;
    }
    
    function raw_create_image($image_name, $file_type, $quality = null, $max_landscape = null, $max_portrait = null, $max_square = null, $attach_logo = true, $resize_mode = self::RESIZE_MODE_MAX, $fill_color = null)
    {
        if (!$this->_orig_image) {
            return 'error#create_orig_image_not_loaded';
        }
        if ($attach_logo && !$this->_logo_images_loaded) {
            return 'error#create_logos_not_loaded';
        }
        if (!in_array($file_type, self::$available_image_types)) {
            return 'error#create_invalid_file_type';
        }
        
        $image_name = self::trim_img_ext($image_name);
        $image_name = self::trim_dir($image_name);
        
        $target_image = null;
        
        $target_width = $this->_orig_image_width;
        $target_height = $this->_orig_image_height;
        
        $orig_width = $this->_orig_image_width;
        $orig_height = $this->_orig_image_height;
        
        if ($max_landscape || $max_portrait) {
            
            if ($max_landscape && !$max_portrait) {
                $max_portrait = $max_landscape;
            } else if (!$max_landscape && $max_portrait) {
                $max_landscape = $max_portrait;
            }
            if (!$max_square) {
                $max_square = $max_landscape;
            }
            
            $max_landscape = array_combine(array('width','height'), $max_landscape);
            $max_portrait = array_combine(array('width','height'), $max_portrait);
            $max_square = array_combine(array('width','height'), $max_square);
            
            if (round($orig_width / $orig_height * 10) / 10 == 1) {
                $max_size = $max_square;
            } else if ($orig_width > $orig_height) {
                $max_size = $max_landscape;
            } else {
                $max_size = $max_portrait;
            }
            
            $max_width = $max_size['width'];
            $max_height = $max_size['height'];
            
            $offset_x = 0;
            $offset_y = 0;
            $resize = false;
            
            if ($resize_mode == self::RESIZE_MODE_MAX || $resize_mode == self::RESIZE_MODE_BOX) 
            {
                if (($orig_width > $max_width || $orig_height > $max_height) || ($resize_mode == self::RESIZE_MODE_BOX && ($orig_width != $max_width || $orig_height != $max_height))) {
                    $resize = true;
                    if (($max_width / $max_height) < ($orig_width / $orig_height)) {
                        $target_width = $max_width;
                        $target_height = ceil($max_width * ($orig_height / $orig_width));
                        $offset_y = ceil(($max_height - $target_height) / 2);
                    } else {
                        $target_width = ceil($max_height * ($orig_width / $orig_height));
                        $target_height = $max_height;
                        $offset_x = ceil(($max_width - $target_width) / 2);
                    }
                } else if (!is_null($fill_color)) {
                    $resize = true;
                    $offset_y = ceil(($max_height - $target_height) / 2);
                    $offset_x = ceil(($max_width - $target_width) / 2);
                }
            } 
            
            else if ($resize_mode == self::RESIZE_MODE_STRETCH) {
                if ($orig_width != $max_width || $orig_height != $max_height) {
                    $resize = true;
                    $target_width = $max_width;
                    $target_height = $max_height;
                }
            } 
            
            else if ($resize_mode == self::RESIZE_MODE_COVER) 
            {
                $target_width = $max_width;
                $target_height = $max_height;
                
                if (($max_width / $max_height) < ($orig_width / $orig_height)) {
                    $width = ceil($orig_height / $target_height * $target_width);
                    $offset_x = ceil(($orig_width - $width) / 2);
                    $orig_width = $width;
                } else {
                    $height = ceil($orig_width / $target_width * $target_height);
                    $offset_y = ceil(($orig_height - $height) / 2);
                    $orig_height = $height;
                }
                
                $target_image = @imagecreatetruecolor($target_width, $target_height);
                if ($target_image === false) {
                    return 'error#create_imagecreatetruecolor_failed';
                }
                if (!is_null($fill_color)) {
                    @imagefill($target_image, 0, 0, $fill_color);
                }
                @imagefill($target_image, 0, 0, $fill_color);
                @imagecopyresampled($target_image, $this->_orig_image, 0, 0, $offset_x, $offset_y, $target_width, $target_height, $orig_width, $orig_height);
            }
            
            if ($resize) {
                if (!is_null($fill_color) && ($offset_x || $offset_y)) {
                    $target_image = @imagecreatetruecolor($max_width, $max_height);
                    if ($target_image === false) {
                        return 'error#create_imagecreatetruecolor_failed';
                    }
                    if (!is_null($fill_color)) {
                        @imagefill($target_image, 0, 0, $fill_color);
                    }
                    @imagecopyresampled($target_image, $this->_orig_image, $offset_x, $offset_y, 0, 0, $target_width, $target_height, $orig_width, $orig_height);
                    
                    $target_width = $max_width;
                    $target_height = $max_height;
                    
                } else {
                    $target_image = @imagecreatetruecolor($target_width, $target_height);
                    if ($target_image === false) {
                        return 'error#create_imagecreatetruecolor_failed';
                    }
                    @imagecopyresampled($target_image, $this->_orig_image, 0, 0, 0, 0, $target_width, $target_height, $orig_width, $orig_height);
                }
            }
        }
        
        if ($attach_logo && ($this->_logo_light || $this->_logo_dark)) {
            
            $logo_light_available = false;
            $logo_dark_available = false;
            if ($this->_logo_light && $target_width >= $this->_logo_light_width && $target_height >= $this->_logo_light_height) {
                $logo_light_available = true;
            }
            if ($this->_logo_dark && $target_width >= $this->_logo_dark_width && $target_height >= $this->_logo_dark_height) {
                $logo_dark_available = true;
            }
            
            if ($logo_light_available || $logo_dark_available) {
                
                if (! $target_image) {
                    $target_image = @imagecreatetruecolor($target_width, $target_height);
                    if ($target_image === false) {
                        return 'error#create_imagecreatetruecolor_failed';
                    }
                    @imagecopyresampled($target_image, $this->_orig_image, 0, 0, 0, 0, $target_width, $target_height, $orig_width, $orig_height);
                }
                
                $logo_used = false;
                
                if ($logo_light_available) {
                    $avg_color = $this->_calc_avg_color($this->_logo_dark_width, $this->_logo_dark_height, $target_width, $target_height, $target_image);
                    if ($avg_color <= 0x888888) {
                        $res = @imagecopyresampled($target_image, $this->_logo_light, $target_width - $this->_logo_light_width, $target_height - $this->_logo_light_height, 0, 0, $this->_logo_light_width, $this->_logo_light_height, $this->_logo_light_width, $this->_logo_light_height);
                        if (! $res) {
                            imagedestroy($target_image);
                            return 'error#create_logo_copy_failed';
                        }
                        $logo_used = true;
                    }
                }
                if ($logo_dark_available && ! $logo_used) {
                    $avg_color = $this->_calc_avg_color($this->_logo_dark_width, $this->_logo_dark_height, $target_width, $target_height, $target_image);
                    if ($avg_color >= 0x888888) {
                        $res = @imagecopyresampled($target_image, $this->_logo_dark, $target_width - $this->_logo_dark_width, $target_height - $this->_logo_dark_height, 0, 0, $this->_logo_dark_width, $this->_logo_dark_height, $this->_logo_dark_width, $this->_logo_dark_height);
                        if (! $res) {
                            imagedestroy($target_image);
                            return 'error#create_logo_copy_failed';
                        }
                        $logo_used = true;
                    }
                }
                if (($logo_light_available || $logo_dark_available) && ! $logo_used) {
                    imagedestroy($target_image);
                    return 'error#create_logo_failed';
                }
            }
        }
        
        $image_name .= '.' . $file_type;
      
        switch ($file_type) {
            case 'jpg':
                $res_save = @imagejpeg(($target_image ? $target_image : $this->_orig_image), self::GALLERY_ROOT . '/' .$image_name, $quality ?? 95);
                break;
            case 'png':
                $res_save = @imagepng(($target_image ? $target_image : $this->_orig_image), self::GALLERY_ROOT . '/' .$image_name, $quality ?? 0);
                break;
            case 'webp':
                $res_save = @imagewebp(($target_image ? $target_image : $this->_orig_image), self::GALLERY_ROOT . '/' .$image_name, $quality ?? 90);
                break;
        }
        
        if ($target_image) {
            imagedestroy($target_image);
        }
        if (empty($res_save)) {
            return 'error#create_failed';
        }
        chmod(self::GALLERY_ROOT . '/' .$image_name, 0777);
        
        return array('image_name' => $image_name, 'width' => $target_width, 'height' => $target_height);
    }  
    
    static function trim_dir($dirname) {
        return trim($dirname, '/');
    }
    
    static function trim_img_dir($image_name) {
        $image_name = explode('/', $image_name);
        return end($image_name);
    }
    
    static function trim_img_ext($image_name) {
        return preg_replace('#\.[a-z]{3,4}$#i', '', $image_name);
    }
    
    static function trim_img_num($image_name) {
        return preg_replace('#_[0-9]+$#', '', $image_name);
    }
    
    static function trim_img_prefix($image_name) {
        return preg_replace('#^[a-z]_#', '', $image_name);
    }
    
    static function trim_img($image_name, $trim_num = false) {
        $image_name = self::trim_img_dir($image_name);
        $image_name = self::trim_img_ext($image_name);
        $image_name = self::trim_img_prefix($image_name);
        if ($trim_num) {
            $image_name = self::trim_img_num($image_name);
        }
        return $image_name;
    }
    
    static function create_dir($dirname, $hidden, $overwrite_htaccess = true)
    {
        $dirname = self::GALLERY_ROOT . '/' . self::trim_dir($dirname);
        
        if (!file_exists($dirname) || !is_dir($dirname)) {
            if (!mkdir($dirname, 0777, true)) {
                return 'error#mkdir';
            }
        }
        if ($overwrite_htaccess || !file_exists($dirname . '/.htaccess')) {
            $shtaccess = $hidden ? "order allow,deny\ndeny from all" : "Options -Indexes";
            if (!file_put_contents($dirname . '/.htaccess', $shtaccess)) {
                return 'error#htaccess';
            }
            chmod($dirname . '/.htaccess', 0777);
        }
        return true;
    }
    
    protected static function _get_image_name_list($dirname, $subfolder_names = null)
    {
        $name_list = array();
        foreach (scandir($dirname) as $entry) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            if (is_dir($dirname . '/' . $entry)) {
                if (!$subfolder_names || in_array($entry, $subfolder_names)) {
                    $name_list = array_merge($name_list, self::_get_image_name_list($dirname . '/' . $entry));
                }
            } else {
                $entry = self::trim_img($entry);
                $name_list[$entry] = $entry;
            }
        }
        return $name_list;
    }

    static function get_next_image_name($image_name, $dirname, $subfolder_names = null)
    {
        $image_name = self::trim_img($image_name, true);
        $image_name .= '_';
        
        $dirname = self::trim_dir($dirname);
        $dirname = self::GALLERY_ROOT . '/' . $dirname;

        $max_nr = 0;
        $name_list_tmp = self::_get_image_name_list($dirname, $subfolder_names);
        
        foreach ($name_list_tmp as $entry) {
            if (strpos($entry, $image_name) === 0) {
                $entry_number = str_replace($image_name, '', $entry);
                if (is_numeric($entry_number)) {
                    $entry_number = (int)$entry_number;
                    $max_nr = $entry_number > $max_nr ? $entry_number : $max_nr;
                }
            }
        }
        
        return $image_name.($max_nr+1);
    }

    protected static function _exif_make_tag($rec, $data, $value)
    {
        $length = strlen($value);
        $retval = chr(0x1C) . chr($rec) . chr($data);

        if ($length < 0x8000) {
            $retval .= chr($length >> 8) . chr($length & 0xFF);
        } else {
            $retval .= chr(0x80) . chr(0x04) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        return $retval . $value;
    }

    static function raw_add_exif($file_name, $credit)
    {
        $file_name = self::trim_dir($file_name);
        $file_name = self::GALLERY_ROOT . '/' . $file_name;
        
        if (!preg_match('#\.jpg$#', $file_name) || !file_exists($file_name)) {
            return;
        }
        
        $iptc = array(
            '2#80' => $credit ? $credit : EXIF_AUTHOR,
            '2#116' => EXIF_NAME,
            '2#120' => EXIF_URL
        );

        // Convert the IPTC tags into binary code
        $data = '';
        foreach ($iptc as $tag => $string) {
            $tag = substr($tag, 2);
            $data .= self::_exif_make_tag(2, $tag, $string);
        }

        // Embed the IPTC data
        $content = iptcembed($data, $file_name);

        $fp = fopen($file_name, "wb");
        fwrite($fp, $content);
        fclose($fp);
    }
}

