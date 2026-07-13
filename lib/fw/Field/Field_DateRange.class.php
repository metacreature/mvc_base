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

require_once 'Field_Date.class.php';

class Field_DateRange extends Field_Date
{

    protected $_sClassName = 'fielddaterange';

    protected $_mValueFrom = null;

    protected $_mValueTo = null;

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->setFieldErrors(array(
            'date_range' => 'invalid daterange'
        ));
        $this->_mValue = null;
    }

    function setValue($mValue)
    {
        if (! is_array($mValue) || count($mValue) != 2) {
            $this->_mValueFrom = null;
            $this->_mValueTo = null;
        } else {
            $this->_mValueFrom = $this->_getDateObject(reset($mValue));
            $this->_mValueTo = $this->_getDateObject(end($mValue));
        }
        return $this;
    }

    function getValue()
    {
        return array(
            'from' => $this->_mValueFrom,
            'to' => $this->_mValueTo
        );
    }

    function resolveRequest($arrRequest)
    {
        if (array_key_exists($this->_sName, $arrRequest)) {
            $arrDate = $arrRequest[$this->_sName];
            if (! is_array($arrDate) || count($arrDate) != 2) {
                $this->_mValueFrom = null;
                $this->_mValueTo = null;
            } else {
                $sDate = mb_trim($arrDate['from']);
                $sDate = ini_get('magic_quotes_gpc') ? stripslashes($sDate) : $sDate;
                $this->_mValueFrom = $this->_resolveValue($sDate);

                $sDate = mb_trim($arrDate['to']);
                $sDate = ini_get('magic_quotes_gpc') ? stripslashes($sDate) : $sDate;
                $this->_mValueTo = $this->_resolveValue($sDate);
            }
        }
    }

    protected function _validateMandatory()
    {
        if ($this->_bMandatory && (! $this->_mValueFrom || ! $this->_mValueTo)) {
            $this->setErrorCode('mandatory');
            return false;
        }
        return true;
    }

    protected function _validateRegEx()
    {
        if (($this->_mValueFrom && ! ($this->_mValueFrom instanceof DateTime)) || ($this->_mValueTo && ! ($this->_mValueTo instanceof DateTime))) {
            $this->setErrorCode('date_invalid');
            return false;
        }
        return true;
    }

    protected function _validateRange()
    {
        if ($this->_mValueFrom && $this->_mValueTo && $this->_mValueFrom > $this->_mValueTo) {
            $this->setErrorCode('date_range');
            return false;
        }
        return true;
    }

    protected function _validateMinDate()
    {
        if ($this->_oMinDate && (($this->_mValueFrom && $this->_mValueFrom < $this->_oMinDate) || ($this->_mValueTo && $this->_mValueTo < $this->_oMinDate))) {
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
        if ($this->_oMaxDate && (($this->_mValueFrom && $this->_mValueFrom > $this->_oMaxDate) || ($this->_mValueTo && $this->_mValueTo > $this->_oMaxDate))) {
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
        if (! $this->_validateRange())
            return false;
        if (! $this->_validateMinDate())
            return false;
        if (! $this->_validateMaxDate())
            return false;
        return $this->_checkError();
    }

    function printInput($arrAttributes = null, $bFormDisabled = false)
    {
        $arrAttributesFrom = $this->_getAttributes($arrAttributes, $bFormDisabled);
        $arrAttributesFrom['name'] .= '[from]';
        $arrAttributesFrom['id'] .= '_from';
        if ($this->_mValueFrom instanceof DateTime) {
            $arrAttributesFrom['value'] = $this->_mValueFrom->format(self::$sInternalFormat);
        } else {
            $arrAttributesFrom['value'] = $this->_mValueFrom;
        }

        $arrAttributesTo = $this->_getAttributes($arrAttributes, $bFormDisabled);
        $arrAttributesTo['name'] .= '[to]';
        $arrAttributesTo['id'] .= '_to';
        if ($this->_mValueTo instanceof DateTime) {
            $arrAttributesTo['value'] = $this->_mValueTo->format(self::$sInternalFormat);
        } else {
            $arrAttributesTo['value'] = $this->_mValueTo;
        }

        $ret = '<input' . $this->_buildAttributesString($arrAttributesFrom) . '>
					<span class="input-group-addon daterange-middle">&#8211;</span>
					<input' . $this->_buildAttributesString($arrAttributesTo) . '>';
        
        $ret .= '<script>
    		$("#'.$arrAttributesFrom['id'].', #'.$arrAttributesTo['id'].'").datepicker({
    			format: "' . $this->getExternalFormat() . '",
    			' . ($this->getMinDate() ? 'startDate: "' . $this->getMinDate()->format($this->getInternalFormat()) . '",' : '') . '
    			' . ($this->getMaxDate() ? 'endDate: "' . $this->getMaxDate()->format($this->getInternalFormat()) . '",' : '') . '
    			clearBtn: true,
    			autoclose: true,
    			maxViewMode: 2,
    			language: "de"
    		});
    		</script>';
        
        return $ret;
    }
}