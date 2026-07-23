<?php
/*
 File: Field_Date.class.php
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

require_once 'Field_Date.class.php';

class Field_Time extends Field_Date
{

    protected $_sClassName = 'fieldtime form-control';

    protected $_sFormatKey = 't';
    protected $_sErrorKeyMin = 'time_min';
    protected $_sErrorKeyMax = 'time_max';
    protected $_sErrorKeyInvalid = 'time_invalid';

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->setFieldErrors(array(
            'time_min' => 'Minimum time is {MIN}',
            'time_max' => 'Maximum time is {MAX}',
            'time_invalid' => 'required format is {FORMAT}',
        ));
    }

    protected function _getDateObject($mDate)
    {
        $oDate = null;
        if (is_int($mDate)) {
            $oDate = new DateTime();
            $oDate->setTimestamp($mDate);
        } else if (is_string($mDate) && mb_trim($mDate)) {
            $oDate = FW_Date::user_to_obj(mb_trim($mDate), $this->_sFormatKey);
        }
        if ($oDate instanceof DateTime) {
            if (strpos(FW_Date::get_user_format($this->_sFormatKey), 's') === false) {
                $oDate->setTime($oDate->format('h'), $oDate->format('i'), 0);
            }
            return $oDate->setDate(1900, 1, 1);
        }
        return null;
    }
}