<?php
/*
 File: Field_Select.class.php
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

class Field_Select extends Field_Base
{

    protected $_sClassName = 'fieldselect';

    protected $_arrList;

    protected $_bMultiple = false;

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->setFieldErrors(array(
            'mandatory' => 'select required'
        ));
    }

    function getList()
    {
        return $this->_arrList;
    }

    function setList($arrList)
    {
        $this->_arrList = $arrList;
        return $this;
    }

    function getMultiple()
    {
        return $this->_bMultiple;
    }

    function setMultiple($bMultiple = true)
    {
        $this->_bMultiple = $bMultiple ? true : false;
        $this->setValue($this->_mValue);
        return $this;
    }

    function setValue($mValue)
    {
        if (is_array($mValue)) {
            if (! $this->_bMultiple) {
                $this->_mValue = (string) reset($mValue);
            } else {
                $this->_mValue = array();
                foreach ($mValue as $sValue) {
                    if ((string) $sValue !== '') {
                        $this->_mValue[] = (string) $sValue;
                    }
                }
            }
        } else if ($this->_bMultiple) {
            if ((string) $mValue === '') {
                $this->_mValue = array();
            } else {
                $this->_mValue = array(
                    (string) $mValue
                );
            }
        } else {
            $this->_mValue = (string) $mValue;
        }
        return $this;
    }

    function resolveRequest($arrRequest)
    {
        if (array_key_exists($this->_sName, $arrRequest)) {
            $this->setValue($arrRequest[$this->_sName]);
        }
    }

    protected function _validateMandatory()
    {
        if ($this->_bMandatory && ($this->_mValue === '' || (is_array($this->_mValue) && count($this->_mValue) == 0))) {
            $this->setErrorCode('mandatory');
            return false;
        }
        return true;
    }

    protected function _validateRegEx()
    {
        if (is_array($this->_mValue)) {
            if (! $this->_bMultiple) {
                $this->setErrorCode('unknown');
                return false;
            }
            if (is_array($this->_arrList)) {
                foreach ($this->_mValue as $sValue) {
                    if (mb_strpos($sValue, '__') === 0 || ! $this->_findValueRecursive($this->_arrList, $sValue)) {
                        $this->setErrorCode('pattern');
                        return false;
                    }
                }
            } else {
                foreach ($this->_mValue as $sValue) {
                    if (mb_strpos($sValue, '__') === 0 || ($this->_sRegEx && ! preg_match($this->_sRegEx, $sValue))) {
                        $this->setErrorCode('pattern');
                        return false;
                    }
                }
            }
            return true;
        } else if ($this->_mValue !== '') {
            if ($this->_bMultiple) {
                $this->setErrorCode('unknown');
                return false;
            }
            if (mb_strpos($this->_mValue, '__') === 0) {
                $this->setErrorCode('pattern');
                return false;
            }
            if (is_array($this->_arrList)) {
                if (! $this->_findValueRecursive($this->_arrList, $this->_mValue)) {
                    $this->setErrorCode('pattern');
                    return false;
                }
            } else if ($this->_sRegEx && ! preg_match($this->_sRegEx, $this->_mValue)) {
                $this->setErrorCode('pattern');
                return false;
            }
        }
        return true;
    }

    protected function _findValueRecursive($arrList, $sValue)
    {
        foreach ($arrList as $key => $value) {
            if (is_array($value)) {
                if ($this->_findValueRecursive($value, $sValue)) {
                    return true;
                }
            } else if ((string) $key === $sValue) {
                return true;
            }
        }
        return false;
    }

    function validate()
    {
        $this->_bValid = true;
        if (! $this->_validateMandatory())
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

        if ($this->_bMultiple) {
            $_arrAttributes['multiple'] = '';
            $_arrAttributes['name'] .= '[]';
        }
        if ($bFormDisabled || $this->_bDisabled) {
            $_arrAttributes['readonly'] = null;
            $_arrAttributes['disabled'] = '';
        }
        return array_merge($_arrAttributes, $arrAttributes);
    }

    function printInput($arrAttributes = null, $bFormDisabled = false)
    {
        $arrAttributes = $this->_getAttributes($arrAttributes, $bFormDisabled);

        $sReturn = '<select' . $this->_buildAttributesString($arrAttributes) . '>';
        if (is_array($this->_arrList)) {
            $sReturn .= $this->_printOtionsRecursive($this->_arrList, 0);
        }
        $sReturn .= '</select>';
        return $sReturn;
    }

    protected function _printOtionsRecursive($arrList, $iLevel)
    {
        $sReturn = '';
        foreach ($arrList as $key => $value) {
            if (is_array($value)) {
                $sReturn .= '<option value="" class="group lv' . $iLevel . '" disabled>' . xssProtect($key) . '</option>';
                $sReturn .= $this->_printOtionsRecursive($value, $iLevel + 1);
            } else {
                $sReturn .= '<option value="' . xssProtect($key) . '" class="lv' . $iLevel . '"';
                if (mb_strpos($key, '__') === 0) {
                    $sReturn .= ' disabled';
                } else if (is_array($this->_mValue)) {
                    if (in_array((string) $key, $this->_mValue)) {
                        $sReturn .= ' selected';
                    }
                } else if ((string) $key === $this->_mValue) {
                    $sReturn .= ' selected';
                }
                $sReturn .= '>' . xssProtect($value) . '</option>';
            }
        }
        return $sReturn;
    }
}