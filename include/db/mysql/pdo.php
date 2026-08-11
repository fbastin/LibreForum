<?php

if (!defined('PHORUM')) return;

/**
 * Phorum database interaction using PDO.
 */
function phorum_db_interact($return, $sql = NULL, $keyfield = NULL, $flags = 0)
{
    static $conn;

    // Close the database connection.
    if ($return == DB_CLOSE_CONN)
    {
        $conn = null;
        return;
    }

    // Setup a database connection if no database connection is available yet.
    if (empty($conn))
    {
        $PHORUM = $GLOBALS['PHORUM'];
        
        $dsn = "mysql:host=" . $PHORUM['DBCONFIG']['server'];
        if (!empty($PHORUM['DBCONFIG']['port'])) {
            $dsn .= ";port=" . $PHORUM['DBCONFIG']['port'];
        }
        if (!empty($PHORUM['DBCONFIG']['name'])) {
            $dsn .= ";dbname=" . $PHORUM['DBCONFIG']['name'];
        }
        if (!empty($PHORUM['DBCONFIG']['charset'])) {
            $dsn .= ";charset=" . $PHORUM['DBCONFIG']['charset'];
        }

        try {
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            );
            $conn = new PDO($dsn, $PHORUM['DBCONFIG']['user'], $PHORUM['DBCONFIG']['password'], $options);
            
            if(!empty($PHORUM["DBCONFIG"]["strict_mode"])){
                $conn->exec("SET SESSION sql_mode='STRICT_ALL_TABLES'");
            }
        } catch (PDOException $e) {
            if ($flags & DB_NOCONNECTOK) return FALSE;
            phorum_database_error('Failed to connect to the database.');
            exit;
        }
    }

    // Return a quoted parameter.
    if ($return === DB_RETURN_QUOTED) {
        $quoted = $conn->quote($sql);
        if ($quoted !== false) {
            // PDO adds single quotes around the escaped string, Phorum expects just the escaped inner string.
            return substr($quoted, 1, -1);
        }
        return addslashes($sql);
    }

    // RETURN: database connection handle
    if ($return === DB_RETURN_CONN) {
        return $conn;
    }

    if ($sql === NULL) trigger_error(
        'Internal error: phorum_db_interact(): missing sql query statement!', E_USER_ERROR
    );

    $tries = 0;
    $res = false;

    while($res === FALSE && $tries < 3){
        $res = $conn->query($sql);

        if ($res === FALSE) {
            $errorInfo = $conn->errorInfo();
            $errno = isset($errorInfo[1]) ? $errorInfo[1] : 0;
            $err = isset($errorInfo[2]) ? $errorInfo[2] : "Unknown error";

            if ($tries < 3 && ($errno == 1422 || $errno == 1213 || $errno == 1205)) {
                $tries++;
                continue;
            }

            $ignore_error = FALSE;
            switch ($errno) {
                case 1146: if ($flags & DB_MISSINGTABLEOK) $ignore_error = TRUE; break;
                case 1050: if ($flags & DB_TABLEEXISTSOK) $ignore_error = TRUE; break;
                case 1060: if ($flags & DB_DUPFIELDNAMEOK) $ignore_error = TRUE; break;
                case 1061: if ($flags & DB_DUPKEYNAMEOK) $ignore_error = TRUE; break;
                case 1062:
                case 1582: if ($flags & DB_DUPKEYOK) $ignore_error = TRUE; break;
            }

            if (! $ignore_error) {
                if ($return === DB_RETURN_ERROR) return $err;
                phorum_database_error("$err ($errno): $sql");
                exit;
            }
            break;
        }
    }

    if ($return === DB_RETURN_ERROR) return NULL;
    if ($return === DB_RETURN_RES) return $res;
    if ($return === DB_RETURN_ROWCOUNT) return $res ? $res->rowCount() : 0;

    if ($return === DB_RETURN_ROW || $return === DB_RETURN_ROWS || $return === DB_RETURN_VALUE) {
        if ($return !== DB_RETURN_ROWS) $keyfield = NULL;
        $rows = array();
        if ($res) {
            while ($row = $res->fetch(PDO::FETCH_NUM)) {
                if ($keyfield === NULL) {
                    $rows[] = $row;
                } else {
                    $rows[$row[$keyfield]] = $row;
                }
            }
        }
        if ($return === DB_RETURN_ROWS) return $rows;
        if ($return === DB_RETURN_ROW) return count($rows) == 0 ? NULL : $rows[0];
        return count($rows) == 0 ? NULL : $rows[0][0];
    }

    if ($return === DB_RETURN_ASSOC || $return === DB_RETURN_ASSOCS) {
        if ($return !== DB_RETURN_ASSOCS) $keyfield = NULL;
        $rows = array();
        if ($res) {
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                if ($keyfield === NULL) {
                    $rows[] = $row;
                } else {
                    $rows[$row[$keyfield]] = $row;
                }
            }
        }
        if ($return === DB_RETURN_ASSOCS) return $rows;
        return count($rows) == 0 ? NULL : $rows[0];
    }

    if ($return === DB_RETURN_NEWID) return $conn->lastInsertId();

    trigger_error('Internal error: phorum_db_interact(): illegal return type specified!', E_USER_ERROR);
}

function phorum_db_fetch_row($res, $type)
{
    if ($type === DB_RETURN_ASSOC) {
        $row = $res->fetch(PDO::FETCH_ASSOC);
    } elseif ($type === DB_RETURN_ROW) {
        $row = $res->fetch(PDO::FETCH_NUM);
    } else {
        trigger_error('Internal error: phorum_db_fetch_row(): illegal $type parameter used', E_USER_ERROR);
    }
    return $row ? $row : NULL;
}
