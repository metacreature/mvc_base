<?php
/*
 File: FW_ErrorLogger.static.php
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

define('FW_ErrorLogger_file_name', 'ErrorLogger.'.hash('sha256', SECURE_SALT).'.log.html');


class FW_ErrorLogger
{

    // ================= Init ================= //

    /**
     * bPrintCallStack
     * Defines if the callstack is printed on the client's browser
     *
     * @static
     */
    static $bPrintCallStack = true;

    /**
     * bLogInDB
     * Defines if the errormessages should be strored in the database
     * !!! Write your own queries & code @ _write
     * see _write
     *
     * @static
     */
    static $bLogInDB = false;

    /**
     * bLogInFile
     * Defines if the errormessages should be strored in the a file
     * !!! override if bLogInDB = true and the error is not logged in the db or db-logging fails
     * see _write
     *
     * @static
     */
    static $bLogInFile = true;

    /**
     * sLogFileName
     * defines the filename of the errorlog
     * !!! Use a crypted filename to avoid unauthorized access
     *
     * @static
     */
    static $sLogFileName = FW_ErrorLogger_file_name;


    // =========== Member Variables =========== //

    // =============== Methods =============== //

    /**
     * printError
     * Prepares the Callstack and prints the message to the client's browser and stores the message, depending on the store-settings
     *
     * @static
     * @param
     *            string sMeesage
     */
    static function printError($sMessage = '')
    {
        // gets and formats the callstack
        $arrRawCallStack = debug_backtrace(); // get CallStack
        $arrCleanedCallStack = self::cleanCallStack($arrRawCallStack); // shifts filename & line to show it correct
        $sPrettyPrintedCallStack = self::PrettyPrintCallStack($arrCleanedCallStack); // formats the CallStack-Array to a pretty String

        // writes the whole message to the log-file and/or database (depends on the function's content)
        self::_write($sMessage, '');

        echo "\n<div class=\"errorlogger\">\n	<b>" . date('d.m.Y - h:i:s') . "</b><br>\n	<i>" . $sMessage . "</i>\n";
        if (self::$bPrintCallStack)
            echo $sPrettyPrintedCallStack;
        echo "\n</div><div style=\"clear:both;\"></div>\n";

        // needs to exit after printing an errormessage
        exit();
    }

    /**
     * writeError
     * Prepares the callstack and stores the message, depending on the store-settings
     *
     * @static
     * @param
     *            string sMeesage
     */
    static function writeError($sMessage = '', $sFileName = null)
    {
        // gets and formats the callstack
        $arrRawCallStack = debug_backtrace(); // get CallStack
        $arrCleanedCallStack = self::cleanCallStack($arrRawCallStack); // shifts filename & line to show it correct
        $sPrettyPrintedCallStack = self::PrettyPrintCallStack($arrCleanedCallStack); // formats the CallStack-Array to a pretty String

        // writes the whole message to the log-file and/or database (depends on the function's content)
        self::_write($sMessage, $sPrettyPrintedCallStack, $sFileName);
    }

    static function printInfo($sMessage = '')
    {
        echo "\n<div class=\"errorlogger\">\n	<b>" . date('d.m.Y - h:i:s') . "</b><br>\n	<i>" . $sMessage . "</i>\n";
        echo "\n</div><div style=\"clear:both;\"></div>\n";
    }

    static function writeInfo($sMessage = '', $sFileName = null)
    {
        self::_write($sMessage, '', $sFileName);
    }

    // =========== Helper-Methods ============ //
    static function cleanCallStack($arrCallStack)
    {
        // shifts filename & line to show it correct
        $iCnt = count($arrCallStack);
        while ($iCnt) {
            $arrCallStack[$iCnt]['file'] = @$arrCallStack[$iCnt - 1]['file'];
            $arrCallStack[$iCnt]['line'] = @$arrCallStack[$iCnt - 1]['line'];
            unset($arrCallStack[$iCnt]['type']);
            $iCnt --;
        }

        // dellets the Stack entry of the error-handling function
        unset($arrCallStack[0]);

        $iCnt = count($arrCallStack);
        while ($iCnt) {
            if (empty($arrCallStack[$iCnt]['file'])) {
                unset($arrCallStack[$iCnt]);
            }
            $iCnt --;
        }

        // turn upside-down
        // $arrCallStack = array_reverse($arrCallStack);

        return $arrCallStack;
    }

    static function PrettyPrintCallStack($arrCallStack)
    {
        $nl = "\n";
        $sCallStack = '	<div style="clear:both;"></div><div class="errorlogger_callstack"><a href="#" onclick="this.nextSibling.style.display=this.nextSibling.style.display==\'none\'?\'block\':\'none\'; return false;">Call Stack >></a><div style="display:none;"><ol>';

        foreach ($arrCallStack as $arrEntry) {
            $sCallStack .= $nl . '		<li><br><ul>';
            foreach ($arrEntry as $sKey => $mValue) {
                if (is_array($mValue)) {
                    if (count($mValue)) {
                        $sCallStack .= $nl . '			<li><b>' . $sKey . ': </b><ul>';
                        foreach ($mValue as $mArg) {
                            if (is_object($mArg)) {
                                $sCallStack .= $nl . '					<li><i>{Object} </i></li>';
                            } else if (is_array($mArg)) {
                                $sCallStack .= $nl . '					<li><i>{Array} </i></li>';
                            } else {
                                $sCallStack .= $nl . '					<li><i>' . $mArg . '</i></li>';
                            }
                        }
                        $sCallStack .= '</ul></li>';
                    } else {
                        $sCallStack .= $nl . '			<li><b>' . $sKey . ': </b></li>';
                    }
                } else {
                    if (is_object($mValue)) {
                        $sCallStack .= $nl . '			<li><b>' . $sKey . ': </b><i>{Object} ' . '</i></li>';
                    } else {
                        $sCallStack .= $nl . '			<li><b>' . $sKey . ': </b><i>' . $mValue . '</i></li>';
                    }
                }
            }
            $sCallStack .= $nl . '		</ul></li>';
        }

        $sCallStack .= $nl . '	</ol></div></div>';
        return $sCallStack;
    }

    /**
     * _write
     * Stores the message including the erlier created callstack, depending on the store-settings
     * !!! Write your own SQL-queries & code here
     *
     * @static
     * @param
     *            string sMeesage
     */
    static function _write($sMessage, $sPrettyPrintedCallStack, $sFileName = null)
    {
        // force LogInfile if the sql-query fails
        $bForceLogInFile = true;

        // writes the whole message to the database
        if (self::$bLogInDB) {
            try {
                // !!!!!!!! Write your own queries & code here - Start

                if (@$db->getIsInit()) {
                    $sMessageDB = @$db->MySQL_Safety($sMessage);
                    $sCallStackDB = @$db->MySQL_Safety($sPrettyPrintedCallStack);
                    if (@$db->executeQuery('INSERT INTO tbl_error SET datee = NOW(),
					msg = ' . $sMessageDB . ', cst = ' . $sCallStackDB . ';')) {
                        if (@$db->getAffectedRows() == 1) {
                            $bForceLogInFile = false;
                        }
                    }
                }
                // !!!!!!!! Write your own queries & code here - End
            } catch (Exception $e) {
                $bForceLogInFile = true;
            }
        } else {
            $bForceLogInFile = false;
        }

        if (self::$bLogInFile || $bForceLogInFile) {
            try {
                // prepares the path of the log-file
                $sDirName = DOCUMENT_ROOT . '/_logs/';

                // prepares the message
                $sContentToWrite = "\n<div class=\"errorlogger\">\n	<b>" . date('d.m.Y - H:i:s') . "</b><br>\n	<i>" . $sMessage . "</i>\n";
                $sContentToWrite .= $sPrettyPrintedCallStack;
                $sContentToWrite .= "\n</div><div style=\"clear:both;\"></div>\n";

                // writes the whole message to the log-file
                $sFileName = ($sFileName ? $sFileName : self::$sLogFileName);
                $sFileContent = @file_get_contents($sDirName . $sFileName);
                @file_put_contents($sDirName . $sFileName, $sContentToWrite . $sFileContent);
                @chmod($sDirName . $sFileName, 0777);
                
            } catch (Exception $e) {}
        }
    }
}

/*
<style type="text/css"><!--
.errorlogger{
	font-size: 12px;
	font-family: Arial;
	float: left;
	margin: 10px 0px 0px 0px;
	padding: 5px;
	border: 1px solid #000000;
	postion: relative;
}
.errorlogger_callstack{
	font-size: 12px;
	font-family: Arial;
	width: 700px;
	float: left;
	margin: 10px 0px 0px 0px;
	padding: 5px;
	border: 1px solid #000000;
	postion: relative;
}
.errorlogger_callstack a{
	color: #000000;
	font-weight: bold;
}
.errorlogger_callstack div{
	margin: 10px 0px 0px 0px;
	padding: 5px 20px 5px 5px;
	border: 1px solid #000000;
}
--></style>
*/