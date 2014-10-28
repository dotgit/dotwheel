<?php

/**
 * miscellanous functions without special attribution to other classes
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

use Dotwheel\Nls\Nls;

class Misc
{
    /** add $n months to the $date_begin considering not skipping short months
     * @param string $date_begin    date that need to be incremented
     * @param int $n                number of months to add
     * @param string $date_base     initial date to trim to (only days part is used)
     * @return string               incremented date in YYYY-MM-DD format
     * @assert('2013-01-01', 1) == '2013-02-01'
     * @assert('2013-01-31', 1) == '2013-02-28'
     * @assert('2013-02-28', 1) == '2013-03-28'
     * @assert('2013-02-28', 1, '2013-01-31') == '2013-03-31'
     * @assert('2013-02-28', 1, '2013-01-29') == '2013-03-29'
     * @assert('2013-02-28', 2, '2013-01-29') == '2013-04-29'
     */
    public static function addMonths($date_begin, $n=1, $date_base=null)
    {
        $n = (int)$n;
        if (empty($date_base))
            $date_base = $date_begin;

        $date_next = \date('Y-m-d', \strtotime("$date_begin +$n month"));

        if (\substr($date_next, 8, 2) != \substr($date_begin, 8, 2))
            return \date('Y-m-d', \strtotime("$date_next last day of previous month"));
        elseif (\substr($date_next, 8, 2) != \substr($date_base, 8, 2))
            return \substr($date_next, 0, 8).
                \min((int)\date('t', \strtotime($date_next)), (int)\substr($date_base, 8, 2));
        else
            return $date_next;
    }

    /** converts numbers from shorthand form (like '1K') to an integer
     * @param string $size_str  shorthand size string to convert
     * @return int integer value in bytes
     */
    public static function convertSize($size_str)
    {
        switch (\substr($size_str, -1))
        {
            case 'M': case 'm': return (int)$size_str << 20;
            case 'K': case 'k': return (int)$size_str << 10;
            case 'G': case 'g': return (int)$size_str << 30;
            default: return (int)$size_str;
        }
    }

    /** assembles standard address representation from different parts. resulting
     * address is in the following form:
     * [street]
     * [postal] [city] [country]
     * @param type $street  address street
     * @param type $postal  address postal code
     * @param type $city    address city
     * @param type $country address country
     * @return string       standard address representation
     */
    public static function formatAddress($street, $postal, $city, $country)
    {
        return self::joinWs(array("\n", $street, array(' ', $postal, $city, $country)));
    }

    /** html-formatted string with some bb-style formatting convertion
     * @param string $text  bb-style formatted string (recognizes *bold*, /italic/,
     *                      ---header lines---, lines started with dash are bulleted)
     * @return string
     * @see Snippets::preview_txt()
     */
    public static function formatPreview($text)
    {
        return \preg_replace(
            array('#&#', '#<#', '#>#',
                '#/([^/\r\n]*)/#m', '#\*([^*\\r\\n]*)\*#m',
                '#^#m', '#$#m',
                '#^<p>---(.*)---</p>$#m',
                '#^<p>-(.*)</p>$#m'
            ),
            array('&amp;', '&lt;', '&gt;',
                '<i>$1</i>', '<b>$1</b>',
                '<p>', '</p>',
                '<h5>$1</h5>',
                '<li>$1</li>'
            ),
            $text
        );
    }

    /** return the formatted tel number or the original string
     * @param string $tel
     * @return string
     */
    public static function formatTel($tel)
    {
        $t = \str_replace(array(' ', '.', '-', '(0)'), '', $tel);
        $m = array();
        if (\preg_match('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m))
            return "$m[1] $m[2] $m[3] $m[4] $m[5]";
        elseif (\preg_match('/^\+?\(?(\d{2})\)?(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m))
            return "+$m[1] $m[2] $m[3] $m[4] $m[5] $m[6]";
        else
            return $tel;
    }

    public static function getMaxUploadSize()
    {
        return \min(
            self::convertSize(\ini_get('upload_max_filesize')),
            self::convertSize(\ini_get('post_max_size'))
        );
    }

    /** returns human-readable amount rounded to 3 meaningful numbers with appropriate suffix (T,G,M,K)
     * @param int $amount   amount to convert
     * @param string $order an order to use, one of (T,G,M,K). if provided, no
     *                      suffix is appended
     * @return string value like '150', '8.37K', '15M', '374G'
     */
    public static function humanFloat($amount, $order=null)
    {
        $amount_abs = \abs($amount);
        switch($order)
        {
        case 'k':
        case 'K':
            return $amount_abs >= 100000
                ? \round($amount/1000)
                : ($amount_abs >= 10000
                    ? \round($amount/1000, 1)
                    : \round($amount/1000, 2)
                );

        case 'm':
        case 'M':
            return $amount_abs >= 100000000
                ? \round($amount/1000000)
                : ($amount_abs >= 10000000
                    ? \round($amount/1000000, 1)
                    : \round($amount/1000000, 2)
                );

        case 'g':
        case 'G':
            return $amount_abs >= 100000000000
                ? \round($amount/1000000000)
                : ($amount_abs >= 10000000000
                    ? \round($amount/1000000000, 1)
                    : \round($amount/1000000000, 2)
                );

        case 't':
        case 'T':
            return $amount_abs >= 100000000000000
                ? \round($amount/1000000000000)
                : ($amount_abs >= 10000000000000
                    ? \round($amount/1000000000000, 1)
                    : \round($amount/1000000000000, 2)
                );

        default:
            if (isset($order))
                return $amount_abs >= 100
                    ? \round($amount)
                    : ($amount_abs >= 10
                        ? \round($amount, 1)
                        : \round($amount, 2)
                    );
            elseif ($amount_abs >= 1000000000000)
                return ($amount_abs >= 100000000000000
                    ? \round($amount/1000000000000)
                    : ($amount_abs >= 10000000000000
                        ? \round($amount/1000000000000, 1)
                        : \round($amount/1000000000000, 2)
                    )
                ).'T';
            elseif ($amount_abs >= 1000000000)
                return ($amount_abs >= 100000000000
                    ? \round($amount/1000000000)
                    : ($amount_abs >= 10000000000
                        ? \round($amount/1000000000, 1)
                        : \round($amount/1000000000, 2)
                    )
                ).'G';
            elseif ($amount_abs >= 1000000)
                return ($amount_abs >= 100000000
                    ? \round($amount/1000000)
                    : ($amount_abs >= 10000000
                        ? \round($amount/1000000, 1)
                        : \round($amount/1000000, 2)
                    )
                ).'M';
            elseif ($amount_abs >= 1000)
                return ($amount_abs >= 100000
                    ? \round($amount/1000)
                    : ($amount_abs >= 10000
                        ? \round($amount/1000, 1)
                        : \round($amount/1000, 2)
                    )
                ).'K';
            else
                return $amount_abs >= 100
                    ? \round($amount)
                    : ($amount_abs >= 10
                        ? \round($amount, 1)
                        : \round($amount, 2)
                    );
        }
    }

    /** returns human-readable number of bytes with appropriate suffix (T,G,M,K).
     * rounded up to a higher integer
     * @param int $bytes    number of bytes to convert
     * @param string $order an order to use, one of (T,G,M,K). if provided, no
     *                      suffix is appended
     * @return string value like '150', '9K', '15M', '374G'
     */
    public static function humanBytes($bytes, $order=null)
    {
        switch($order)
        {
        case 'k':
        case 'K':
            return \ceil($bytes/1024);

        case 'm':
        case 'M':
            return \ceil($bytes/1048576);

        case 'g':
        case 'G':
            return \ceil($bytes/1073741824);

        case 't':
        case 'T':
            return \ceil($bytes/1099511627776);

        default:
            if ($bytes >= 1099511627776)
                return \ceil($bytes/1099511627776).'T';
            elseif ($bytes >= 1073741824)
                return \ceil($bytes/1073741824).'G';
            elseif ($bytes >= 1048576)
                return \ceil($bytes/1048576).'M';
            elseif ($bytes >= 1024)
                return \ceil($bytes/1024).'K';
            else
                return (int)$bytes;
        }
    }

    /** returns the parts from <code>$params</code> joined using the first parameter
     * as separator. if part is an array then calls itself recursively providing
     * this array as parameter. if the glue is an array then uses it as ['prefix',
     * 'separator', 'suffix']
     * @param array $params [separator, part1, part2, ...] where
     *                      separator may be a string or array ['prefix', 'separator', 'suffix']
     *                      partN may be a string or array [separatorN, partN1, partN2, ...]
     * @return string
     */
    public static function joinWs($params=array())
    {
        $elements = array();
        $splitter = '';

        foreach ($params as $k=>$v)
        {
            if ($k)
            {
                if (isset($v))
                {
                    if (\is_scalar($v))
                        $elements[] = $v;
                    elseif (($v = self::joinWs($v)) !== null)
                        $elements[] = $v;
                }
            }
            else
                $splitter = $v;
        }
        if (\is_scalar($splitter))
            return $elements ? \implode($splitter, $elements) : null;
        elseif ($elements)
            return $splitter[0].\implode($splitter[1], $elements).$splitter[2];
        else
            return null;
    }

    /** compacts $values by removing all null values from the array and adding the
     * corresponding keys to a new 'N' field
     * @param array $values hash of values to compact
     * @return array resulting array like {key1:value1, key3:value3, N:[key2,key4]}
     */
    public static function nullCompact($values)
    {
        if ($empty = \array_keys($values, null, true))
        {
            $res = \array_diff_key($values, \array_flip($empty));
            $res['N'] = $empty;

            return $res;
        }
        else
            return $values;
    }

    /** restores null values in the array from the 'N' field and removes the
     * 'N' field afterwards
     * @param array $values hash, compacted with hash_compact()
     * @return array restored array like {key1:value1, key2:null, key3:value3, key4:null}
     */
    public static function nullRestore($values)
    {
        if (isset($values['N']))
        {
            $values += \array_fill_keys($values['N'], null);
            unset($values['N']);
        }

        return $values;
    }

    /** sets session cookie ttl and starts the session or regenerates session id
     * if session already open
     * @param int $ttl  new time to live in seconds
     */
    public static function sessionSetTtl($ttl)
    {
        \session_set_cookie_params($ttl);
        if (\session_status() == \PHP_SESSION_NONE)
            \session_start();
        else
            \session_regenerate_id(true);
    }

    /** simplify the line by only keeping lowercased alphanumeric symbols and replacing all the rest with dashes,
     * then coalescing dashes and removing trailing dashes
     * ex: 'Very Common Name, Inc...' becomes 'very-common-name-inc'
     * @param string $line
     * @return string
     */
    public static function simplifyLine($line)
    {
        return \trim(\preg_replace(
            '/\\W+/',
            '-',
            \mb_strtolower($line, Nls::$charset)
        ), '-');
    }

    /** escapes a string to be used in sprintf by doubling the % characters
     * @param string $str   string to escape
     * @return string
     */
    public static function sprintfEscape($str)
    {
        return \str_replace('%', '%%', $str);
    }

    /** trims string to the specified length adding suffix
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     */
    public static function trim($str, $len=0, $suffix='...')
    {
        return ($len && \mb_strlen($str, Nls::$charset) > $len)
            ? \mb_substr($str, 0, $len - \max(0, \mb_strlen($suffix, Nls::$charset) - 1), Nls::$charset).$suffix
            : $str;
    }

    /** trims string to the specified length by word boundary adding suffix
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     */
    public static function trimWord($str, $len=100, $suffix='...')
    {
        return \mb_substr($str,
            0,
            $len - \mb_strlen(
                \mb_strrchr(\mb_substr($str, 0, $len, Nls::$charset), ' ', false, Nls::$charset),
                Nls::$charset
            ),
            Nls::$charset
        ).$suffix;
    }

    /** whether the byte code corresponds to a utf-8 starting byte
     * @param int $char
     * @return bool
     */
    public static function utf8First($char)
    {
        return (($char & 0b11100000) == 0b11000000)
            or (($char & 0b11110000) == 0b11100000)
            or (($char & 0b11111000) == 0b11110000)
            or (($char & 0b11111100) == 0b11111000)
            or (($char & 0b11111110) == 0b11111100)
            ;
    }

    /** whether the byte code corresponds to a utf-8 following byte
     * @param int $char
     * @return bool
     */
    public static function utf8Next($char)
    {
        return ($char & 0b11000000) == 0b10000000;
    }
}
