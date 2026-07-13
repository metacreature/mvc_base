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

class Field_Date extends Field_Base
{

    static $sInternalFormat = 'd.m.Y';

    static $sExternalFormat = 'dd.mm.yyyy';

    protected $_sClassName = 'fielddate';

    protected $_oMinDate = null;

    protected $_oMaxDate = null;

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->setFieldErrors(array(
            'date_min' => 'Minimum Date is {MIN_DATE}',
            'date_max' => 'Maximum Date is {MAX_DATE}',
            'date_invalid' => 'invalid date',
            'date_config' => 'invalid config'
        ));
    }

    function getInternalFormat()
    {
        return self::$sInternalFormat;
    }

    function getExternalFormat()
    {
        return self::$sExternalFormat;
    }

    function getMinDate()
    {
        return $this->_oMinDate;
    }

    function getMaxDate()
    {
        return $this->_oMaxDate;
    }

    protected function _getDateObject($mDate)
    {
        if ($mDate instanceof DateTime) {
            return $mDate->setTime(0, 0, 0);
        } else if (is_string($mDate) && mb_trim($mDate)) {
            $mDate = DateTime::createFromFormat(FORMAT_DATE, mb_trim($mDate));
            if ($mDate === false) {
                return null;
            }
            return $mDate->setTime(0, 0, 0);
        } else {
            return null;
        }
    }

    function setMinDate($mMinDate)
    {
        $this->_oMinDate = $this->_getDateObject($mMinDate);
        return $this;
    }

    function setMaxDate($mMaxDate)
    {
        $this->_oMaxDate = $this->_getDateObject($mMaxDate);
        return $this;
    }

    function setValue($mValue)
    {
        $this->_mValue = $this->_getDateObject($mValue);
        return $this;
    }

    function getValue()
    {
        return is_object($this->_mValue) ? $this->_mValue : null;
    }

    protected function _resolveValue($sDate)
    {
        $oDate = $this->_getDateObject($sDate);
        if ($oDate === false) {
            return $sDate;
        }
        return $oDate;
    }

    function resolveRequest($arrRequest)
    {
        if (array_key_exists($this->_sName, $arrRequest)) {
            $sDate = mb_trim($arrRequest[$this->_sName]);
            $sDate = ini_get('magic_quotes_gpc') ? stripslashes($sDate) : $sDate;
            $this->_mValue = $this->_resolveValue($sDate);
        }
    }

    protected function _validateMandatory()
    {
        if ($this->_bMandatory && ! $this->_mValue) {
            $this->setErrorCode('mandatory');
            return false;
        }
        return true;
    }

    protected function _validateRegEx()
    {
        if ($this->_mValue && ! ($this->_mValue instanceof DateTime)) {
            $this->setErrorCode('date_invalid');
            return false;
        }
        return true;
    }

    protected function _validateConfig()
    {
        if ($this->_oMinDate && $this->_oMaxDate && $this->_oMinDate > $this->_oMaxDate) {
            $this->setErrorCode('date_config');
            return false;
        }
        return true;
    }

    protected function _validateMinDate()
    {
        if ($this->_oMinDate && $this->_mValue && $this->_mValue < $this->_oMinDate) {
            $this->_bValid = false;
            $this->_sErrorCode = 'date_min';
            if (! $this->_sError) {
                $this->_sError = str_replace('{MIN_DATE}', $this->_oMinDate->format(self::$sInternalFormat), $this->_arrFieldErrors['date_min']);
            }
            return false;
        }
        return true;
    }

    protected function _validateMaxDate()
    {
        if ($this->_oMaxDate && $this->_mValue && $this->_mValue > $this->_oMaxDate) {
            $this->_bValid = false;
            $this->_sErrorCode = 'date_max';
            if (! $this->_sError) {
                $this->_sError = str_replace('{MAX_DATE}', $this->_oMaxDate->format(self::$sInternalFormat), $this->_arrFieldErrors['date_max']);
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
        if (! $this->_validateMinDate())
            return false;
        if (! $this->_validateMaxDate())
            return false;
        return $this->_checkError();
    }

    protected function _getAttributes($arrAttributes, $bFormDisabled)
    {
        if (! is_array($arrAttributes)) {
            $arrAttributes = array();
        }
        $_arrAttributes = parent::_getAttributes($arrAttributes, $bFormDisabled);

        if ($this->_mValue instanceof DateTime) {
            $_arrAttributes['value'] = $this->_mValue->format(self::$sInternalFormat);
        } else {
            $_arrAttributes['value'] = $this->_mValue;
        }

        return array_merge($_arrAttributes, $arrAttributes);
    }

    function printInput($arrAttributes = null, $bFormDisabled = false)
    {
        $arrAttributes = $this->_getAttributes($arrAttributes, $bFormDisabled);

        return '<input' . $this->_buildAttributesString($arrAttributes) . '>';
    }
}