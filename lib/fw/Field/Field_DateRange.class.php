<?php
/*
 File: Field_DateRange.class.php
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
require_once 'Field_Date.class.php';

class Field_DateRange extends Field_Base
{

    protected $_sClassName = 'fielddaterange form-control';

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->_arrChildFields = array();
        $this->_arrChildFields['from'] = new Field_Date($sName.'_from');
        $this->_arrChildFields['to'] = new Field_Date($sName.'_to');
        
        $this->setFieldErrors(array(
            'date_range' => 'invalid daterange'
        ));
    }

    function getMinValue()
    {
        return $this->_arrChildFields['from']->getMinValue();
    }

    function getMaxValue()
    {
        return $this->_arrChildFields['to']->getMaxValue();
    }

    function setMinValue($mMinValue)
    {
        $this->_arrChildFields['from']->setMinValue($mMinValue);
        $this->_arrChildFields['to']->setMinValue($mMinValue);
        return $this;
    }

    function setMaxValue($mMaxValue)
    {
        $this->_arrChildFields['from']->setMaxValue($mMaxValue);
        $this->_arrChildFields['to']->setMaxValue($mMaxValue);
        return $this;
    }

    function getFieldFrom() {
        return $this->_arrChildFields['from'];
    }

    function getFieldTo() {
        return $this->_arrChildFields['to'];
    }

    function setFieldErrors($arrFieldErrors)
    {
        $this->_arrFieldErrors = array_merge($this->_arrFieldErrors, $arrFieldErrors);
        $this->_arrChildFields['from']->setFieldErrors($arrFieldErrors);
        $this->_arrChildFields['to']->setFieldErrors($arrFieldErrors);
        return $this;
    }

    function setDisabled($bDisabled = true)
    {
        $this->_bDisabled = $bDisabled ? true : false;
        $this->_arrChildFields['from']->setDisabled($bDisabled);
        $this->_arrChildFields['to']->setDisabled($bDisabled);
        return $this;
    }

    function setMandatory($bMandatory = true)
    {
        $this->_bMandatory = $bMandatory ? true : false;
        $this->_arrChildFields['from']->setMandatory($bMandatory);
        $this->_arrChildFields['to']->setMandatory($bMandatory);
        return $this;
    }

    function isValid()
    {
        return $this->_bValid && $this->_arrChildFields['from']->isValid() && $this->_arrChildFields['to']->isValid();
    }

    function getError()
    {
        if ($this->isValid()) {
            return null;
        }
        $error = array();
        if ($this->_arrChildFields['from']->getError()) {
            $error[$this->_arrChildFields['from']->getName()] = $this->_arrChildFields['from']->getError();
        } else {
            $error[$this->_arrChildFields['from']->getName()] = true;
        }
        if ($this->_arrChildFields['to']->getError()) {
            $error[$this->_arrChildFields['to']->getName()] = $this->_arrChildFields['to']->getError();
        } else {
            $error[$this->_arrChildFields['to']->getName()] = true;
        }

        return $error;
    }

    function setValue($mValue)
    {
        if (! is_array($mValue) || count($mValue) != 2) {
            $this->_arrChildFields['from']->setValue(null);
            $this->_arrChildFields['to']->setValue(null);
        } else {
            $this->_arrChildFields['from']->setValue(!empty($mValue['from']) ? $mValue['from'] : $mValue[0]);
            $this->_arrChildFields['to']->setValue(!empty($mValue['to']) ? $mValue['to'] : $mValue[1]);
        }
        return $this;
    }

    function getValue()
    {
        return array(
            'from' => $this->_arrChildFields['from']->getValue(),
            'to' => $this->_arrChildFields['to']->getValue()
        );
    }

    function resolveRequest($arrRequest)
    {
        $this->_arrChildFields['from']->resolveRequest($arrRequest);
        $this->_arrChildFields['to']->resolveRequest($arrRequest);
    }

    protected function _validateRange()
    {
        $value = $this->getValue();
        if ($value['from'] && $value['to'] && $value['from'] > $value['to']) {
            $this->_arrChildFields['from']->setErrorCode('date_range');
            $this->_bValid = false;
            return false;
        }
        return true;
    }

    function validate()
    {
        $this->_bValid = $this->_arrChildFields['from']->validate() && $this->_arrChildFields['to']->validate();
        if (!$this->_bValid)
            return false;
        if (! $this->_validateRange())
            return false;
        return $this->_checkError();
    }

    function returnInput($arrAttributes = null, $bFormDisabled = false)
    {
        return '<div class="input-group">'.
                $this->_arrChildFields['from']->returnInput($arrAttributes, $bFormDisabled).
		        '<div class="input-group-text">&#8211;</div>'.
                $this->_arrChildFields['to']->returnInput($arrAttributes, $bFormDisabled).
                '</div>';
    }
}