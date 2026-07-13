<?php
/*
 File: Field_Text.class.php
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

class Field_Text extends Field_Base
{

    protected $_sType = 'text';

    protected $_sClassName = 'fieldtext';

    protected $_sPlaceholder;

    function __construct($sName)
    {
        parent::__construct($sName);
    }

    function getPlaceholder()
    {
        return ! is_null($this->_sPlaceholder) ? $this->_sPlaceholder : '';
    }

    function setPlaceHolder($sPlaceholder)
    {
        $this->_sPlaceholder = $sPlaceholder;
        return $this;
    }

    function validate()
    {
        $this->_bValid = true;
        if (! $this->_validateMandatory())
            return false;
        if (! $this->_validateLength())
            return false;
        if (! $this->_validateRegEx())
            return false;
        return $this->_checkError();
    }

    protected function _getAttributes($arrAttributes, $bFormDisabled)
    {
        if (! is_array($arrAttributes)) {
            $arrAttributes = array();
        }
        $_arrAttributes = parent::_getAttributes($arrAttributes, $bFormDisabled);

        $_arrAttributes['type'] = $this->_sType;
        $_arrAttributes['value'] = $this->_mValue;

        if ($this->getPlaceholder()) {
            $_arrAttributes['placeholder'] = $this->getPlaceholder();
        }
        if ($this->_iMaxLength) {
            $_arrAttributes['maxlength'] = $this->_iMaxLength;
        }
        
        return array_merge($_arrAttributes, $arrAttributes);
    }

    function printInput($arrAttributes = null, $bFormDisabled = false)
    {
        $arrAttributes = $this->_getAttributes($arrAttributes, $bFormDisabled);

        return '<input' . $this->_buildAttributesString($arrAttributes) . '>';
    }
}