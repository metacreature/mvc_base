<?php
/*
 File: Field_Base.class.php
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


abstract class Field_Base
{

    protected $_sName;

    protected $_sLabel;

    protected $_sClassName;

    protected $_mValue = '';

    protected $_bHelper = true;

    protected $_bMandatory = false;

    protected $_bDisabled = false;

    protected $_bValid = true;

    protected $_sError = '';

    protected $_sErrorCode = '';

    protected $_sRegEx = '';
    
    protected $_iMinLength = null;

    protected $_iMaxLength = null;

    protected $_arrParams = array();

    protected $_arrFieldErrors = array(
        'mandatory' => 'input is required',
        'pattern' => 'invalid input',
        'unknown' => 'unknown Error',
        'too_long' => 'input too long <br>(max {LENGTH}, actual {ACTUAL_LENGTH})',
        'too_short' => 'input too short <br>(min {LENGTH}), actual {ACTUAL_LENGTH})'
    );

    function __construct($sName)
    {
        $this->_sName = $sName;
    }

    function getType()
    {
        $sType = explode('_', get_class($this));
        return $sType[1];
    }

    function setFieldErrors($arrFieldErrors)
    {
        $this->_arrFieldErrors = array_merge($this->_arrFieldErrors, $arrFieldErrors);
        return $this;
    }

    function getClassName()
    {
        return $this->_sClassName;
    }

    function setClassName($sClassName)
    {
        $this->_sClassName = $sClassName;
        return $this;
    }

    function getHelper()
    {
        return $this->_bHelper;
    }

    function setHelper($bHelper = true)
    {
        $this->_bHelper = $bHelper ? true : false;
        return $this;
    }

    function getDisabled()
    {
        return $this->_bDisabled;
    }

    function setDisabled($bDisabled = true)
    {
        $this->_bDisabled = $bDisabled ? true : false;
        return $this;
    }

    function getMandatory()
    {
        return $this->_bMandatory;
    }

    function setMandatory($bMandatory = true)
    {
        $this->_bMandatory = $bMandatory ? true : false;
        return $this;
    }

    function getParam($sParam)
    {
        if (! array_key_exists($sParam, $this->_arrParams)) {
            return null;
        }
        return $this->_arrParams[$sParam];
    }

    function setParam($sParam, $mValue)
    {
        $this->_arrParams[$sParam] = $mValue;
        return $this;
    }

    function getRegEx()
    {
        return $this->_sRegEx;
    }

    function setRegEx($sRegEx)
    {
        $this->_sRegEx = $sRegEx;
        return $this;
    }
    
    function getMinLength()
    {
        return $this->_iMinLength;
    }

    function setMinLength($iMinLength)
    {
        $this->_iMinLength = $iMinLength;
        return $this;
    }

    function getMaxLength()
    {
        return $this->_iMaxLength;
    }

    function setMaxLength($iMaxLength)
    {
        $this->_iMaxLength = $iMaxLength;
        return $this;
    }
    
    function getName()
    {
        return $this->_sName;
    }

    function getValue()
    {
        return $this->_mValue;
    }

    function setValue($sValue)
    {
        $sValue = (string) $sValue;
        $sValue = mb_trim($sValue);
        $this->_mValue = $sValue === '' ? null : $sValue;
        return $this;
    }

    function getLabel()
    {
        return ! is_null($this->_sLabel) ? $this->_sLabel : $this->_sName;
    }

    function setLabel($sLabel)
    {
        $this->_sLabel = $sLabel;
        return $this;
    }

    function isValid()
    {
        return $this->_bValid;
    }

    function getError()
    {
        return $this->_sError;
    }

    function getErrorCode()
    {
        return $this->_sErrorCode;
    }

    function setErrorCode($sErrorCode)
    {
        if (!$this->_sError) {
            $this->_sErrorCode = $sErrorCode;
            $this->_bValid = false;
            $this->_sError = ! empty($this->_arrFieldErrors[$sErrorCode]) ? $this->_arrFieldErrors[$sErrorCode] : $this->_arrFieldErrors['unknown'];
        }
    }

    function resolveRequest($arrRequest)
    {
        if (array_key_exists($this->_sName, $arrRequest)) {
            $mValue = mb_trim($arrRequest[$this->_sName]);
            $this->setValue(ini_get('magic_quotes_gpc') ? stripslashes($mValue) : $mValue);
        }
    }

    protected function _getRealName($bFormDisabled)
    {
        if ($bFormDisabled || $this->_bDisabled) {
            return '_disabled_' . $this->_sName;
        }
        return $this->_sName;
    }

    protected function _getAttributes($arrAttributes, $bFormDisabled)
    {
        if (! is_array($arrAttributes)) {
            $arrAttributes = array();
        }

        $sClassNames = '';
        if (!empty($arrAttributes['class'])) {
            $sClassNames = ' ' .$arrAttributes['class'];
            unset($arrAttributes['class']);
        }

        $_arrAttributes = array();
        $_arrAttributes['id'] = $this->_sName;
        $_arrAttributes['autocomplete'] = 'off';
        $_arrAttributes['class'] = $this->_sClassName . $sClassNames;

        if ($bFormDisabled || $this->_bDisabled) {
            $_arrAttributes['name'] = '_disabled_' . $this->_sName;
            $_arrAttributes['readonly'] = '';
        } else {
            $_arrAttributes['name'] = $this->_sName;
            if ($this->_bMandatory) {
                // $_arrAttributes['required'] = '';
            }
        }

        return array_merge($_arrAttributes, $arrAttributes);
    }

    protected function _buildAttributesString($arrAttributes)
    {
        $sReturn = '';
        foreach ($arrAttributes as $sAttr => $sVal) {
            if (is_null($sVal) || strpos($sAttr, '__') === 0) {
                continue;
            }
            if ($sVal === '' && $sAttr != 'value') {
                $sReturn .= ' ' . $sAttr;
            } else {
                $sReturn .= ' ' . $sAttr . '="' . xssProtect($sVal) . '"';
            }
        }
        return $sReturn;
    }

    protected function _validateMandatory()
    {
        if ($this->_bMandatory && (string) $this->_mValue === '') {
            $this->setErrorCode('mandatory');
            return false;
        }
        return true;
    }

    protected function _validateRegEx()
    {
        if ($this->_sRegEx && (string) $this->_mValue !== '' && ! preg_match($this->_sRegEx, (string) $this->_mValue)) {
            $this->setErrorCode('pattern');
            return false;
        }
        return true;
    }

    protected function _validateLength()
    {
        if ((string) $this->_mValue === '') {
            return true;
        }
        if ($this->_iMinLength && $this->_iMinLength > mb_strlen($this->_mValue)) {
            $this->_bValid = false;
            $this->_sErrorCode = 'too_short';
            if (! $this->_sError) {
                $this->_sError = str_replace(array(
                    '{LENGTH}',
                    '{ACTUAL_LENGTH}'
                ), array(
                    $this->_iMinLength,
                    mb_strlen($this->_mValue)
                ), $this->_arrFieldErrors['too_short']);
            }
            return false;
        }
        if ($this->_iMaxLength && $this->_iMaxLength < mb_strlen($this->_mValue)) {
            $this->_bValid = false;
            $this->_sErrorCode = 'too_long';
            if (! $this->_sError) {
                $this->_sError = str_replace(array(
                    '{LENGTH}',
                    '{ACTUAL_LENGTH}'
                ), array(
                    $this->_iMaxLength,
                    mb_strlen($this->_mValue)
                ), $this->_arrFieldErrors['too_long']);
            }
            return false;
        }
        return true;
    }

    protected function _checkError()
    {
        if ($this->_sError || $this->_sErrorCode) {
            $this->_bValid = false;
        }
        return $this->_bValid;
    }

    abstract function validate();

    abstract function printInput($arrAttributes, $bFormDisabled);
}