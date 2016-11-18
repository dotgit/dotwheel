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
        if (empty($date_base)) {
            $date_base = $date_begin;
        }

        $date_next = \date('Y-m-d', \strtotime("$date_begin +$n month"));

        if (\substr($date_next, 8, 2) != \substr($date_begin, 8, 2)) {
            return \date('Y-m-d', \strtotime("$date_next last day of previous month"));
        } elseif (\substr($date_next, 8, 2) != \substr($date_base, 8, 2)) {
            return \substr($date_next, 0, 8).
                \min((int)\date('t', \strtotime($date_next)), (int)\substr($date_base, 8, 2));
        } else {
            return $date_next;
        }
    }

    /** converts numbers from shorthand form (like '1K') to an integer
     * @param string $size_str  shorthand size string to convert
     * @return int integer value in bytes
     * @assert('15') == 15
     * @assert('1K') == 1024
     * @assert('2k') == 2048
     * @assert('1M') == 1048576
     * @assert('1g') == 1073741824
     */
    public static function convertSize($size_str)
    {
        switch (\substr($size_str, -1)) {
            case 'M': case 'm':
                return (int)$size_str << 20;
            case 'K': case 'k':
                return (int)$size_str << 10;
            case 'G': case 'g':
                return (int)$size_str << 30;
            default:
                return (int)$size_str;
        }
    }

    /** returns number of days between 2 dates
     * @param string $date_start    first date of period (yyyy-mm-mm)
     * @param string $date_end      last date of period (yyyy-mm-dd)
     * @return int
     * @assert('2013-01-01', '2013-01-01') == 1
     * @assert('2013-01-01', '2013-01-31') == 31
     * @assert('2013-01-01', '2013-12-31') == 365
     */
    public static function daysInPeriod($date_start, $date_end)
    {
        return \round((\strtotime("$date_end +1 day") - \strtotime($date_start))/86400);
    }

    /** returns html color converted to unsigned int
     * @param string $color html standard color format (#000000 or #000)
     * @return int|bool unsigned int representation of 3-bytes color or <i>false</i> if color is not of
     * form #XXX or #XXXXXX
     * @assert('#000000') == 0
     * @assert('#0000ff') == 255
     * @assert('#00ff00') == 65280
     * @assert('#ff0000') == 16711680
     * @assert('#ffffff') == 16777215
     * @assert('#0f0') == 65280
     * @assert('#f00') == 16711680
     * @assert('#fef') == 16772863
     * @assert('#eef') == 15658751
     * @assert('#cff') == 13434879
     * @assert('#cfc') == 13434828
     * @assert('#fed') == 16772829
     * @assert('#ffd') == 16777181
     * @assert('#fff') == 16777215
     * @assertFalse('abc')
     * @assertFalse('#xyz')
     */
    public static function rgbToInt($color)
    {
        switch (\strlen($color)) {
            case 4:
                $color = "{$color[0]}{$color[1]}{$color[1]}{$color[2]}{$color[2]}{$color[3]}{$color[3]}";
            case 7:
                if (\sscanf(\strtolower($color), '#%02x%02x%02x', $r, $g, $b) != 3) {
                    return false;
                } else {
                    return $r<<16 | $g<<8 | $b;
                }
            default:
                return false;
        }
    }

    /** returns html color value from unsigned int
     * @param int $rgb
     * @return string html color value in the form #XXXXXX
     */
    public static function intToRgb($rgb)
    {
        return \sprintf('#%06x', 0xffffff & $rgb);
    }

    /** assembles standard address representation from different parts. resulting
     * address is in the following form:
     * [street]
     * [postal] [city] [country]
     * @param string $street    address street
     * @param string $postal    address postal code
     * @param string $city      address city
     * @param string $country   address country
     * @return string       standard address representation
     * @assert('Street', 'Postal', 'City', 'Country') == "Street\nPostal City Country"
     * @assert('Street', 'Postal', 'City', null) == "Street\nPostal City"
     * @assert('Street', null, 'City', null) == "Street\nCity"
     * @assert('Street', null, null, null) == "Street"
     * @assert(null, null, 'City', null) == "City"
     */
    public static function formatAddress($street, $postal, $city, $country)
    {
        return self::joinWs(array("\n", $street, array(' ', $postal, $city, $country)));
    }

    /** html-formatted string with some bb-style formatting convertion
     * @param string $text  bb-style formatted string (recognizes *bold*, /italic/,
     *                      ---header lines---, lines started with dash are bulleted)
     * @return string
     * @assert("line") == "<p>line</p>"
     * @assert("line *bold* line") == "<p>line <b>bold</b> line</p>"
     * @assert("line *bold* /italic/ line") == "<p>line <b>bold</b> <i>italic</i> line</p>"
     * @assert("---heading line---\nline") == "<h5>heading line</h5>\n<p>line</p>"
     * @assert("-line 1\n-line 2") == "<li>line 1</li>\n<li>line 2</li>"
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
     * @assert("01.23.45.67.89") == "01 23 45 67 89"
     * @assert("+33-1-23-45-67-89") == "+33 1 23 45 67 89"
     * @assert("T.+33-1-23-45-67-89") == "T.+33-1-23-45-67-89"
     */
    public static function formatTel($tel)
    {
        $t = \str_replace(array(' ', '.', '-', '(0)'), '', $tel);
        $m = array();
        if (\preg_match('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m)) {
            return "$m[1] $m[2] $m[3] $m[4] $m[5]";
        } elseif (\preg_match('/^\+?\(?(\d{2})\)?(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m)) {
            return "+$m[1] $m[2] $m[3] $m[4] $m[5] $m[6]";
        } else {
            return $tel;
        }
    }

    /** return maximum allowed size for uploaded file in bytes
     * @return int
     */
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
     * @assert(20) == '20'
     * @assert(999) == '999'
     * @assert(1000) == '1K'
     * @assert(2000) == '2K'
     * @assert(2100) == '2.1K'
     * @assert(2150) == '2.15K'
     * @assert(2157) == '2.16K'
     * @assert(21573) == '21.6K'
     * @assert(-21573) == '-21.6K'
     * @assert(2000, 'K') == '2'
     * @assert(2157, 'K') == '2.16'
     */
    public static function humanFloat($amount, $order=null)
    {
        $amount_abs = \abs($amount);
        switch ($order) {
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

    /** returns human-readable number of bytes with appropriate suffix (P,T,G,M,K).
     * rounded up to a higher integer
     * @param int $bytes    number of bytes to convert
     * @param string $order an order to use, one of (P,T,G,M,K). if provided, no
     *                      suffix is appended
     * @return string value like '150', '9K', '15M', '374G'
     * @assert(20) == '20'
     * @assert(999) == '999'
     * @assert(1000) == '1000'
     * @assert(1024) == '1K'
     * @assert(1025) == '2K'
     * @assert(2048) == '2K'
     * @assert(1048575) == '1024K'
     * @assert(1048576) == '1M'
     * @assert(1048577) == '2M'
     */
    public static function humanBytes($bytes, $order=null)
    {
        switch($order) {
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

            case 'p':
            case 'P':
                return \ceil($bytes/1125899906842624);

            default:
                if ($bytes >= 1125899906842624)
                    return \ceil($bytes/1125899906842624).'P';
                elseif ($bytes >= 1099511627776)
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
     * @assert(array()) == null
     * @assert(array(' ', 'First', 'Second')) == 'First Second'
     * @assert(array("\n", 'Street', array(' ', 'Postal', 'City'))) == "Street\nPostal City"
     * @assert(array("\n", 'Street', array(' ', 'Postal', null, 'Country'))) == "Street\nPostal Country"
     * @assert(array(array('[', ', ', ']'), 'First', 'Second')) == "[First, Second]"
     * @assert(array("\n", null, null)) == null
     */
    public static function joinWs($params=array())
    {
        $elements = array();
        $splitter = '';

        foreach ($params as $k=>$v) {
            if ($k) {
                if (isset($v)) {
                    if (\is_scalar($v)) {
                        $elements[] = $v;
                    } elseif (($v = self::joinWs($v)) !== null) {
                        $elements[] = $v;
                    }
                }
            } else {
                $splitter = $v;
            }
        }

        if (\is_scalar($splitter)) {
            return $elements ? \implode($splitter, $elements) : null;
        } elseif ($elements) {
            return $splitter[0].\implode($splitter[1], $elements).$splitter[2];
        } else {
            return null;
        }
    }

    /** compacts $values by removing all null values from the array and adding the
     * corresponding keys to a new 'N' field
     * @param array $values hash of values to compact, like {key1:value1, key2:null, key3:value3, key4:null}
     * @return array resulting array like {key1:value1, key3:value3, N:[key2,key4]}
     * @assert(array(1, 2, 3)) == array(1, 2, 3)
     * @assert(array('a'=>1, 'b'=>null, 'c'=>3, 'd'=>null, 'e'=>5)) == array('a'=>1, 'c'=>3, 'e'=>5, 'N'=>array('b', 'd'))
     */
    public static function nullCompact($values)
    {
        if ($empty = \array_keys($values, null, true)) {
            $res = \array_diff_key($values, \array_flip($empty));
            $res['N'] = $empty;
            return $res;
        } else {
            return $values;
        }
    }

    /** restores null values in the array compacted with nullCompact() from the 'N' field and removes the
     * 'N' field afterwards
     * @param array $values hash like {key1:value1, key3:value3, N:[key2,key4]}
     * @return array restored array like {key1:value1, key3:value3, key2:null, key4:null}
     * @assert(array(1, 2, 3)) == array(1, 2, 3)
     * @assert(array('a'=>1, 'c'=>3, 'e'=>5, 'N'=>array('b', 'd'))) == array('a'=>1, 'c'=>3, 'e'=>5, 'b'=>null, 'd'=>null)
     */
    public static function nullRestore($values)
    {
        if (isset($values['N'])) {
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
        if (\session_status() == \PHP_SESSION_NONE) {
            \session_start();
        } else {
            \session_regenerate_id(true);
        }
    }

    /** simplify the line by only keeping lowercased alphanumeric symbols and replacing all the rest with dashes,
     * then coalescing dashes and removing trailing dashes
     * ex: 'Very Common Name, Inc...' becomes 'very-common-name-inc'
     * @param string $line
     * @return string
     * @assert('simple') == 'simple'
     * @assert('simple string') == 'simple-string'
     * @assert('Very Common Name, Inc...') == 'very-common-name-inc'
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
     * @assert('simple') == 'simple'
     * @assert('line with % sign') == 'line with %% sign'
     */
    public static function sprintfEscape($str)
    {
        return \str_replace('%', '%%', $str);
    }

    /** enhance vsprintf semantics to be able to use named args for argument swapping. so instead of calling:
     *  <pre>vsprintf(
     *      'select * from %1$s where %2$s = %3$u',
     *      ['users', 'user_id', 15]
     *  )</pre>
     * we call:
     *  <pre>vsprintfArgs(
     *      'select * from %tbl$s where %id$s = %value$u',
     *      ['tbl'=&gt;'users', 'id'=&gt;'user_id', 'value'=&gt;15]
     *  )</pre>
     *
     * @param string $format    format string, like 'select * from %tbl$s where %id$s = %value$u'
     * @param array $args       named args array, like ['tbl'=&gt;'users', 'id'=&gt;'user_id', 'value'=&gt;15]
     * @return string formatted string, like select * from users where user_id = 15
     * @assert('select * from %tbl$s where %id$s = %value$u', ['tbl'=>'users', 'id'=>'user_id', 'value'=>15]) == 'select * from users where user_id = 15'
     * @assert('select * from %1$s where %2$s = %3$u', ['users', 'user_id', 15]) == 'select * from users where user_id = 15'
     * @assert('select * from %s where %s = %u', ['users', 'user_id', 15]) == 'select * from users where user_id = 15'
     * @assert('aaa=%aaa$s, aa=%aa$s, a=%a$s', ['a'=>'A', 'aa'=>'AA', 'aaa'=>'AAA']) == 'aaa=AAA, aa=AA, a=A'
     */
    public static function vsprintfArgs($format, $args)
    {
        $from = [];
        $to = [];
        $i = 0;

        foreach ($args as $k=>$v) {
            if (! \is_int($k)) {
                $from[] = "%$k$";
                $to[] = '%'.++$i.'$';
            }
        }

        return \vsprintf(\str_replace($from, $to, $format), $args);
    }

    /** trims string to the specified length adding suffix
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     * @assert('line') == 'line'
     * @assert('longer line', 8) == 'longe...'
     * @assert('длинная строка', 10, '.') == 'длинная с.'
     */
    public static function trim($str, $len=0, $suffix='...')
    {
        return ($len && \mb_strlen($str, Nls::$charset) > $len)
            ? \mb_substr($str, 0, $len - \max(0, \mb_strlen($suffix, Nls::$charset)), Nls::$charset).$suffix
            : $str;
    }

    /** trims string to the specified length by word boundary adding suffix
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     * @assert('much longer line', 15) == 'much longer...'
     * @assert('гораздо более длинная строка', 23) == 'гораздо более...'
     */
    public static function trimWord($str, $len=100, $suffix='...')
    {
        return \mb_substr(
            $str,
            0,
            \mb_strlen(
                \mb_strrchr(\mb_substr($str, 0, $len - \mb_strlen($suffix, Nls::$charset) + 1, Nls::$charset), ' ', true, Nls::$charset),
                Nls::$charset
            ),
            Nls::$charset
        ).$suffix;
    }

    /** whether the byte code corresponds to a valid UTF-8 leading byte
     * @param int $char
     * @return int|null|false value indicating number of bytes for the Unicode character,
     * <i>0</i> if trailing byte, <i>null</i> if US-ASCII byte,
     * <i>false</i> if incorrect UTF-8 byte (0xC0, 0xC1, 0xF5 .. 0xFF)
     * @see http://tools.ietf.org/html/rfc3629
     * @assert(0x29) === null
     * @assert(0xE2) == 3
     * @assert(0x89) == 0
     * @assert(0xA2) == 0
     * @assert(0xCE) == 2
     * @assert(0xF0) == 4
     * @assert(0xC0) === false
     * @assert(0xC1) === false
     * @assert(0xF5) === false
     * @assert(0xFF) === false
     */
    public static function utf8Leading($char)
    {
        if (($char & 0b10000000) == 0b00000000) {       // ascii byte
            return null;
        } elseif (($char & 0b11000000) == 0b10000000) { // trailing byte
            return 0;
        } elseif ($char == 0xC0 or $char == 0xC1 or $char >= 0xF5) { // incorrect
            return false;
        } elseif (($char & 0b11100000) == 0b11000000) { // leading + 1 byte
            return 2;
        } elseif (($char & 0b11110000) == 0b11100000) { // leading + 2 bytes
            return 3;
        } elseif (($char & 0b11111000) == 0b11110000) { // leading + 3 bytes
            return 4;
        } else {                                        // incorrect
            return false;
        }
    }

    /** whether the byte code corresponds to a utf-8 continuation byte
     * @param int $char
     * @return bool
     * @assert(0xA2) === true
     * @assert(0xE2) === false
     * @assert(0x29) === false
     */
    public static function utf8Trailing($char)
    {
        return ($char & 0b11000000) == 0b10000000;
    }
}
