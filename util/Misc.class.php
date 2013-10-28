<?php

/**
 * miscellanous functions without special attribution to other classes
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

require_once (__DIR__.'/Nls.class.php');

class Misc
{
    /** converts the proposed size to from K, M or G form to bytes
     * @param string $size_str  string with size representation(128M, 2G etc.)
     * @return int              size in bytes
     */
    public static function convertSize($size_str)
    {
        switch (substr($size_str, -1))
        {
            case 'M': case 'm': return (int)$size_str * 1048576;
            case 'K': case 'k': return (int)$size_str * 1024;
            case 'G': case 'g': return (int)$size_str * 1073741824;
            default: return $size_str;
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
     * @param string $text  bb-style formatted string(recognizes *bold*, /italic/,
     *                      ---header lines---, lines started with dash are bulleted)
     * @return string
     * @see Snippets::preview_txt()
     */
    public static function formatPreview($text)
    {
        return preg_replace(array('#&#', '#<#', '#>#'
                , '#/([^/\r\n]*)/#m', '#\*([^*\\r\\n]*)\*#m'
                , '#^#m', '#$#m'
                , '#^<p>---(.*)---</p>$#m'
                , '#^<p>-(.*)</p>$#m'
                )
            , array('&amp;', '&lt;', '&gt;'
                , '<i>$1</i>', '<b>$1</b>'
                , '<p>', '</p>'
                , '<h5>$1</h5>'
                , '<li>$1</li>'
                )
            , $text
            );
    }

    /** return the formatted tel number or the original string
     * @param string $tel
     * @return string
     */
    public static function formatTel($tel)
    {
        $t = str_replace(array(' ', '.', '-', '(0)'), '', $tel);
        $m = array();
        if (preg_match('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m))
            return "$m[1] $m[2] $m[3] $m[4] $m[5]";
        elseif (preg_match('/^\+?\(?(\d{2})\)?(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m))
            return "+$m[1] $m[2] $m[3] $m[4] $m[5] $m[6]";
        else
            return $tel;
    }

    public static function getMaxUploadSize()
    {
        return min(self::convertSize(ini_get('upload_max_filesize')), self::convertSize(ini_get('post_max_size')));
    }

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
                    if (is_scalar($v))
                        $elements[] = $v;
                    elseif (($v = self::joinWs($v)) !== null)
                        $elements[] = $v;
                }
            }
            else
                $splitter = $v;
        }
        if (is_scalar($splitter))
            return $elements ? implode($splitter, $elements) : null;
        elseif ($elements)
            return $splitter[0].implode($splitter[1], $elements).$splitter[2];
        else
            return null;
    }

    /** sets session cookie ttl and starts the session or regenerates session id
     * if session already open
     * @param int $ttl  new time to live in seconds
     */
    public static function sessionSetTtl($ttl)
    {
        session_set_cookie_params($ttl);
        if (session_status() == PHP_SESSION_NONE)
            session_start();
        else
            session_regenerate_id(true);
    }

    /** escapes a string to be used in sprintf by doubling the % characters
     * @param string $str   string to escape
     * @return string
     */
    public static function sprintfEscape($str)
    {
        return str_replace('%', '%%', $str);
    }

    /** trims string to the specified length by word boundary adding suffix
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     */
    public static function trimWord($str, $len=100, $suffix='...')
    {
        return mb_substr($str
            , 0
            , $len - mb_strlen(mb_strrchr(mb_substr($str, 0, $len, Nls::$charset), ' ', false, Nls::$charset), Nls::$charset)
            , Nls::$charset
            ).$suffix
            ;
    }
}
