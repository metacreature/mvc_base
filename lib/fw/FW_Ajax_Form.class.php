<?php
/*
 File: FW_Ajax_Form.class.php
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


class FW_Ajax_Form
{
    protected $_bFormDisabled = false;

    protected $_arrFormFields = array();

    protected $_arrFieldErrors = array();
    
    protected $_form_name;

    protected $_activate_secure;
    
    protected $_secure_time;

    protected $_col_behavior_label = 4;
    protected $_col_behavior_field = 8;
    protected $_col_behavior_break = 'md';

    function __construct($form_name, $activate_secure = false, $secure_time = 0) 
    {
        $this->_form_name = $form_name;
        $this->_activate_secure = $activate_secure;
        $this->_secure_time = $secure_time;
    }

    function addField($sType, $sFieldName, $bMandatory = false, $sDefaultValue = '', $bDisabled = false)
    {
        $sType = 'Field_' . preg_replace('#[^a-zA-Z0-9_]#', '', $sType);
        require_once ('Field/' . $sType . '.class.php');

        $oField = new $sType($sFieldName);
        $oField->setMandatory($bMandatory);
        $oField->setValue($sDefaultValue);
        $oField->setDisabled($bDisabled);
        $oField->setFieldErrors($this->_arrFieldErrors);
        $this->_arrFormFields[$sFieldName] = $oField;

        return $oField;
    }

    function setColBehavior($iLabel, $iField, $sBreak = 'md') {
        $this->_col_behavior_label = intval($iLabel);
        $this->_col_behavior_field = intval($iField);
        $this->_col_behavior_break = preg_replace('#[^a-z]#', '', $sBreak);
    }

    function setFieldErrors($arrFieldErrors, $mTypes = null)
    {
        if ($mTypes && is_string($mTypes)) {
            $mTypes = array(
                $mTypes
            );
        }
        if (! $mTypes) {
            $this->_arrFieldErrors = array_merge($this->_arrFieldErrors, $arrFieldErrors);
        }
        
        foreach ($this->_arrFormFields as $oField) {
            if (! $mTypes || in_array($oField->getType(), $mTypes)) {
                $oField->setFieldErrors($arrFieldErrors);
            }
        }
    }

    function getField($sFieldName)
    {
        if (! array_key_exists($sFieldName, $this->_arrFormFields)) {
            return null;
        }
        return $this->_arrFormFields[$sFieldName];
    }

    function getFormDisabled()
    {
        return $this->_bFormDisabled;
    }

    function setFormDisabled($bDisabled = true)
    {
        $this->_bFormDisabled = $bDisabled ? true : false;
    }

    function getValue($sFieldName)
    {
        if (! $this->getField($sFieldName)) {
            return null;
        }
        return $this->_arrFormFields[$sFieldName]->getValue();
    }

    function setValue($sFieldName, $sValue)
    {
        if (! $this->getField($sFieldName)) {
            return null;
        }
        return $this->_arrFormFields[$sFieldName]->setValue($sValue);
    }

    function getValues($bIncludeHelper = false)
    {
        $arrResult = array();
        foreach ($this->_arrFormFields as $sFieldName => $oField) {
            if (!$oField->getDisabled() && ($bIncludeHelper || ! $oField->getHelper())) {
                $arrResult[$sFieldName] = $oField->getValue();
            }
        }
        return $arrResult;
    }

    function setValues($arrValues)
    {
        if (is_array($arrValues)) {
            $arrResult = array();
            foreach ($this->_arrFormFields as $sFieldName => $oField) {
                if (array_key_exists($sFieldName, $arrValues)) {
                    $arrResult[$sFieldName] = $oField->setValue($arrValues[$sFieldName]);
                }
            }
            return $arrResult;
        }
        return null;
    }

    function resolveRequest(&$arrDBData = null)
    {
        if (is_array($arrDBData)) {
            $arrRequest = &$arrDBData;
        } else if ($_REQUEST) {
            $arrRequest = array_merge($_POST, $_GET, $_FILES);
        } else {
            $arrRequest = array();
        }

        foreach ($this->_arrFormFields as $oField) {
            $oField->resolveRequest($arrRequest);
        }
    }

    function validate()
    {
        $is_valid = true;
        
        if ($this->_activate_secure) {
            if (!isset($_SESSION['ajaxform_secure']) 
                || !isset($_POST['secure'])
                || !array_key_exists($_POST['secure'], $_SESSION['ajaxform_secure']) 
                || ($_SESSION['ajaxform_secure'][$_POST['secure']] + $this->_secure_time) > time()) {
                $is_valid = false;
            }
            
            $values = array('secure' => $_POST['secure']);
        } else {
            $values = array();
        }
        

        foreach ($this->_arrFormFields as $oField) {
            if (!$oField->getDisabled() && !$oField->validate()) {
                $is_valid = false;
            }
            if ($this->_activate_secure && !$oField->getDisabled() && !$oField instanceof Field_File) {
                $values[$oField->getName()] = (string)$oField->getValue();
            }
        }
           
        
        if ($this->_activate_secure) {
            ksort($values);
            if (!isset($_POST['secure2'])
                || $_POST['secure2'] != md5(implode('data', $values))) {
                $is_valid = false;
            }
        }
        
        
        return $is_valid;
    }
    
    function getFormSuccess($sMessage, $arr = array())
    {
        return array_merge(array('success' => true, 'message' => $sMessage), $arr);
    }
    
    function getFormWarning($sMessage, $arr = array())
    {
        return array_merge(array('warning' => true, 'message' => $sMessage), $arr);
    }
    
    function getFormError($sMessage, $arr = array())
    {
        $arrFieldErrors = array();
        foreach ($this->_arrFormFields as $sFieldName => $oField) {
            if ($oField->getError()) {
                $arrFieldErrors[$sFieldName] = $oField->getError();
            }
        }
        return array_merge(array('error' => true, 'field_errors' => $arrFieldErrors, 'message' => $sMessage), $arr);
    }

    function returnStartTag()
    {
        $sReturn = '';
        $sEncType = '';
        foreach ($this->_arrFormFields as $oField) {
            if ($oField instanceof Field_Hidden) {
                $sReturn .= '
        '.$oField->returnInput();
            } else if ($oField instanceof Field_File) {
                $sEncType = ' enctype="multipart/form-data" ';
            }
        }
        
        if($this->_activate_secure) {
            $secure = md5(uniqid(mt_rand(), true));
            if (!isset($_SESSION['ajaxform_secure'])) {
                $_SESSION['ajaxform_secure'] = array();
            }
            $_SESSION['ajaxform_secure'][$secure] = time();
        }
        
        return '

    <form method="post" ' . $sEncType . ' autocomplete="off" class="ajax-form form-horizontal break-'.$this->_col_behavior_break.
        ' ' . $this->_form_name . '" data-name="'.$this->_form_name.'">'.($this->_activate_secure ? '
        <input type="hidden" name="secure" value="' . $secure . '">' : '').
        $sReturn;
    }

    function returnMsg() {
        return '
        <div class="row form-group ajax-form-response-line justify-content-end">
            <div class="field-wrapper col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_field.'">
                <div class="ajax-form-response"></div>
            </div>
        </div>';
    }

    function returnEndTag()
    {
        return '
	</form>

';
    }


    function returnInput($sFieldName, $arrFieldAttributes = null)
    {
        $oField = $this->getField($sFieldName);
        if (!$oField) {
            return null;
        }
        return $oField->returnInput($arrFieldAttributes, $this->_bFormDisabled);
    }

    function getLineClassName($sFieldName)
    {
        $oField = $this->getField($sFieldName);
        if (!$oField) {
            return null;
        }
        $sClassName = ''; 
        
        if ($this->_bFormDisabled || $oField->getDisabled()) {
            $sClassName .= ' line-disabled';
        } else {
            if ($oField->getMandatory()) {
                $sClassName .= ' line-mandatory';
            }
        }
        return mb_trim($sClassName);
    }

    function returnLine($sFieldName, $sLabel, $arrFieldAttributes = null, $sLineClassNames = '')
    {
        $oField = $this->getField($sFieldName);
        if (!$oField || $oField->getType() == 'Hidden') {
            return null;
        } 

        $sClassName = 'field-line form-group row mb-3 justify-content-end ';
        $sClassName .= 'line-'.strtolower($oField->getType()) . ' ';
        $sClassName .= $this->getLineClassName($sFieldName);
        $sClassName .= $sLineClassNames ? ' ' . $sLineClassNames : '';

        if ($sLabel) {
            $sLabel .= $sLabel && $oField->getMandatory() ? ' *' : '';
        } else {
            $sLabel = ' ';
        }

        return '
        <div class="' . mb_trim(xssProtect($sClassName)) . '">
            <label class="col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_label.' control-label" for="' . 
            $sFieldName . '" aria-label-for="' . $sFieldName . '">' . xssProtect($sLabel) . '</label>
            <div class="field-wrapper col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_field.'">
                ' . $this->returnInput($sFieldName, $arrFieldAttributes) . '
            </div>
        </div>';
    }

    function returnCheckboxLine($sFieldName, $sLabel, $arrFieldAttributes = null, $sLineClassNames = '')
    {
        $oField = $this->getField($sFieldName);
        if (!$oField || $oField->getType() != 'Checkbox') {
            return null;
        } 

        $sClassName = 'field-line form-group row mb-3 justify-content-end ';
        $sClassName .= $this->getLineClassName($sFieldName);
        $sClassName .= $sLineClassNames ? ' ' . $sLineClassNames : '';

        $sLabel .= $sLabel && $oField->getMandatory() ? ' *' : ' ';

        return '
        <div class="' . mb_trim(xssProtect($sClassName)) . '">
            <div class="field-wrapper col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_field.'">
                <div class="form-check">
                    ' . $this->returnInput($sFieldName, $arrFieldAttributes) . '
                    <label class="form-check-label" for="' . $sFieldName . '" aria-label-for="' . $sFieldName . '">' . xssProtect($sLabel) . '</label>
                </div>
            </div>
        </div>';
    }

    function returnSubmitLine($sDisplayValue, $sClassName = null, $sEndpoint = null, $sLineClassNames = '')
    {
        if (! $this->_bFormDisabled) {
            $sLineClassName = 'button-line form-group row mb-3 justify-content-end ';
            $sLineClassName .= $sLineClassNames ? $sLineClassNames : '';

            return '
        <div class="' . mb_trim(xssProtect($sLineClassName)) . '">
            <div class="button-wrapper col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_field.'">
                ' . $this->returnSubmit($sDisplayValue, $sClassName, $sEndpoint) . '
            </div>
        </div>';
        }
        return '';
    }

    function returnSubmit($sDisplayValue, $sClassName = null, $sEndpoint = null)
    {
        return '<a href="#" '.($sEndpoint ? 'data-endpoint="' .xssProtect($sEndpoint)  . '"' : '').' class="btn btn-primary btn-ajax-submit' .
         ($sClassName ? ' ' . xssProtect($sClassName) : ''). '"><span>' . xssProtect($sDisplayValue) . '</span></a>';
    }

    function returnButtonLine($sDisplayValue, $sClassName = null, $sLineClassNames = '')
    {
        $sLineClassName = 'button-line form-group row mb-3 justify-content-end ';
        $sLineClassName .= $sLineClassNames ? $sLineClassNames : '';

        return '
        <div class="' . mb_trim(xssProtect($sLineClassName)) . '">
            <div class="button-wrapper col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_field.'">
                ' . $this->returnButton($sDisplayValue, $sClassName) . '
            </div>
        </div>';
        
    }

    function returnButton($sDisplayValue, $sClassName = null)
    {
        return '<a href="#" class="btn' . ($sClassName ? ' ' . xssProtect($sClassName) : ''). '"><span>' . xssProtect($sDisplayValue) . '</span></a>';
    }

    function returnHTMLLine($sHTML = '&nbsp;', $sLineClassNames = '', $xssProtect = true)
    {
        $sClassName = 'html-line form-group row mb-3 justify-content-end ';
        $sClassName .= $sLineClassNames ? $sLineClassNames : '';

        return '
        <div class="' . mb_trim(xssProtect($sClassName)) . '">
            <div class="col-'.$this->_col_behavior_break.'-'.$this->_col_behavior_field.'">' . ($xssProtect ? ($sHTML) : $sHTML) . '</div>
        </div>';
    }

    function outStartTag() {
        echo $this->returnStartTag();
    }

    function outMsg() {
        echo $this->returnMsg();
    }

    function outEndTag() {
        echo $this->returnEndTag();
    }

    function outInput($sFieldName, $arrFieldAttributes = null) {
        echo $this->returnInput($sFieldName, $arrFieldAttributes = null);
    }

    function outLine($sFieldName, $sLabel, $arrFieldAttributes = null, $sLineClassNames = '')
    {
        echo $this->returnLine($sFieldName, $sLabel, $arrFieldAttributes, $sLineClassNames);
    }

    function outCheckboxLine($sFieldName, $sLabel, $arrFieldAttributes = null, $sLineClassNames = '') {
        echo $this->returnCheckboxLine($sFieldName, $sLabel, $arrFieldAttributes, $sLineClassNames);
    }

    function outSubmitLine($sDisplayValue, $sClassName = null, $sEndpoint = null, $sLineClassNames = '') {
        echo $this->returnSubmitLine($sDisplayValue, $sClassName, $sEndpoint, $sLineClassNames);
    }

    function outSubmit($sDisplayValue, $sClassName = null, $sEndpoint = null) {
        echo $this->returnSubmit($sDisplayValue, $sClassName, $sEndpoint) ;
    }

    function outButtonLine($sDisplayValue, $sClassName = null, $sLineClassNames = '') {
        echo $this->returnButtonLine($sDisplayValue, $sClassName, $sLineClassNames);
    }

    function outButton($sDisplayValue, $sClassName = null) {
        echo $this->returnButton($sDisplayValue, $sClassName);
    }

    function outHTMLLine($sHTML = '&nbsp;', $sLineClassNames = '', $xssProtect = true) {
        echo $this->returnHTMLLine($sHTML, $sLineClassNames, $xssProtect);
    }
}