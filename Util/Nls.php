<?php

/**
 * nls parameters management
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class Nls
{
    const LANG_DEFAULT = 'fr';
    const FW_DOMAIN = 'dotwheel';

    const P_NAME                = 1;
    const P_LOCALES             = 2;
    const P_DECIMAL_CHAR        = 3;
    const P_THOUSANDS_CHAR      = 4;
    const P_MON_DECIMAL_CHAR    = 5;
    const P_MON_THOUSANDS_CHAR  = 6;
    const P_MON_CODE            = 7;
    const P_MON_CHAR            = 8;
    const P_MON_FMT             = 9;
    const P_DATE_DT             = 10;
    const P_DATEFULL_DT         = 11;
    const P_DATETIME_DT         = 12;
    const P_DATETIMESEC_DT      = 13;
    const P_DATETIMESEC_FMT     = 14;
    const P_DATEREV_FMT         = 15;
    const P_DATEREV_RE          = 16;
    const P_LIST_DELIM          = 17;
    const P_LIST_DELIM_HTML     = 18;
    const P_COLON               = 19;
    const P_COLON_HTML          = 20;
    const P_GMAPS_FMT           = 21;
    const P_DATEPICKER          = 22;

    /** @var array $store list of available nls-settings */
    public static $store = array('en'=>array(self::P_NAME=>'English'
            , self::P_LOCALES=>array('en_US', 'English_United States', 'en')
            , self::P_DECIMAL_CHAR=>'.'
            , self::P_THOUSANDS_CHAR=>','
            , self::P_MON_DECIMAL_CHAR=>'.'
            , self::P_MON_THOUSANDS_CHAR=>','
            , self::P_MON_CODE=>'USD'
            , self::P_MON_CHAR=>'$'
            , self::P_MON_FMT=>'$%s'
            , self::P_DATE_DT=>'m/d/y'
            , self::P_DATEFULL_DT=>'m/d/Y'
            , self::P_DATETIME_DT=>'m/d/y H:i'
            , self::P_DATETIMESEC_DT=>'m/d/y H:i:s'
            , self::P_DATETIMESEC_FMT=>'%u/%u/%u %2u:%2u:%2u'
            , self::P_DATEREV_FMT=>'%3$u-%1$u-%2$u'
            , self::P_DATEREV_RE=>'$3-$1-$2'
            , self::P_LIST_DELIM=>','
            , self::P_LIST_DELIM_HTML=>','
            , self::P_COLON=>':'
            , self::P_COLON_HTML=>':'
            , self::P_GMAPS_FMT=>'http://maps.google.com/?q=%s'
            , self::P_DATEPICKER=>array('language'=>'en')
            )
        , 'fr'=>array(self::P_NAME=>'Français'
            , self::P_LOCALES=>array('fr_FR', 'French_France', 'fr')
            , self::P_DECIMAL_CHAR=>','
            , self::P_THOUSANDS_CHAR=>' '
            , self::P_MON_DECIMAL_CHAR=>','
            , self::P_MON_THOUSANDS_CHAR=>' '
            , self::P_MON_CODE=>'EUR'
            , self::P_MON_CHAR=>'€'
            , self::P_MON_FMT=>'%s€'
            , self::P_DATE_DT=>'d/m/y'
            , self::P_DATEFULL_DT=>'d/m/Y'
            , self::P_DATETIME_DT=>'d/m/y H:i'
            , self::P_DATETIMESEC_DT=>'d/m/y H:i:s'
            , self::P_DATETIMESEC_FMT=>'%u/%u/%u %2u:%2u:%2u'
            , self::P_DATEREV_FMT=>'%3$u-%2$u-%1$u'
            , self::P_DATEREV_RE=>'$3-$2-$1'
            , self::P_LIST_DELIM=>' ;'
            , self::P_LIST_DELIM_HTML=>'&nbsp;;'
            , self::P_COLON=>' :'
            , self::P_COLON_HTML=>'&nbsp;:'
            , self::P_GMAPS_FMT=>'http://maps.google.fr/?q=%s'
            , self::P_DATEPICKER=>array('language'=>'fr'
                , 'format'=>'dd/mm/yy'
                , 'weekStart'=>1
                )
            )
        , 'ru'=>array(self::P_NAME=>'Русский'
            , self::P_LOCALES=>array('ru_RU', 'Russian_Russia', 'ru')
            , self::P_DECIMAL_CHAR=>','
            , self::P_THOUSANDS_CHAR=>' '
            , self::P_MON_DECIMAL_CHAR=>','
            , self::P_MON_THOUSANDS_CHAR=>' '
            , self::P_MON_CODE=>'RUR'
            , self::P_MON_CHAR=>'R'
            , self::P_MON_FMT=>'%sR'
            , self::P_DATE_DT=>'d.m.y'
            , self::P_DATEFULL_DT=>'d.m.Y'
            , self::P_DATETIME_DT=>'d.m.y H:i'
            , self::P_DATETIMESEC_DT=>'d.m.Y H:i:s'
            , self::P_DATETIMESEC_FMT=>'%u.%u.%u %2u:%2u:%2u'
            , self::P_DATEREV_FMT=>'%3$u-%2$u-%1$u'
            , self::P_DATEREV_RE=>'$3-$2-$1'
            , self::P_LIST_DELIM=>','
            , self::P_LIST_DELIM_HTML=>','
            , self::P_COLON=>':'
            , self::P_COLON_HTML=>':'
            , self::P_GMAPS_FMT=>'http://maps.google.ru/?q=%s'
            , self::P_DATEPICKER=>array('language'=>'ru'
                , 'format'=>'dd.mm.yy'
                , 'weekStart'=>1
                )
            )
        );

    /** @var string $lang current language */
    public static $lang = self::LANG_DEFAULT;

    /** @var array $formats current nls parameters (set from self::$store[self::$list]) */
    public static $formats = array();

    /** @var string $charset current charset */
    public static $charset = 'UTF-8';



    /** gets the user preferred language code and stores it in a cookie
     * @param string $cookie_lang language cookie variable name
     */
    public static function getLang($cookie_lang)
    {
        // set language from GET...
        if (isset($_GET[$cookie_lang])
            and isset(self::$store[$_GET[$cookie_lang]])
            )
        {
            $ln = $_GET[$cookie_lang];
            if (empty($_COOKIE[$cookie_lang])
                or $_COOKIE[$cookie_lang] != $ln
                )
                \setcookie($cookie_lang, $ln, $_SERVER['REQUEST_TIME'] + 60*60*24*30, '/');
            return $ln;
        }
        // ...or guess language if cookie empty
        if (empty($_COOKIE[$cookie_lang]))
        {
            $ln = self::guessLang();
            \setcookie($cookie_lang, $ln, $_SERVER['REQUEST_TIME'] + 60*60*24*30, '/');
            return $ln;
        }
        // ...or return value from cookie
        else
            return $_COOKIE[$cookie_lang];

    }

    /** determines language code from Accept-Language http header (if matches
     * available languages) or self::LANG_DEFAULT otherwise
     * @return string
     */
    public static function guessLang()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            foreach (\explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lng)
            {
                $ln = \substr(\ltrim($lng), 0, 2);
                if (isset(self::$store[$ln]))
                    return $ln;
            }
        }

        return self::LANG_DEFAULT;
    }

    /** initializes application and framework locales
     * @param string $app_domain application locale domain name
     * @param string $app_dir gettext directory containing /locale/... hierarchy
     * @param string $ln 2-letter language code
     */
    public static function init($app_domain, $app_dir, $ln)
    {
        // Nls configuration
        if ($ln != self::$lang and isset(self::$store[$ln]))
            self::$lang = $ln;
        self::$formats = self::$store[self::$lang];

        // gettext configuration
        \putenv('LANGUAGE='.self::$lang);
        \bindtextdomain(self::FW_DOMAIN, __DIR__.'/../Locale');
        \bind_textdomain_codeset(self::FW_DOMAIN, self::$charset);
        \bindtextdomain($app_domain, $app_dir);
        \bind_textdomain_codeset($app_domain, self::$charset);
        \textdomain($app_domain);

        return self::$lang;
    }

    /** returns the date representation in standard format 2012-12-31 23:59:59
     * @param string $value     the value to convert
     * @param boolean $datetime whether to include time
     * @return string|boolean date string or false on error
     */
    public static function asDate($value, $datetime=null)
    {
        $d1 = $d2 = $d3 = $h = $m = $s = null;
        \sscanf($value, self::$formats[self::P_DATETIMESEC_FMT], $d1, $d2, $d3, $h, $m, $s);
        list($year, $month, $day) = \explode('-', \sprintf(self::$formats[self::P_DATEREV_FMT], $d1, $d2, $d3));
        if (empty($year))
            $year = \date('Y');
        elseif ($year < 50)
            $year += 2000;
        elseif ($year < 100)
            $year += 1900;

        if (! \checkdate($month, $day, $year))
            return false;
        else
            return $datetime
                ? \sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $h, $m, $s)
                : \sprintf('%04d-%02d-%02d', $year, $month, $day)
                ;
    }
}
