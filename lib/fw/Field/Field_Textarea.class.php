<?php
/*
 File: Field_Textarea.class.php
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

require_once 'Field_Text.class.php';

class Field_Textarea extends Field_Text
{

    protected $_sClassName = 'fieldtextarea';

    protected function _getAttributes($arrAttributes, $bFormDisabled)
    {
        if (! is_array($arrAttributes)) {
            $arrAttributes = array();
        }
        $_arrAttributes = parent::_getAttributes($arrAttributes, $bFormDisabled);

        unset($_arrAttributes['type']);
        unset($_arrAttributes['value']);
        unset($_arrAttributes['placeholder']);

        return array_merge($_arrAttributes, $arrAttributes);
    }

    function printInput($arrAttributes = null, $bFormDisabled = false)
    {
        $arrAttributes = $this->_getAttributes($arrAttributes, $bFormDisabled);

        return '<textarea' . $this->_buildAttributesString($arrAttributes) . '>' . xssProtect($this->_mValue) . '</textarea>';
    }
}