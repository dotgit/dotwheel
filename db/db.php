<?php

/**
 * basic functions to retrieve db records and execute dml statements
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Db;

class Db
{
    /** @var ?Resource current connection */
    protected static $conn = null;

    /** connect to the database with specified options
     *
     * @param ?string $host
     * @param ?string $username
     * @param ?string $password
     * @param ?string $database
     * @param array $options
     * @return Resource|bool new database connection or <i>false</i> on error + error_log
     */
    public static function connect(
        ?string $host,
        ?string $username,
        ?string $password,
        ?string $database,
        array $options = []
    ) {
        if ($conn = mysqli_init()) {
            foreach ($options as $name => $value) {
                mysqli_options($conn, $name, $value);
            }
            if (mysqli_real_connect($conn, $host, $username, $password, $database)) {
                self::$conn = $conn;
                return self::$conn;
            }
        }

        error_log(sprintf(
            '[%s] >>>>> CANNOT CONNECT TO %s@%s/%s, mysql message: %s',
            __METHOD__, $username, $host, $database, mysqli_connect_error()
        ));
        return false;
    }

    /** get current connection
     *
     * @return Resource current DB connection
     */
    public static function getConnection()
    {
        return self::$conn;
    }

    /** execute a sql statement and fetch only one record in associative mode
     *
     * @param string $sql SQL sentence
     * @return array|null|bool hash with the row information, <i>null</i> if not found or <i>false</i> on error
     * + error_log
     */
    public static function fetchRow(string $sql)
    {
        if ($_ = mysqli_query(self::$conn, $sql)) {
            $row = mysqli_fetch_assoc($_);
            mysqli_free_result($_);
            return $row;
        } else {
            error_log(sprintf("[%s] %s; SQL: %s", __METHOD__, mysqli_error(self::$conn), $sql));
            return false;
        }
    }

    public static function fetchRowDEBUG($sql)
    {
        error_log(sprintf("[%s] SQL: %s", __METHOD__, $sql));
        return self::fetchRow($sql);
    }

    /** execute a sql statement and fetch all records into a hash array. each record must consist of just two columns:
     * key(first) and value(second)
     *
     * @param string $sql SQL sentence (returning at least two columns)
     * @return array|bool hash with the rows or <i>false</i> on error + error_log
     */
    public static function fetchList(string $sql)
    {
        if ($_ = mysqli_query(self::$conn, $sql)) {
            $lst = [];
            if ($rows = mysqli_fetch_all($_)) {
                foreach ($rows as $row) {
                    $lst[$row[0]] = $row[1];
                }
            }
            mysqli_free_result($_);
            return $lst;
        } else {
            error_log(sprintf("[%s] %s; SQL: %s", __METHOD__, mysqli_error(self::$conn), $sql));
            return false;
        }
    }

    public static function fetchListDEBUG($sql)
    {
        error_log(sprintf("[%s] SQL: %s", __METHOD__, $sql));
        return self::fetchList($sql);
    }

    /** execute a sql statement and fetch all records into a hash. <i>$key</i> column is considered a key in the
     * returned list and the corresponding row is a corresponding value
     *
     * @param string $sql SQL sentence
     * @param string $key key column name
     * @return array|bool hash with the rows or <i>false</i> on error + error_log
     */
    public static function fetchHash(string $sql, string $key)
    {
        if ($_ = mysqli_query(self::$conn, $sql)) {
            $hash = [];
            if ($rows = mysqli_fetch_all($_, MYSQLI_ASSOC)) {
                foreach ($rows as $row) {
                    $hash[$row[$key]] = $row;
                }
            }
            mysqli_free_result($_);
            return $hash;
        } else {
            error_log(sprintf("[%s] %s; SQL: %s", __METHOD__, mysqli_error(self::$conn), $sql));
            return false;
        }
    }

    public static function fetchHashDEBUG($sql, $key)
    {
        error_log(sprintf("[%s] SQL: %s; KEY: %s", __METHOD__, $sql, $key));
        return self::fetchHash($sql, $key);
    }

    /** execute a sql statement and fetch all records into an array
     *
     * @param string $sql SQL sentence
     * @return array|bool array with the rows or <i>false</i> on error + error_log
     */
    public static function fetchArray(string $sql)
    {
        if ($_ = mysqli_query(self::$conn, $sql)) {
            $lst = mysqli_fetch_all($_, MYSQLI_ASSOC);
            mysqli_free_result($_);
            return $lst;
        } else {
            error_log(sprintf("[%s] %s; SQL: %s", __METHOD__, mysqli_error(self::$conn), $sql));
            return false;
        }
    }

    public static function fetchArrayDEBUG($sql)
    {
        error_log(sprintf("[%s] SQL: %s", __METHOD__, $sql));
        return self::fetchArray($sql);
    }

    /** execute a sql statement, fetch all records and concatenate the first column from each record into a final CSV
     * string
     *
     * @param string $sql SQL sentence(selecting one column)
     * @return string|bool CSV string or <i>false</i> on error + error_log
     */
    public static function fetchCsv(string $sql)
    {
        if ($_ = mysqli_query(self::$conn, $sql)) {
            $lst = [];
            if ($rows = mysqli_fetch_all($_)) {
                foreach ($rows as $row) {
                    $lst[] = $row[0];
                }
            }
            mysqli_free_result($_);
            return implode(',', $lst);
        } else {
            error_log(sprintf("[%s] %s; SQL: %s", __METHOD__, mysqli_error(self::$conn), $sql));
            return false;
        }
    }

    public static function fetchCsvDEBUG($sql)
    {
        error_log(sprintf("[%s] SQL: %s", __METHOD__, $sql));
        return self::fetchCsv($sql);
    }

    /** access database using low level HANDLER statement via primary key to fetch one row in associative mode
     *
     * @param string $table table name
     * @param int|string|array $pk primary key value or array for multiple columns key. keys must be properly escaped
     * @return array|bool hash with the row information or <i>false</i> on error + error_log
     */
    public static function handlerReadPrimary(string $table, $pk)
    {
        return self::handlerReadIndex($table, '`PRIMARY`', $pk);
    }

    /** access database using low level HANDLER statement via specified index to fetch one row in associative mode
     *
     * @param string $table table name
     * @param string $index table index name
     * @param int|string|array $value index scalar value or array of scalars for multiple columns key. keys must be
     *  properly escaped
     * @return array|bool hash with the row information or <i>false</i> on error + error_log
     */
    public static function handlerReadIndex(string $table, string $index, $value)
    {
        $key = implode(',', (array)$value);
        if (mysqli_query(self::$conn, "handler $table open")) {
            if ($_ = mysqli_query(self::$conn, "handler $table read $index = ($key)")) {
                $row = mysqli_fetch_assoc($_);
                mysqli_query(self::$conn, "handler $table close");
                return $row;
            } else {
                error_log(sprintf(
                    "[%s] %s; TABLE: %s(%s); VALUE: %s",
                    __METHOD__, mysqli_error(self::$conn), $table, $index, $key
                ));
                return false;
            }
        } else {
            error_log(sprintf(
                "[%s] %s; TABLE: %s(%s); VALUE: %s",
                __METHOD__, mysqli_error(self::$conn), $table, $index, $key
            ));
            return false;
        }
    }

    /** access database using low level HANDLER statement via primary keys to fetch many rows in associative mode
     *
     * @param string $table table name
     * @param array $pks array of primary key values (integer values), like [pk1, pk2]. keys must be properly escaped
     * @return array|bool hash with the row information, like {
     *  pk1:{record 1},
     *  pk2:{record 2}
     * } or <i>false</i> on error + error_log
     */
    public static function handlerReadPrimaryMulti(string $table, array $pks)
    {
        return self::handlerReadIndexMulti($table, '`PRIMARY`', $pks);
    }

    /** access database using low level HANDLER statement via specified index to fetch many rows in associative mode
     *
     * @param string $table table name
     * @param string $index table index name
     * @param array $values array of index values, like [pk1, pk2] or [
     *  [pk11, pk12],
     *  [pk21, pk22],
     *  ...
     * ]. keys must be properly escaped.
     * @return array|bool hash with the row information, like {
     *  val1:{record 1},
     *  val2:{record 2}
     * } or <i>false</i> on error + error_log
     */
    public static function handlerReadIndexMulti(string $table, string $index, array $values)
    {
        if (mysqli_query(self::$conn, "handler $table open")) {
            $rows = [];
            foreach ($values as $pk) {
                $key = implode(',', (array)$pk);
                if ($_ = mysqli_query(self::$conn, "handler $table read $index = ($key)")) {
                    $rows[$key] = mysqli_fetch_assoc($_);
                } else {
                    error_log(sprintf(
                        "[%s] %s; TABLE: %s; VALUE: %s",
                        __METHOD__, mysqli_error(self::$conn), $table, $key
                    ));
                    return false;
                }
            }
            mysqli_query(self::$conn, "handler $table close");
            return $rows;
        } else {
            error_log(sprintf("[%s] %s; TABLE: %s", __METHOD__, mysqli_error(self::$conn), $table));
            return false;
        }
    }

    /** execute a DML sentence
     *
     * @param string $sql DML sentence
     * @return int|bool number of affected rows or <i>false</i> on error +
     * error_log
     */
    public static function dml(string $sql)
    {
        if (mysqli_query(self::$conn, $sql)) {
            return mysqli_affected_rows(self::$conn);
        } else {
            error_log(sprintf("[%s] %s; SQL: %s", __METHOD__, mysqli_error(self::$conn), $sql));
            return false;
        }
    }

    public static function dmlDEBUG($sql)
    {
        error_log(sprintf("[%s] SQL: %s", __METHOD__, $sql));
        return self::dml($sql);
    }

    /** execute a prepared DML sentence with bound parameters
     *
     * @param string $sql DML sentence with ?-placeholders
     * @param string $types params types string as in mysqli_stmt_bind_param()
     * @param array $params bind parameters used for ?-placeholders
     * @return int|bool number of affected rows or <i>false</i> on error + error_log
     * @link http://php.net/manual/en/mysqli-stmt.bind-param.php
     */
    public static function dmlBind(string $sql, string $types, array $params)
    {
        if ($stmt = mysqli_prepare(self::$conn, $sql)) {
            $bind_params = [$stmt, $types];
            foreach ($params as &$p) {
                $bind_params[] = &$p;
            }
            if (call_user_func_array('mysqli_stmt_bind_param', $bind_params)
                and mysqli_stmt_execute($stmt)
            ) {
                $res = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                return $res;
            }
        } else {
            $err = mysqli_error(self::$conn);
            error_log(sprintf(
                "[%s] %s; SQL: %s; TYPES: %s; PARAMS: %s",
                __METHOD__, $err, $sql, $types, json_encode($params)
            ));
        }

        return false;
    }

    public static function dmlBindDEBUG($sql, $types, $params)
    {
        error_log(sprintf(
            "[%s] SQL: %s; TYPES: %s; PARAMS: %s",
            __METHOD__, $sql, $types, json_encode($params)
        ));
        return self::dmlBind($sql, $types, $params);
    }

    /** @return int last insert id */
    public static function insertId(): int
    {
        return mysqli_insert_id(self::$conn);
    }

    /** escape passed value following tha database rules (normally used to escape numbers)
     *
     * @param ?string $value number to escape
     * @return string escaped value or "null" if the value is not set
     */
    public static function escapeInt(?string $value)
    {
        return isset($value) ? (int)$value : 'null';
    }

    /** produce a CSV string from an array of passed non-zero integers
     *
     * @param array|int|null $values array of int values to concatenate (if a scalar is passed, it is converted to
     *  int and returned)
     * @return string concatenated CSV string or "null" if the value is unset or empty list
     */
    public static function escapeIntCsv($values)
    {
        if (is_array($values)) {
            $vals = [];
            foreach ($values as $v) {
                if ((int)$v) {
                    $vals[] = (int)$v;
                }
            }
            return $vals ? implode(',', $vals) : 'null';
        } elseif (isset($values)) {
            return (int)$values;
        } else {
            return 'null';
        }
    }

    /** escape passed value following database rules and wrap it in apostrophes (normally used to escape strings)
     *
     * @param ?string $value string to escape
     * @return string wrapped value or "null" if the value is unset
     */
    public static function wrapChar(?string $value): string
    {
        if (isset($value)) {
            return "'" . mysqli_real_escape_string(self::$conn, $value) . "'";
        } else {
            return 'null';
        }
    }

    /** escape passed string values following database rules (normally used to escape array of strings)
     *
     * @param array|string|null $values array of strings to escape
     * @return string comma separated wrapped values "'a','b','c'" or "null" if value unset
     */
    public static function wrapCharCsv($values): string
    {
        if (is_array($values)) {
            $vals = [];
            foreach ($values as $v) {
                if (isset($v)) {
                    $vals[] = "'" . mysqli_real_escape_string(self::$conn, $v) . "'";
                }
            }
            return $vals ? implode(',', $vals) : 'null';
        } elseif (isset($values)) {
            return "'" . mysqli_real_escape_string(self::$conn, $values) . "'";
        } else {
            return 'null';
        }
    }
}
