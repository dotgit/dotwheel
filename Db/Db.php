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
    /** @var Resource current connection */
    static protected $conn = null;



    /** connects to the database using the currently set codepage
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $charset
     * @return Resource|bool new database connection or <i>false</i> on error +
     * error_log
     */
    public static function connect($host=null, $username=null, $password=null, $database=null, $charset='UTF8')
    {
        if (self::$conn = \mysqli_init()
            and \mysqli_options(self::$conn, MYSQLI_SET_CHARSET_NAME, $charset)
            and \mysqli_real_connect(self::$conn, $host, $username, $password, $database)
        )
        {
            return self::$conn;
        }
        else
        {
            \error_log('['.__METHOD__."] >>>>> CANNOT CONNECT TO $username@$host/$database, mysql message: ".
                \mysqli_connect_error());
            return false;
        }
    }

    /** executes a sql statement and fetches only one record in associative mode
     * @param string $sql   SQL sentence
     * @return array|bool hash with the row information or <i>false</i> on error
     * + error_log
     */
    public static function fetchRow($sql)
    {
        if ($_ = \mysqli_query(self::$conn, $sql))
            return \mysqli_fetch_assoc($_);
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn).'; SQL: '.$sql);
            return false;
        }
    }

    public static function fetchRowDEBUG($sql)
    {
        \error_log('['.__METHOD__.'] SQL: '.$sql);
        return self::fetchRow($sql);
    }

    /**    executes a sql statement and fetches all records into a hash array. each
     * record must consist of just two columns -- key(first) and value(second)
     * @param string $sql   SQL sentence (returning at least two columns)
     * @return array|bool hash with the rows or <i>false</i> on error + error_log
     */
    public static function fetchList($sql)
    {
        if ($_ = \mysqli_query(self::$conn, $sql))
        {
            $lst = array();
            if ($rows = \mysqli_fetch_all($_))
                foreach ($rows as $row)
                    $lst[$row[0]] = $row[1];
            \mysqli_free_result($_);

            return $lst;
        }
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn).'; SQL: '.$sql);
            return false;
        }
    }

    public static function fetchListDEBUG($sql)
    {
        \error_log('['.__METHOD__.'] SQL: '.$sql);
        return self::fetchList($sql);
    }

    /** executes a sql statement and fetches all records into a hash. <i>$key</i>
     * column is considered a key in the returned list and the corresponding row
     * is a corresponding value
     * @param string $sql   SQL sentence
     * @param string $key   key column name
     * @return array|bool hash with the rows or <i>false</i> on error + error_log
     */
    public static function fetchHash($sql, $key)
    {
        if ($_ = \mysqli_query(self::$conn, $sql))
        {
            $hash = array();
            if ($rows = \mysqli_fetch_all($_, MYSQLI_ASSOC))
                foreach ($rows as $row)
                    $hash[$row[$key]] = $row;
            \mysqli_free_result($_);

            return $hash;
        }
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn).'; SQL: '.$sql);
            return false;
        }
    }

    public static function fetchHashDEBUG($sql, $key)
    {
        \error_log('['.__METHOD__.'] SQL: '.$sql.'; KEY: '.$key);
        return self::fetchHash($sql, $key);
    }

    /** executes a sql statement and fetches all records into an array
     * @param string $sql   SQL sentence
     * @return array|bool array with the rows or <i>false</i> on error + error_log
     */
    public static function fetchArray($sql)
    {
        if ($_ = \mysqli_query(self::$conn, $sql))
        {
            $lst = \mysqli_fetch_all($_, MYSQLI_ASSOC);
            \mysqli_free_result($_);

            return isset($lst) ? $lst : array();
        }
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn).'; SQL: '.$sql);
            return false;
        }
    }

    public static function fetchArrayDEBUG($sql)
    {
        \error_log('['.__METHOD__.'] SQL: '.$sql);
        return self::fetchArray($sql);
    }

    /** executes a sql statement, fetches all records and concatenates the first
     * column from each record into a final CSV string
     * @param string $sql   SQL sentence(selecting one column)
     * @return string|bool CSV string or <i>false</i> on error + error_log
     */
    public static function fetchCsv($sql)
    {
        if ($_ = \mysqli_query(self::$conn, $sql))
        {
            $lst = array();
            if ($rows = \mysqli_fetch_all($_))
                foreach ($rows as $row)
                    $lst[] = $row[0];
            \mysqli_free_result($_);

            return \implode(',', $lst);
        }
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn).'; SQL: '.$sql);
            return false;
        }
    }

    public static function fetchCsvDEBUG($sql)
    {
        \error_log('['.__METHOD__.'] SQL: '.$sql);
        return self::fetchCsv($sql);
    }

    /** access database using low level HANDLER statement via primary key to fetch
     * one row in associative mode
     * @param string $table table name
     * @param int|array $pk primary key value or array for multiple colunms key
     * @return array|bool hash with the row information or <i>false</i> on error
     * + error_log
     */
    public static function getPrimary($table, $pk)
    {
        $key = \is_array($pk) ? self::escapeIntCsv($pk) : (int)$pk;
        if (\mysqli_query(self::$conn, "handler $table open"))
        {
            if ($_ = \mysqli_query(self::$conn, "handler $table read `PRIMARY` = ($key)"))
            {
                $row = \mysqli_fetch_assoc($_);
                \mysqli_query(self::$conn, "handler $table close");

                return $row;
            }
            else
            {
                \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn)."; TABLE: $table; KEY: $key");
                return false;
            }
        }
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn)."; TABLE: $table; KEY: $key");
            return false;
        }
    }

    /** executes a DML sentence
     * @param string $sql   DML sentence
     * @return int|bool number of affected rows or <i>false</i> on error + error_log
     */
    public static function dml($sql)
    {
        if (\mysqli_query(self::$conn, $sql))
            return \mysqli_affected_rows(self::$conn);
        else
        {
            \error_log('['.__METHOD__.'] '.\mysqli_error(self::$conn).'; SQL: '.$sql);
            return false;
        }
    }

    public static function dmlDEBUG($sql)
    {
        \error_log('['.__METHOD__.'] SQL: '.$sql);
        return self::dml($sql);
    }

    /** executes a prepared DML sentence with bound parameters
     * @param string $sql   DML sentence with ?-placeholders
     * @param string $types params types string as in mysqli_stmt_bind_param()
     * @param array $params bind parameters used for ?-placeholders
     * @return int|bool number of affected rows or <i>false</i> on error + error_log
     * @link http://php.net/manual/en/mysqli-stmt.bind-param.php
     */
    public static function dmlBind($sql, $types, $params)
    {
        if ($stmt = \mysqli_prepare(self::$conn, $sql)
            and \call_user_func_array(
                'mysqli_stmt_bind_param',
                \array_merge(array($stmt, $types), \array_map(function(&$el){return $el;}, $params))
            )
            and \mysqli_stmt_execute($stmt)
        )
        {
            $res = \mysqli_stmt_affected_rows($stmt);
            \mysqli_stmt_close($stmt);
            return $res;
        }
        else
        {
            if ($stmt)
            {
                $err = \mysqli_stmt_error($stmt);
                \mysqli_stmt_close($stmt);
            }
            else
                $err = \mysqli_error(self::$conn);
            \error_log('['.__METHOD__."] $err; SQL: $sql; TYPES: $types; PARAMS: ".\json_encode($params));
            return false;
        }
    }

    public static function dmlBindDEBUG($sql, $types, $params)
    {
        \error_log('['.__METHOD__."] SQL: $sql; TYPES: $types; PARAMS: ".\json_encode($params));
        return self::dmlBind($sql, $types, $params);
    }

    /** @return int last insert id */
    public static function insertId()
    {
        return \mysqli_insert_id(self::$conn);
    }

    /** restores blob value encoded with blobEncode()
     * @param type $blob    encoded blob value
     * @return string original blob value
     */
    public static function blobDecode($blob)
    {
        if (\substr($blob, 0, 3) == ' z:')
            $blob = \gzinflate(\substr($blob, 3));
        if (\substr($blob, 0, 3) == ' j:')
            $blob = \json_decode(\substr($blob, 3), true);

        return $blob;
    }

    /** serializes the value if it is not a scalar. long values are gzdeflate-d
     * @param type $blob    value to store
     * @return string encoded blob value
     */
    public static function blobEncode($blob)
    {
        if (isset($blob) and ! \is_scalar($blob))
            $blob = ' j:'.\json_encode($blob, \JSON_NUMERIC_CHECK | \JSON_UNESCAPED_SLASHES);
        if (\strlen($blob) > 127)
            $blob = ' z:'.\gzdeflate($blob);

        return \strlen($blob) <= 65535 ? $blob : null;
    }

    /** escapes the passed value following tha database rules (normally used to
     * escape numbers)
     * @param string $value number to escape
     * @return string escaped value or <i>'NULL'</i> string if the value
     * is not set
     */
    public static function escape($value)
    {
        return isset($value) ? \mysqli_real_escape_string(self::$conn, $value) : 'NULL';
    }

    /** escapes the passed value following tha database rules and wraps it in apostrophes
     * (normally used to escape strings)
     * @param string|array $value   string to escape (if an array is passed then
     * all values are escaped, concatenated with a comma and then wrapped in apostrophes,
     * like in <i>'a,b,c'</i>)
     * @return string wrapped value or <i>'NULL'</i> string if the value
     * is not set
     */
    public static function wrap($value)
    {
        if (\is_array($value))
            return "'".\implode(',', \array_map('self::escape', $value))."'";
        elseif (isset($value))
            return "'".\mysqli_real_escape_string(self::$conn, $value)."'";
        else
            return 'NULL';
    }

    /** produces a CSV string from an array of passed non-zero integers
     * @param array|int $values array of int values to concatenate (if a scalar
     * is passed then it is converted to int and returned)
     * @return string concatenated CSV string or <i>'NULL'</i> string if the value is unset
     */
    public static function escapeIntCsv($values)
    {
        if (\is_array($values))
        {
            return ($res = \array_filter($values))
                ? \implode(',', \array_map('intval', $res))
                : 'NULL'
                ;
        }
        elseif (isset($values))
            return (int)$values;
        else
            return 'NULL';
    }
}
