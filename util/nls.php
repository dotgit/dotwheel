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
    const P_WDAYS_SHORT         = 21;
    const P_GMAPS_FMT           = 22;
    const P_DATEPICKER          = 23;

    /** @var array $store list of available nls-settings */
    public static $store = array(
        'en'=>array(
            self::P_NAME=>'English',
            self::P_LOCALES=>array('en_US', 'English_United States', 'en'),
            self::P_DECIMAL_CHAR=>'.',
            self::P_THOUSANDS_CHAR=>',',
            self::P_MON_DECIMAL_CHAR=>'.',
            self::P_MON_THOUSANDS_CHAR=>',',
            self::P_MON_CODE=>'USD',
            self::P_MON_CHAR=>'$',
            self::P_MON_FMT=>'$%s',
            self::P_DATE_DT=>'m/d/y',
            self::P_DATEFULL_DT=>'m/d/Y',
            self::P_DATETIME_DT=>'m/d/y H:i',
            self::P_DATETIMESEC_DT=>'m/d/y H:i:s',
            self::P_DATETIMESEC_FMT=>'%u/%u/%u %2u:%2u:%2u',
            self::P_DATEREV_FMT=>'%3$u-%1$u-%2$u',
            self::P_DATEREV_RE=>'$3-$1-$2',
            self::P_LIST_DELIM=>',',
            self::P_LIST_DELIM_HTML=>',',
            self::P_COLON=>':',
            self::P_COLON_HTML=>':',
            self::P_WDAYS_SHORT=>array('Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'),
            self::P_GMAPS_FMT=>'http://maps.google.com/?q=%s',
            self::P_DATEPICKER=>array('language'=>'en')
        ),
        'fr'=>array(
            self::P_NAME=>'Français',
            self::P_LOCALES=>array('fr_FR', 'French_France', 'fr'),
            self::P_DECIMAL_CHAR=>',',
            self::P_THOUSANDS_CHAR=>' ',
            self::P_MON_DECIMAL_CHAR=>',',
            self::P_MON_THOUSANDS_CHAR=>' ',
            self::P_MON_CODE=>'EUR',
            self::P_MON_CHAR=>'€',
            self::P_MON_FMT=>'%s€',
            self::P_DATE_DT=>'d/m/y',
            self::P_DATEFULL_DT=>'d/m/Y',
            self::P_DATETIME_DT=>'d/m/y H:i',
            self::P_DATETIMESEC_DT=>'d/m/y H:i:s',
            self::P_DATETIMESEC_FMT=>'%u/%u/%u %2u:%2u:%2u',
            self::P_DATEREV_FMT=>'%3$u-%2$u-%1$u',
            self::P_DATEREV_RE=>'$3-$2-$1',
            self::P_LIST_DELIM=>' ;',
            self::P_LIST_DELIM_HTML=>'&nbsp;;',
            self::P_COLON=>' :',
            self::P_COLON_HTML=>'&nbsp;:',
            self::P_WDAYS_SHORT=>array('Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'),
            self::P_GMAPS_FMT=>'http://maps.google.fr/?q=%s',
            self::P_DATEPICKER=>array('language'=>'fr', 'format'=>'dd/mm/yy', 'weekStart'=>1),
        ),
        'ru'=>array(
            self::P_NAME=>'Русский',
            self::P_LOCALES=>array('ru_RU', 'Russian_Russia', 'ru'),
            self::P_DECIMAL_CHAR=>',',
            self::P_THOUSANDS_CHAR=>' ',
            self::P_MON_DECIMAL_CHAR=>',',
            self::P_MON_THOUSANDS_CHAR=>' ',
            self::P_MON_CODE=>'RUR',
            self::P_MON_CHAR=>'R',
            self::P_MON_FMT=>'%sR',
            self::P_DATE_DT=>'d.m.y',
            self::P_DATEFULL_DT=>'d.m.Y',
            self::P_DATETIME_DT=>'d.m.y H:i',
            self::P_DATETIMESEC_DT=>'d.m.Y H:i:s',
            self::P_DATETIMESEC_FMT=>'%u.%u.%u %2u:%2u:%2u',
            self::P_DATEREV_FMT=>'%3$u-%2$u-%1$u',
            self::P_DATEREV_RE=>'$3-$2-$1',
            self::P_LIST_DELIM=>',',
            self::P_LIST_DELIM_HTML=>',',
            self::P_COLON=>':',
            self::P_COLON_HTML=>':',
            self::P_WDAYS_SHORT=>array('Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'),
            self::P_GMAPS_FMT=>'http://maps.google.ru/?q=%s',
            self::P_DATEPICKER=>array('language'=>'ru', 'format'=>'dd.mm.yy', 'weekStart'=>1),
        )
    );

    /** @var string $lang current language */
    public static $lang;

    /** @var array $formats current nls parameters (set from self::$store[self::$list]) */
    public static $formats = array();

    /** @var string $charset current charset */
    public static $charset = 'UTF-8';



    /** gets the user preferred language code and stores it in a cookie
     * @param string $cookie_lang language cookie variable name
     * @param array $languages list of languages that the application understands, ex: ['en', 'fr']
     * @param string $default_lang default language if cannot guess from user agent
     */
    public static function getLang($cookie_lang, $languages=array(), $default_lang='en')
    {
        if (empty($languages))
            $languages = \array_keys(self::$store);

        // set language from GET...
        if (isset($_GET[$cookie_lang]) and isset(self::$store[$_GET[$cookie_lang]]))
        {
            $ln = $_GET[$cookie_lang];
            if (empty($_COOKIE[$cookie_lang]) or $_COOKIE[$cookie_lang] != $ln)
                \setcookie($cookie_lang, $ln, $_SERVER['REQUEST_TIME'] + 60*60*24*30, '/');

            return $ln;
        }
        // ...or guess language if cookie empty
        if (empty($_COOKIE[$cookie_lang]) or empty(self::$store[$_COOKIE[$cookie_lang]]))
        {
            $ln = self::guessLang($languages, $default_lang);
            \setcookie($cookie_lang, $ln, $_SERVER['REQUEST_TIME'] + 60*60*24*30, '/');

            return $ln;
        }
        // ...or return value from cookie
        else
            return $_COOKIE[$cookie_lang];
    }

    /** determines language code from Accept-Language http header (if matches
     * available languages) or set as $default_lang otherwise.
     * <pre>Accept-Language: fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4,ru;q=0.2</pre>
     * convert from <code>en-US</code> to <code>en_US</code> form and
     * process comma-separated language groups from left to right. in each
     * group only take part before semicolon. if the part does not match, try
     * to reduce it to first two letters and check again. if does not match
     * move to the next group. if no more groups, return
     * <code>$default_lang</code>.
     * @param array $languages list of languages that the application understands, ex: ['en', 'fr']
     * @param string $default_lang default language if cannot guess from user agent
     * @return string
     */
    public static function guessLang($languages, $default_lang='en')
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            // convert from en-US to en_US form and break on groups by , symbol
            foreach (\explode(',', \strtr($_SERVER['HTTP_ACCEPT_LANGUAGE'], '-', '_')) as $group)
            {
                // in each group only take part before ; symbol (if present)
                list($lang) = \explode(';', \ltrim($group));
                if (\array_search($lang, $languages) !== false)
                    return $lang;
                // if lang does not match as a whole, try to match the first 2 letters of lang
                $ln = \substr($lang, 0, 2);
                if (\array_search($ln, $languages) !== false)
                    return $ln;
            }
        }

        return $default_lang;
    }

    /** initializes application and framework locales. selects application text domain.
     * translations must be placed in {$app_locale_dir}/{$ln}/en/LC_MESSAGES/ directory.
     * English translation in ./locale/en/en/LC_MESSAGES/domain.mo,
     * French translation in ./locale/fr/en/LC_MESSAGES/domain.mo, etc.
     * gettext is always passed an english locale, environment variables are set to the
     * same value and do not change between requests
     * @param string $app_domain        application locale domain name
     * @param string $app_locale_dir    gettext directory containing locale hierarchy
     * @param string $lang              2-letter language code
     */
    public static function init($app_domain, $app_locale_dir, $lang)
    {
        // Nls configuration
        if ($lang != self::$lang and isset(self::$store[$lang]))
            self::$lang = $lang;
        self::$formats = self::$store[self::$lang];

        // gettext configuration
        \putenv('LC_MESSAGES=en');
        \bindtextdomain(self::FW_DOMAIN, __DIR__.'/../locale/'.self::$lang);
        \bind_textdomain_codeset(self::FW_DOMAIN, self::$charset);
        \bindtextdomain($app_domain, $app_locale_dir.'/'.self::$lang);
        \bind_textdomain_codeset($app_domain, self::$charset);
        \textdomain($app_domain);

        return self::$lang;
    }

    /** converts date from nls representation to standard format 2012-12-31 23:59:59
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
                : \sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
