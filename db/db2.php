<?php

/**
 * less frequently used db functions
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Db;

class Db2
{
    const P_TABLE      = 1;
    const P_FIELDS     = 2;
    const P_VALUES     = 3;
    const P_WHERE      = 4;
    const P_DUPLICATES = 5;
    const P_IGNORE     = 6;

    const FMT_ALPHA = 1;
    const FMT_NUM   = 2;
    const FMT_ASIS  = 3;

    /** constructs and executes a DML command to insert a row in the specified table.
     * <i>P_FIELDS</i> parameter specifies the type of escaping for each field
     * (for example, FMT_ALPHA means escape the value and wrap it in apostrophes,
     * FMT_NUM escapes value as a number, FMT_ASIS includes value as is).
     * only inserts fields present in both <i>P_FIELDS</i> and <i>P_VALUES</i>.
     *
     * @param array $params {
     *  P_TABLE:'table_name', required,
     *  P_FIELDS:{fld1:FMT_ALPHA|FMT_NUM|FMT_ASIS,...},
     *  P_VALUES:{fld1:value1,...},
     *  P_IGNORE:null|true (whether to use ignore hint),
     *  P_DUPLICATES:{fld1:true,fld2:'value',...} (whether to include the
     * <i>'on duplicate key update'</i> part with specified fields)
     * }
     * @return int|bool     number of affected records or <i>false</i> on error
     */
    public static function insert($params)
    {
        $ins  = array();
        $dupl = array();
        foreach (\array_intersect_key($params[self::P_FIELDS], $params[self::P_VALUES]) as $name=> $wrap) {
            if (isset($params[self::P_VALUES][$name])) {
                switch ($wrap) {
                    case self::FMT_ALPHA:
                        $ins[$name] = Db::wrapChar($params[self::P_VALUES][$name]);
                        break;
                    case self::FMT_NUM:
                        $ins[$name] = Db::escapeInt($params[self::P_VALUES][$name]);
                        break;
                    default:
                        $ins[$name] = $params[self::P_VALUES][$name];
                }
            } else {
                $ins[$name] = 'null';
            }
            if (isset($params[self::P_DUPLICATES][$name])) {
                $dupl[$name] = $params[self::P_DUPLICATES][$name] === true
                    ? "$name = values($name)"
                    : "$name = {$params[self::P_DUPLICATES][$name]}";
            }
        }

        return $ins
            ? Db::dml(\sprintf(
                "insert%s into %s (%s) values (%s)%s",
                !empty($params[self::P_IGNORE]) ? ' ignore' : null,
                $params[self::P_TABLE],
                \implode(',', \array_keys($ins)),
                \implode(',', \array_values($ins)),
                $dupl ? (' on duplicate key update '.\implode(',', $dupl)) : ''
            )) : 0;
    }

    /** constructs and executes a DML command to update a row in the specified table.
     * <i>P_FIELDS</i> parameter specifies the type of escaping for each field
     * (for example, FMT_ALPHA means escape the value and wrap it in apostrophes,
     * FMT_NUM escapes value as a number, FMT_ASIS includes value as is).
     * only inserts fields present in both <i>P_FIELDS</i> and <i>P_VALUES</i>.
     *
     * @param array $params {
     *  P_TABLE:'table_name', required,
     *  P_FIELDS:{fld1:FMT_ALPHA|FMT_NUM|FMT_ASIS,...},
     *  P_VALUES:{fld1:value1,...},
     *  P_WHERE:'id = value', required,
     * }
     * @return int|bool     number of affected records or <i>false</i> on error
     */
    public static function update($params)
    {
        $upd = array();
        foreach (\array_intersect_key($params[self::P_FIELDS], $params[self::P_VALUES]) as $name => $wrap) {
            switch ($wrap) {
                case self::FMT_ALPHA:
                    $upd[] = "$name = ".Db::wrapChar($params[self::P_VALUES][$name]);
                    break;
                case self::FMT_NUM:
                    $upd[] = "$name = ".Db::escapeInt($params[self::P_VALUES][$name]);
                    break;
                default:
                    $upd[] = "$name = ".$params[self::P_VALUES][$name];
            }
        }

        return $upd
            ? Db::dml(\sprintf(
                'update %s set %s where %s',
                $params[self::P_TABLE],
                \implode(', ', $upd),
                isset($params[self::P_WHERE]) ? $params[self::P_WHERE] : 'null'
            )) : 0;
    }

    /** restores blob value encoded with blobEncode()
     *
     * @param type $blob    encoded blob value
     * @return string original blob value
     */
    public static function blobDecode($blob)
    {
        if (\substr($blob, 0, 3) == ' z:') {
            $blob = \gzinflate(\substr($blob, 3));
        }
        if (\substr($blob, 0, 3) == ' j:') {
            $blob = \json_decode(\substr($blob, 3), true);
        }

        return $blob;
    }

    /** serializes the value if it is not a scalar. long values are gzdeflate-d
     *
     * @param mixed $blob   value to store
     * @param int $size     size of the field in bytes
     * @return string encoded blob value
     */
    public static function blobEncode($blob, $size = 65535)
    {
        if (isset($blob)
            and (! \is_scalar($blob) or \substr($blob, 0, 3) === ' j:')
        ) {
            $blob = ' j:'.\json_encode($blob);
        }
        if (\strlen($blob) > 127) {
            $blob = ' z:'.\gzdeflate($blob);
        }

        return \strlen($blob) <= $size ? $blob : null;
    }

    /** tries to lock a <i>$token</i>
     *
     * @param string $token the name of the token
     * @param int $ttl max nbr of seconds to spend trying
     * @return bool whether the lock is obtained
     */
    public static function lockGet($token, $ttl = 1)
    {
        $locked = Db::fetchRow(\sprintf('select get_lock(%s, %u) locked', Db::wrapChar($token), $ttl));
        return (bool)$locked['locked'];
    }

    /** sees whether the token is already locked
     *
     * @param string $token the name of the token (must be properly escaped)
     * @return bool whether the token is locked by someone
     */
    public static function lockIsUsed($token)
    {
        $is_locked = Db::fetchRow(\sprintf('select is_used_lock(%s) is_locked', Db::wrapChar($token)));
        return (bool)$is_locked['is_locked'];
    }

    /** releases a locked token
     *
     * @param string $token the name of the token (must be properly escaped)
     * @return bool whether the token was released
     */
    public static function lockRelease($token)
    {
        return Db::dml(\sprintf('do release_lock(%s)', Db::wrapChar($token))) !== false;
    }

    /** get the next id in the sequence. normally increments the maximal id. if
     * maximal id cannot be incremented then search from the start to find the free
     * slot
     *
     * @param array $ids    array of existing ids
     * @param int $max      max available id value
     * @return int|bool next id value in the sequence, an id from a non-used slot
     * or <i>false</i> if all slots used
     */
    public static function nextSequence($ids, $max)
    {
        if (empty($ids)) {
            return 1;
        } elseif (($m = \max($ids)) < $max) {
            return $m + 1;
        } elseif (\count($ids) == $max) {
            return false;
        } else {
            \sort($ids, \SORT_NUMERIC);
            foreach ($ids as $i=> $id) {
                if ($id > $i + 1) {
                    return $i + 1;
                }
            }
        }
        return false;
    }
}
