<?php
/*
 File: FW_MySQL.class.php
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

define('FW_MySQL_file_name', 'db_queries.log.html');


class FW_MySQL
{

    static $sLogFileName = FW_MySQL_file_name;

    // =========== singleton =========== //

    protected static $_singleton = array();

    protected static $_credentials;

    protected static $_bDebugMode = false;

    protected static $_sTimezone;

    static function setCredentials($credentials) {
        self::$_credentials = $credentials;
    }

    static function setDebugMode($bDebugMode) {
        self::$_bDebugMode = $bDebugMode;
    }

    static function setTimezone($sTimezone) {
        self::$_sTimezone = $sTimezone;
    }
    
    static function singleton($db_credential_key) 
    {
        if (!array_key_exists($db_credential_key, self::$_singleton)) {
            self::$_singleton[$db_credential_key] = new self(self::$_credentials[$db_credential_key]);
        }
        return self::$_singleton[$db_credential_key];
    }

    // =========== Member Variables =========== //

    protected $_connectionCrededentials;

    protected $_isConnected;
    
    protected $_hasLockedTables;

    protected $_rMySqli;

    protected $_arrResource;

    protected $_arrError;

    protected $_arrLog;

    // ======== Constructor/Destructor ========= //

    function __construct($connectionCrededentials)
    {    
        $this->_connectionCrededentials = $connectionCrededentials;
        $this->_isConnected = false;
    }

    function __destruct()
    {
        $this->close();
    }

    function isPersistent() {
        return $this->_connectionCrededentials['persistent'];
    }

    // ========== Connection-Methods ========== //

    function connect()
    {
        if ($this->_isConnected) {
            return;
        }

        $this->_arrResource = array();
        $this->_arrError = array();
        $this->_arrLog = array();
        $this->_hasLockedTables = false;

        $con = $this->_connectionCrededentials;

        $persistent = $con['persistent'] ? 'p:' : '';
        $this->_rMySqli = new MySqli($persistent . $con['host'], $con['username'], $con['password'], $con['dbname']);

        if ($this->_rMySqli->connect_errno) {
            die('<div style="color:red; padding:20px;">Could not connect to database #1!</div>');
        }

        $this->_rMySqli->set_charset('utf8');
        if (self::$_sTimezone) {
            $this->_rMySqli->query('SET time_zone = ' . $this->escape(self::$_sTimezone));
        }

        $this->_isConnected = true;
    }

    function close()
    {
        if ($this->_isConnected) {
            if ($this->_hasLockedTables)
                $this->unlockTables();
            if (self::$_bDebugMode)
                $this->writeLog();
            $this->_rMySqli->close();
            $this->_rMySqli = null;
        }
        $this->_hasLockedTables = false;
        $this->_isConnected = false;
    }

    // ============= Helper =================== //

    static function cleanHelper(&$data, $clean_columns) {
        foreach($clean_columns as $col) {
            unset($data[$col]);
        }
    }

    static function cleanBasePutData(&$data, $primary_key) {
        self::cleanHelper($data, [
            $primary_key,
            'insert_timestamp',
            'insert_key',
            'update_timestamp',
            'cnt_update',
        ]);
    }

    static function prepareArrayInsert($arrAssocData)
    {
        $sql = array();
        foreach ($arrAssocData as $sFieldName => $mValue) {
            if (preg_match('#^[a-z0-9_`]*$#i', $sFieldName)) {
                $sql[] = $sFieldName;
            } else {
                throw new Exception('FW_MySQL::prepareArrayInsert -> "'. $sFieldName . '" is not a valid field name!');
            }
        }
        return implode(', ', $sql);
    }

    static function prepareAssocArray($arrAssocData)
    {
        $sql = array();
        foreach ($arrAssocData as $sFieldName => $mValue) {
            if (preg_match('#^[a-z0-9_`]*$#i', $sFieldName)) {
                $sql[] = $sFieldName . ' = ?';
            } else {
                throw new Exception('FW_MySQL::prepareArray -> "'. $sFieldName . '" is not a valid field name!');
            }
        }
        return $sql;
    }

    static function prepareArrayToSet($arrAssocData)
    {
        return implode(', ', self::prepareAssocArray($arrAssocData));
    }

    static function prepareArrayToAnd($arrAssocData)
    {
        return implode(' AND ', self::prepareAssocArray($arrAssocData));
    }

    static function prepareArrayToOr($arrAssocData)
    {
        return implode(' OR ', self::prepareAssocArray($arrAssocData));
    }

    static function prepareArrayToIn($arrData)
    {
        return implode(', ', array_fill(0, count($arrData), '?'));
    }

    // =========== Security-Methods =========== //
    
    static function escape($mValue)
    {
        $this->connect();
        
        return '\'' . MySqli::real_escape_string((string) $mValue) . '\'';
    }

    static function escapeArray($arrData)
    {
        foreach ($arrData as $sKey => $mValue) {
            $arrData[$sKey] = $this->escape($mValue);
        }
        return $arrData;
    }

    static function escapeAssocArray($arrAssocData)
    {
        $sql = array();
        foreach ($arrAssocData as $sFieldName => $mValue) {
            if (preg_match('#^[a-z0-9_`]*$#i', $sFieldName)) {
                $sql[] = $sFieldName . ' = ' . $this->escape($mValue);
            } else {
                throw new Exception('FW_MySQL::escapeArray -> "'. $sFieldName . '" is not a valid field name!');
            }
        }
        return $sql;
    }

    static function escapeArrayToSet($arrAssocData)
    {
        return implode(', ', self::escapeAssocArray($arrAssocData));
    }

    static function escapeArrayToAnd($arrAssocData)
    {
        return implode(' AND ', self::escapeAssocArray($arrAssocData));
    }

    static function escapeArrayToOr($arrAssocData)
    {
        return implode(' OR ', self::escapeAssocArray($arrAssocData));
    }

    static function escapeArrayToIn($arrData)
    {
        return implode(', ', $this->escapeArray($arrData));
    }

    // ============ Query-Methods ============ //

    function executeQuery($sSQL, $data = null, $iRn = 0)
    {   
        
        $this->connect();
        
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
        $this->connect();

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

    // insert helper to get the primary-key afterwards for persistent db-connections
    function insertHelper($table_name, $primary_key, $data, $query_columns = null) 
    {
        self::cleanBasePutData($data, $primary_key);

        if (!count($data)) {
            return false;
        }

        $is_persistent = $this->isPersistent();

        if (!is_array($query_columns) || !$is_persistent) {
            $res = $this->executeQuery(
                'INSERT INTO '.$table_name.' SET '.self::prepareArrayToSet($data).';',
                array_values($data));

            if (!$res || !is_array($query_columns)) {
                return $res;
            }

            return $this->getLastInsertId();
        }

        $data['insert_key'] = hrtime(true);
        $query_columns[] = 'insert_key';
        $query = array_intersect_key($data, array_flip($query_columns));

        $res = $this->executeQuery(
                'INSERT INTO '.$table_name.' SET '.self::prepareArrayToSet($data).';',
                array_values($data));
        if ($res) {
            $res = $this->executeQuery('SELECT max('.$primary_key.') FROM '.$table_name.' 
                WHERE '.self::prepareArrayToAnd($query).';',
                array_values($query));
            if ($res) {
                $id = $this->fetchRow();
                if ($id) {
                    return $id[0];
                }
            }
        }
        return false;
    }

    function insertMultipleHelper($table_name, $primary_key, $data) 
    {
        if (!count($data)) {
            return false;
        }

        $placeholder = array();
        $values = array();
        foreach (array_keys($data) as $i) {
            self::cleanBasePutData($data[$i], $primary_key);
            $placeholder[] = '('.self::prepareArrayToIn($data[$i]).')';
            $values = array_merge($values, array_values($data[$i]));
        }

        if (!count($data[0])) {
            return false;
        }
        
        return $this->executeQuery('INSERT INTO ('.self::prepareArrayInsert($data[0]).') 
                VALUES '.implode(', ', $placeholder).';', $values);
    }

    function updateHelper($table_name, $primary_key, $data, $where_clause, $where_data) 
    {
        self::cleanBasePutData($data, $primary_key);

        if (!count($data)) {
            return false;
        }

        $res = $this->executeQuery(
            'UPDATE '.$table_name.' SET 
                '.self::prepareArrayToSet($data).',
                update_timestamp = NOW(),
                cnt_update = cnt_update + 1
            WHERE '.$where_clause.';',
            array_merge(array_values($data), $where_data));
        if ($res) {
            return $this->getAffectedRows();
        }
        return false;
    }

    // ============ Result-Methods ============ //
    
    function getError($iRn = 0)
    {
        return !empty($this->_arrError[$iRn]) ? $this->_arrError[$iRn] : null;
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
        return $this->_rMySqli->insert_id;
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
        $this->connect();
        
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
        $this->_hasLockedTables = true;
        $this->executeQuery('LOCK TABLES ' . $mTables . ' WRITE', null, 1846464);
    }

    function unlockTables()
    {
        $this->executeQuery('UNLOCK TABLES', null, 1846464);
        $this->_hasLockedTables = false;
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
        if ($this->_isConnected) {
            if (count($this->_arrLog)) {
                FW_ErrorLogger::writeInfo('SQL Log: ' . count($this->_arrLog) . " Abfragen<br>\n<ul><li>" . implode("</li>\n<li>", $this->_arrLog) . '</li></ul>', FW_MySQL::$sLogFileName);
            } else {
                FW_ErrorLogger::writeInfo('SQL Log: 0 Abfragen', FW_MySQL::$sLogFileName);
            }
        }
    }
}
