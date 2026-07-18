<?php
/*
 File: FW_MySqlDataBaseLayer.class.php
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

require_once ('FW_ErrorLogger.static.php');
require_once ('FW_Date.static.php');

define('FW_MySqlDataBaseLayer_file_name', 'db_queries.log.html');


class FW_MySqlDataBaseLayer
{

    static $sLogFileName = FW_MySqlDataBaseLayer_file_name;

    // =========== Member Variables =========== //
    protected $_bIsInit = false;
    
    protected $_bLockedTables = false;

    protected $_rMySqli;

    protected $_bDebugMode;

    protected $_arrResource;

    protected $_arrError;

    protected $_arrLog;
    
    protected static $_singleton = array();
    
    static function singleton($host, $user, $password, $dbname, $persistent = false, $timezone = null, $bDebugMode = false, $connection = 'db') 
    {
        if (!array_key_exists($connection, self::$_singleton)) {
            self::$_singleton[$connection] = new self($host, $user, $password, $dbname, $persistent, $timezone, $bDebugMode);
        }
        return self::$_singleton[$connection];
    }

    static function getsingleton($connection = 'db') 
    {
        if (array_key_exists($connection, self::$_singleton)) {
            return self::$_singleton[$connection];
        }
    }

    // ======== Constructor/Destructor ========= //
    function __construct($host, $user, $password, $dbname, $persistent = false, $timezone = null, $bDebugMode = false)
    {        
        $this->_bDebugMode = $bDebugMode;
        $this->_arrResource = array();
        $this->_arrError = array();
        $this->_arrLog = array();

        $persistent = $persistent ? 'p:' : '';

        $this->_rMySqli = new MySqli($persistent . $host, $user, $password, $dbname);

        if ($this->_rMySqli->connect_errno) {
            die('<div style="color:red; padding:20px;">Could not connect to database #1!</div>');
        }

        $this->_rMySqli->set_charset('utf8');
        if ($timezone) {
            $this->_rMySqli->query('SET time_zone = ' . $this->escape($timezone));
        }

        $this->_bIsInit = true;
    }

    function __destruct()
    {
        if ($this->_bIsInit) {
            if ($this->_bLockedTables)
                $this->unlockTables();
            if ($this->_bDebugMode)
                $this->writeLog();
        }
        $this->_bIsInit = false;
    }


    // ================ Getter ================ //
    function getIsInit()
    {
        return $this->_bIsInit;
    }

    // ========== Connection-Methods ========== //
    function close()
    {
        $this->_bIsInit = false;
        $this->_rMySqli->close();
    }

    // ============= Helper =================== //

    function prepareArrayToSet($arrAssocData)
    {
        $sql = array();
        foreach ($arrAssocData as $sFieldName => $mValue) {
            if (preg_match('#^[a-z0-9_`]*$#i', $sFieldName)) {
                $sql[] = $sFieldName . ' = ?';
            } else {
                throw new Exception('prepareArrayToSet -> "'. $sFieldName . '" is not a valid field name!');
            }
        }
        return implode(', ', $sql);
    }

    function prepareArrayToIn($arrData)
    {
        return implode(', ', array_fill(0, count($arrData), '?'));
    }


    // =========== Security-Methods =========== //
    
    function escape($mValue)
    {
        return '\'' . $this->_rMySqli->real_escape_string((string) $mValue) . '\'';
    }

    function escapeArray($arrData)
    {
        foreach ($arrData as $sKey => $mValue) {
            $arrData[$sKey] = $this->escape($mValue);
        }
        return $arrData;
    }

    function escapeArrayToSet($arrAssocData)
    {
        foreach ($arrAssocData as $sFieldName => $mValue) {
            if (preg_match('#^[a-z0-9_`]*$#i', $sFieldName)) {
                $arrAssocData[$sFieldName] = $sFieldName . ' = ' . $this->escape($mValue);
            } else {
                throw new Exception('escapeArrayToSet -> "'. $sFieldName . '" is not a valid field name!');
            }
        }
        return implode(', ', $arrAssocData);
    }

    function escapeArrayToIn($arrData)
    {
        return implode(', ', $this->escapeArray($arrData));
    }

    // ============ Query-Methods ============ //

    function executeQuery($sSQL, $data = null, $iRn = 0)
    {   
        try{
            if (is_null($data)) {
                $rRes = @$this->_rMySqli->query($sSQL, MySqlI_STORE_RESULT);
            }  else {
                $rRes = @$this->_rMySqli->execute_query($sSQL, $data);
            }
            if ($rRes !== false) {
                $this->_arrResource[$iRn] = $rRes instanceof MySqli_result ? $rRes : null;
                $this->_arrError[$iRn] = null;
                $this->_arrLog[] = $iRn . ' ' . $sSQL;
                return true;
            }
        } catch (Exception $e) {}

        $this->_arrError[$iRn] = $this->_rMySqli->error;
        $sError = '<b>' . $iRn . ' ' . $sSQL . '
		<br>(ERROR)' . $this->_arrError[$iRn] . '</b>';
        $this->_arrLog[] = $sError;

        return false;
    }

    function executeUnbufferedQuery($sSQL, $iRn = 0)
    {
        try {
            $rRes = @$this->_rMySqli->query($sSQL, MySqlI_USE_RESULT);

            if ($rRes !== false) {
                $this->_arrResource[$iRn] = $rRes instanceof MySqli_result ? $rRes : null;
                $this->_arrError[$iRn] = null;
                $this->_arrLog[] = $iRn . ' ' . $sSQL;
                return true;
            }
        } catch (Exception $e) {}

        $this->_arrError[$iRn] = $this->_rMySqli->error;
        $sError = '<b>' . $iRn . ' ' . $sSQL . '
		<br>(ERROR)' . $this->_arrError[$iRn] . '</b>';
        $this->_arrLog[] = $sError;
        
        return false;
    }

    // ============ Result-Methods ============ //
    
    function getError($iRn = 0)
    {
        return $this->_arrError[$iRn];
    }


    function fetchAssoc($iRn = 0)
    {
        return $this->_arrResource[$iRn]->fetch_assoc();
    }

    function fetchRow($iRn = 0)
    {
        return $this->_arrResource[$iRn]->fetch_row();
    }

    function getAssocResults($iRn = 0)
    {
        $rRes = &$this->_arrResource[$iRn];
        $arrResult = array();
        while ($arrRow = $rRes->fetch_assoc()) {
            $arrResult[] = $arrRow;
        }
        $this->freeResult($iRn);
        return $arrResult;
    }

    function getRowResults($iRn = 0)
    {
        $rRes = &$this->_arrResource[$iRn];
        $arrResult = array();
        while ($arrRow = $rRes->fetch_row()) {
            $arrResult[] = $arrRow;
        }
        $this->freeResult($iRn);
        return $arrResult;
    }

    function getFormatedResults($mKeyFormat, $mValueFormat = null, $iRn = 0)
    {
        $rRes = &$this->_arrResource[$iRn];
        $arrResult = array();
        if (is_numeric($mKeyFormat) && is_null($mValueFormat)) {
            while ($arrRow = $rRes->fetch_row()) {
                $arrResult[$arrRow[$mKeyFormat]] = $arrRow;
            }
        } else if (! is_null($mKeyFormat) && is_null($mValueFormat)) {
            while ($arrRow = $rRes->fetch_assoc()) {
                $arrResult[$arrRow[$mKeyFormat]] = $arrRow;
            }
        } else if (is_numeric($mKeyFormat) && is_numeric($mValueFormat)) {
            while ($arrRow = $rRes->fetch_row()) {
                $arrResult[$arrRow[$mKeyFormat]] = $arrRow[$mValueFormat];
            }
        } else if (is_null($mKeyFormat) && is_numeric($mValueFormat)) {
            while ($arrRow = $rRes->fetch_row()) {
                $arrResult[] = $arrRow[$mValueFormat];
            }
        } else if (! is_null($mKeyFormat) && ! is_null($mValueFormat)) {
            while ($arrRow = $rRes->fetch_assoc()) {
                $arrResult[$arrRow[$mKeyFormat]] = $arrRow[$mValueFormat];
            }
        } else if (is_null($mKeyFormat) && ! is_null($mValueFormat)) {
            while ($arrRow = $rRes->fetch_assoc()) {
                $arrResult[] = $arrRow[$mValueFormat];
            }
        }
        $this->freeResult($iRn);
        return $arrResult;
    }

    function getMultiResults($mKeyFormat, $iRn = 0)
    {
        $rRes = &$this->_arrResource[$iRn];
        $arrResult = array();
        if (is_numeric($mKeyFormat)) {
            while ($arrRow = $rRes->fetch_row()) {
                $arrResult[$arrRow[$mKeyFormat]][] = $arrRow;
            }
        } else {
            while ($arrRow = $rRes->fetch_assoc()) {
                $arrResult[$arrRow[$mKeyFormat]][] = $arrRow;
            }
        }
        $this->freeResult($iRn);
        return $arrResult;
    }

    function getAffectedRows()
    {
        return $this->_rMySqli->affected_rows;
    }

    function getLastInsertId()
    {
        $rRes = @$this->_rMySqli->query('SELECT LAST_INSERT_ID()');
        if ($rRes) {
            $id = $rRes->fetch_row();
            $rRes->free_result();
            return $id[0];
        }
        return 0;
    }

    function getNumRows($iRn = 0)
    {
        return $this->_arrResource[$iRn]->num_rows;
    }

    function freeResult($iRn = 0)
    {
        $this->_arrResource[$iRn]->free_result();
    }

    function getResource($iRn = 0)
    {
        return $this->_arrResource[$iRn];
    }

    // ========== Transaction-Methods ========= //
    function begin()
    {
        $this->_rMySqli->begin_transaction();
        $this->_arrLog[] = 'BEGIN';
    }

    function commit()
    {
        $mRes = $this->_rMySqli->commit();
        $this->_arrLog[] = 'COMMIT';
        return $mRes ? true : false;
    }

    function rollback()
    {
        $this->_rMySqli->rollback();
        $this->_arrLog[] = 'ROLLBACK';
    }

    function lockTables($mTables)
    {
        if (is_array($mTables))
            $mTables = implode(' WRITE, ', $mTables);
        $this->_bLockedTables = true;
        $this->executeQuery('LOCK TABLES ' . $mTables . ' WRITE', null, 1846464);
    }

    function unlockTables()
    {
        $this->executeQuery('UNLOCK TABLES', null, 1846464);
        $this->_bLockedTables = false;
    }

    function optimizeTables($mTables)
    {
        if (is_string($mTables)) {
            $mTables = array(
                $mTables
            );
        }
        $this->executeQuery('OPTIMIZE TABLE ' . implode(', ', $mTables), null, 185674);
    }

    // ============== Info-Methods =========== //
    function showDatabases()
    {
        $arrDatabases = array();
        $this->executeQuery('SHOW DATABASES', null, 1846464);
        $rRes = &$this->_arrResource[1846464];
        while ($arrRow = $rRes->fetch_assoc()) {
            $arrDatabases[] = $arrRow['Database'];
        }
        return $arrDatabases;
    }

    function showTables($sDbName = '')
    {
        $arrTables = array();
        if ($sDbName)
            $this->executeQuery('SHOW TABLES FROM ' . $sDbName, null, 1846464);
        else
            $this->executeQuery('SHOW TABLES', null, 1846464);
        $rRes = &$this->_arrResource[1846464];
        while ($arrRow = $rRes->fetch_assoc()) {
            $arrTables[] = $arrRow;
        }
        return $arrTables;
    }

    function showColumns($sTableName, $bShowFullInfo = true)
    {
        $arrFields = array();
        if ($sTableName) {
            $this->executeQuery('SHOW COLUMNS FROM ' . $sTableName, null, 1846464);
            $rRes = &$this->_arrResource[1846464];
            while ($arrRow = $rRes->fetch_assoc()) {
                $arrFields[] = $arrRow;
            }
        }
        if ($bShowFullInfo)
            return $arrFields;

        $arrReturn = array();
        foreach ($arrFields as $arrField) {
            $arrReturn[$arrField['Field']] = $arrField['Field'];
        }
        return $arrReturn;
    }

    // ============ Logging-Methods =========== //
    function getLog()
    {
        return $this->_arrLog;
    }

    function printLog()
    {
        if (count($this->_arrLog))
            echo 'SQL Log: ' . count($this->_arrLog) . " Abfragen<br>\n<ul><li>" . implode("</li>\n<li>", $this->_arrLog) . '</li></ul>';
        else
            echo 'SQL Log: 0 Abfragen';
    }

    function writeLog()
    {
        if ($this->_bIsInit) {
            if (count($this->_arrLog)) {
                FW_ErrorLogger::writeInfo('SQL Log: ' . count($this->_arrLog) . " Abfragen<br>\n<ul><li>" . implode("</li>\n<li>", $this->_arrLog) . '</li></ul>', FW_MySqlDataBaseLayer::$sLogFileName);
            } else {
                FW_ErrorLogger::writeInfo('SQL Log: 0 Abfragen', FW_MySqlDataBaseLayer::$sLogFileName);
            }
        }
    }
}
