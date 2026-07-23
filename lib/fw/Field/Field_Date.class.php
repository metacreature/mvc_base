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

require_once 'Field_Base.class.php';
require_once dirname(dirname(__FILE__)).'/FW_Date.static.php';

class Field_Date extends Field_Base
{

    protected $_sClassName = 'fielddate form-control';

    protected $_sValue;

    protected $_oMinValue = null;

    protected $_oMaxValue = null;


    protected $_sFormatKey = 'd';
    protected $_sErrorKeyMin = 'date_min';
    protected $_sErrorKeyMax = 'date_max';
    protected $_sErrorKeyInvalid = 'date_invalid';

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->setFieldErrors(array(
            'date_min' => 'Minimum Date is {MIN}',
            'date_max' => 'Maximum Date is {MAX}',
            'date_invalid' => 'required format is {FORMAT}',
            'date_config' => 'invalid config'
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
            return $oDate->setTime(0, 0, 0);
        }
        return null;
    }

    function getMinValue()
    {
        return $this->_oMinValue;
    }

    function getMaxValue()
    {
        return $this->_oMaxValue;
    }

    function setMinValue($mMinValue)
    {
        $this->_oMinValue = $this->_getDateObject($mMinValue);
        return $this;
    }

    function setMaxValue($mMaxValue)
    {
        $this->_oMaxValue = $this->_getDateObject($mMaxValue);
        return $this;
    }

    function setValue($mValue)
    {
        $this->_mValue = $this->_getDateObject($mValue);
        if (is_string($mValue)) {
            $this->_sValue = $mValue;
        } else if ($this->_mValue) {
            $this->_sValue = FW_Date::obj_to_user($this->_mValue, $this->_sFormatKey);
        } else {
            $this->_sValue = null;
        }
        return $this;
    }

    function resolveRequest($arrRequest)
    {
        if (array_key_exists($this->_sName, $arrRequest)) {
            $sDate = mb_trim($arrRequest[$this->_sName]);
            $sDate = ini_get('magic_quotes_gpc') ? stripslashes($sDate) : $sDate;
            $this->setValue($sDate);
        }
    }

    protected function _validateMandatory()
    {
        if ($this->_bMandatory && ! $this->_sValue) {
            $this->setErrorCode('mandatory');
            return false;
        }
        return true;
    }

    protected function _validateRegEx()
    {
        if ($this->_sValue && ! ($this->_mValue instanceof DateTime)) {
            $this->_bValid = false;
            if (! $this->_mError) {
                $this->_mError = str_replace('{FORMAT}', FW_Date::get_display_format($this->_sFormatKey), $this->_arrFieldErrors[$this->_sErrorKeyInvalid]);
            }
            return false;
        }
        return true;
    }

    protected function _validateConfig()
    {
        if ($this->_oMinValue && $this->_oMaxValue && $this->_oMinValue > $this->_oMaxValue) {
            $this->setErrorCode('date_config');
            return false;
        }
        return true;
    }

    protected function _validateMinValue()
    {
        if ($this->_oMinValue && $this->_mValue && $this->_mValue < $this->_oMinValue) {
            $this->_bValid = false;
            if (! $this->_mError) {
                $this->_mError = str_replace('{MIN}', FW_Date::obj_to_user($this->_oMinValue, $this->_sFormatKey), $this->_arrFieldErrors[$this->_sErrorKeyMin]);
            }
            return false;
        }
        return true;
    }

    protected function _validateMaxValue()
    {
        if ($this->_oMaxValue && $this->_mValue && $this->_mValue > $this->_oMaxValue) {
            $this->_bValid = false;
            if (! $this->_mError) {
                $this->_mError = str_replace('{MAX}', FW_Date::obj_to_user($this->_oMaxValue, $this->_sFormatKey), $this->_arrFieldErrors[$this->_sErrorKeyMax]);
            }
            return false;
        }
        return true;
    }

    function validate()
    {
        $this->_bValid = true;
        if (! $this->_validateMandatory())
            return false;
        if (! $this->_validateRegEx())
            return false;
        if (! $this->_validateConfig())
            return false;
        if (! $this->_validateMinValue())
            return false;
        if (! $this->_validateMaxValue())
            return false;
        return $this->_checkError();
    }

    protected function _getAttributes($arrAttributes, $bFormDisabled)
    {
        if (! is_array($arrAttributes)) {
            $arrAttributes = array();
        }
        $_arrAttributes = parent::_getAttributes($arrAttributes, $bFormDisabled);

        $_arrAttributes['value'] = is_object($this->_mValue) ? FW_Date::obj_to_user($this->_mValue, $this->_sFormatKey) : '';
        $_arrAttributes['placeholder'] = FW_Date::get_display_format($this->_sFormatKey);
        $_arrAttributes['data-format'] = $_arrAttributes['placeholder'];

        if ($this->_oMinValue)
            $_arrAttributes['data-min'] = FW_Date::obj_to_user($this->_oMinValue, $this->_sFormatKey);
        if ($this->_oMaxValue)
            $_arrAttributes['data-max'] = FW_Date::obj_to_user($this->_oMaxValue, $this->_sFormatKey);

        return array_merge($_arrAttributes, $arrAttributes);
    }

    function returnInput($arrAttributes = null, $bFormDisabled = false)
    {
        $arrAttributes = $this->_getAttributes($arrAttributes, $bFormDisabled);

        return '<input' . $this->_buildAttributesString($arrAttributes) . '>';
    }
}