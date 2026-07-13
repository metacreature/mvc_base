<?php
/*
 File: Field_File.class.php
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

class Field_File extends Field_Base
{

    protected $_sClassName = 'fieldfile';

    protected $_arrExtensions = array();

    protected $_iMaxSize = 0;

    protected $_bMultiple = false;

    function __construct($sName)
    {
        parent::__construct($sName);
        $this->setFieldErrors(array(
            'file_type' => 'wrong filetype (allowed types {FILETYPES})',
            'file_size' => 'file too big (maximum filesize: {FILESIZE})',
            'file_upload' => 'PHP-Error {ERROR} occurred during upload'
        ));
        $this->_bHelper = true;
        $this->_mValue = array();
    }

    function setHelper($bHelper = true)
    {
        return $this;
    }

    function getExtensions()
    {
        return $this->_arrExtensions;
    }

    function setExtensions($arrExtensions = array())
    {
        $this->_arrExtensions = array();
        if ($arrExtensions && is_string($arrExtensions)) {
            $arrExtensionse = preg_split('#[^a-z]+#', mb_strtolower($arrExtensions));
        }
        if (is_array($arrExtensions)) {
            foreach ($arrExtensions as $sExtesion) {
                $sExtesion = preg_replace('#[^a-z]+#', '', mb_strtolower($sExtesion));
                if ($sExtesion) {
                    $this->_arrExtensions[] = $sExtesion;
                }
            }
        }
        return $this;
    }

    function getMaxSize()
    {
        return $this->_iMaxSize;
    }

    function setMaxSize($mMaxSize = '10M')
    {
        if (is_string($mMaxSize)) {
            $arrMatches = array();
            if (preg_match('#^([0-9]+(\.[0-9]+)?)([GMKB])$#', strtoupper($mMaxSize), $arrMatches)) {
                $iFaktor = 1;
                switch ($arrMatches[3]) {
                    case 'G':
                        $iFaktor *= 1024;
                    case 'M':
                        $iFaktor *= 1024;
                    case 'K':
                        $iFaktor *= 1024;
                }
                $this->_iMaxSize = (int) ($arrMatches[1] * $iFaktor);
            } else {
                $this->_iMaxSize = 0;
            }
        } else {
            $this->_iMaxSize = $mMaxSize > 0 ? (int) ($mMaxSize * 1024 * 1024) : 0;
        }
        return $this;
    }

    function getMultiple()
    {
        return $this->_bMultiple;
    }

    function setMultiple($bMultiple = true)
    {
        $this->_bMultiple = $bMultiple ? true : false;
        return $this;
    }

    function setValue($mValue)
    {
        if (is_array($mValue) && ! empty($mValue['tmp_name'])) {
            if (! is_array($mValue['tmp_name'])) {
                $this->_mValue = array(
                    $mValue
                );
            } else if (! empty($mValue['tmp_name'][0])) {
                $this->_mValue = self::_diverse_array($mValue);
            } else {
                $this->_mValue = array();
            }
        } else {
            $this->_mValue = array();
        }
        return $this;
    }

    function resolveRequest($arrRequest)
    {
        if (array_key_exists($this->_sName, $arrRequest)) {
            $this->setValue($arrRequest[$this->_sName]);
        }
    }

    protected static function _diverse_array($vector)
    {
        $result = array();
        foreach ($vector as $key1 => $value1)
            foreach ($value1 as $key2 => $value2)
                $result[$key2][$key1] = $value2;
        return $result;
    }

    protected static function _human_filesize($bytes, $decimals = 1)
    {
        $sz = array(
            'byte',
            'kB',
            'MB',
            'GB',
            'TB',
            'PB'
        );
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $sz[$factor];
    }

    protected function _validateMandatory()
    {
        if ($this->_bMandatory && ! $this->_mValue) {
            $this->setErrorCode('mandatory');
            return false;
        }
        return true;
    }

    protected function _validateUploadError()
    {
        if ($this->_mValue) {
            foreach ($this->_mValue as $arrUploadedFile) {
                if ($arrUploadedFile['error']) {
                    $this->_bValid = false;
                    $this->_sErrorCode = 'file_upload';
                    if (! $this->_sError) {
                        $this->_sError = str_replace('{ERROR}', $arrUploadedFile['error'], $this->_arrFieldErrors['file_upload']);
                    }
                    return false;
                }
            }
        }
        return true;
    }

    protected function _validateExtensions()
    {
        if ($this->_arrExtensions && $this->_mValue) {
            foreach ($this->_mValue as $arrUploadedFile) {
                $bFound = false;
                foreach ($this->_arrExtensions as $sExtension) {
                    if (preg_match('#[.]' . $sExtension . '$#i', $arrUploadedFile['name'])) {
                        $bFound = true;
                        break;
                    }
                }
                if (! $bFound) {
                    $this->_bValid = false;
                    $this->_sErrorCode = 'file_type';
                    if (! $this->_sError) {
                        $this->_sError = str_replace('{FILETYPES}', '*.' . implode(', *.', $this->_arrExtensions), $this->_arrFieldErrors['file_type']);
                    }
                    return false;
                }
            }
        }
        return true;
    }

    protected function _validateMaxSize()
    {
        if ($this->_iMaxSize && $this->_mValue) {
            foreach ($this->_mValue as $arrUploadedFile) {
                if ($arrUploadedFile['size'] > $this->_iMaxSize) {
                    $this->_bValid = false;
                    $this->_sErrorCode = 'file_size';
                    if (! $this->_sError) {
                        $this->_sError = str_replace('{FILESIZE}', self::_human_filesize($this->_iMaxSize), $this->_arrFieldErrors['file_size']);
                    }
                    return false;
                }
            }
        }
        return true;
    }

    function validate()
    {
        $this->_bValid = true;
        if (! $this->_validateMandatory())
            return false;
        if (! $this->_validateUploadError())
            return false;
        if (! $this->_validateExtensions())
            return false;
        if (! $this->_validateMaxSize())
            return false;
        return $this->_checkError();
    }

    protected function _getAttributes($arrAttributes, $bFormDisabled)
    {
        if (! is_array($arrAttributes)) {
            $arrAttributes = array();
        }
        $_arrAttributes = parent::_getAttributes($arrAttributes, $bFormDisabled);

        $_arrAttributes['type'] = 'file';
        unset($_arrAttributes['autocomplete']);

        if ($this->_arrExtensions) {
            $_arrAttributes['accept'] = '.' . implode(',.', $this->_arrExtensions);
        }

        if ($this->_bMultiple) {
            $_arrAttributes['multiple'] = 'true';
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

        return '<input' . $this->_buildAttributesString($arrAttributes) . '>';
    }
}