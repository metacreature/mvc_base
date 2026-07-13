<?php
/*
 File: Field_Email.class.php
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

require_once 'Field_Text.class.php';

class Field_Number extends Field_Text
{
    protected $_sType = 'number';

    protected $_fMinValue = null;

    protected $_fMaxValue = null;
    
    function __construct($sName)
    {
        parent::__construct($sName);

        $this->setRegEx('#^[0-9]+([,.][0-9]+){0,1}$#');

        $this->setFieldErrors(array(
            'min_number' => 'minimum value: {VALUE}',
            'max_number' => 'maximum value: {VALUE}'
        ));
    }

    function setValue($sValue)
    {
        $sValue = (string) $sValue;
        $sValue = mb_trim($sValue);
        $this->_mValue = $sValue === '' ? null : str_replace(',', '.', $sValue);
        return $this;
    }

    function getMinValue()
    {
        return $this->_fMinValue;
    }

    function setMinValue($fMinValue)
    {
        $this->_fMinValue = $fMinValue;
        return $this;
    }

    function getMaxValue()
    {
        return $this->_fMaxValue;
    }

    function setMaxValue($fMaxValue)
    {
        $this->_fMaxValue = $fMaxValue;
        return $this;
    }

    protected function _validateValue() 
    {
        if ((string) $this->_mValue === '') {
            return true;
        }
        if (!is_null($this->_fMinValue) && $this->_fMinValue > (int) $this->_mValue) {
            $this->_bValid = false;
            $this->_sErrorCode = 'min_number';
            if (! $this->_sError) {
                $this->_sError = str_replace('{VALUE}', $this->_fMinValue, $this->_arrFieldErrors['min_number']);
            }
            return false;
        }
        if (!is_null($this->_fMaxValue) && $this->_fMaxValue < (int) $this->_mValue) {
            $this->_bValid = false;
            $this->_sErrorCode = 'max_number';
            if (! $this->_sError) {
                $this->_sError = str_replace('{VALUE}', $this->_fMaxValue, $this->_arrFieldErrors['max_number']);
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
        if (! $this->_validateValue())
            return false;
        return $this->_checkError();
    }
}