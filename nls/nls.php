<?php

/**
 * nls parameters management
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Nls;

class Nls
{
    public const FW_DOMAIN = 'dotwheel';

    public const P_NAME = 1;
    public const P_LOCALES = 2;
    public const P_DECIMAL_CHAR = 3;
    public const P_THOUSANDS_CHAR = 4;
    public const P_MON_DECIMAL_CHAR = 5;
    public const P_MON_THOUSANDS_CHAR = 6;
    public const P_MON_CODE = 7;
    public const P_MON_CHAR = 8;
    public const P_MON_FMT = 9;
    public const P_DATE_DT = 10;
    public const P_DATEFULL_DT = 11;
    public const P_DATETIME_DT = 12;
    public const P_DATETIMESEC_DT = 13;
    public const P_DATETIMESEC_FMT = 14;
    public const P_DATEREV_FMT = 15;
    public const P_DATEREV_RE = 16;
    public const P_DATEMON_FMT = 17;
    public const P_DATEMON_SHORT_FMT = 18;
    public const P_LIST_DELIM = 19;
    public const P_LIST_DELIM_HTML = 20;
    public const P_COLON = 21;
    public const P_COLON_HTML = 22;
    public const P_MONTHS = 23;
    public const P_MONTHS_SHORT = 24;
    public const P_WDAYS_SHORT = 25;
    public const P_GMAPS_FMT = 26;
    public const P_DATEPICKER = 27;

    /** @var array $store list of available nls-settings */
    public static array $store = [
        'en' => [
            self::P_NAME => 'English',
            self::P_LOCALES => ['en_US', 'English_United States', 'en'],
            self::P_DECIMAL_CHAR => '.',
            self::P_THOUSANDS_CHAR => ',',
            self::P_MON_DECIMAL_CHAR => '.',
            self::P_MON_THOUSANDS_CHAR => ',',
            self::P_MON_CODE => 'USD',
            self::P_MON_CHAR => '$',
            self::P_MON_FMT => '%2$s %1$s', // value / currency
            self::P_DATE_DT => 'm/d/y',
            self::P_DATEFULL_DT => 'm/d/Y',
            self::P_DATETIME_DT => 'm/d/y H:i',
            self::P_DATETIMESEC_DT => 'm/d/y H:i:s',
            self::P_DATETIMESEC_FMT => '%u/%u/%u %2u:%2u:%2u',
            self::P_DATEREV_FMT => '%3$u-%1$u-%2$u',
            self::P_DATEREV_RE => '$3-$1-$2',
            self::P_DATEMON_FMT => '%s %u', // month / year
            self::P_DATEMON_SHORT_FMT => "%s '%u", // month / year
            self::P_LIST_DELIM => ',',
            self::P_LIST_DELIM_HTML => ',',
            self::P_COLON => ':',
            self::P_COLON_HTML => ':',
            self::P_MONTHS => [
                1 => 'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December',
            ],
            self::P_MONTHS_SHORT => [
                1 => 'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ],
            self::P_WDAYS_SHORT => ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            self::P_GMAPS_FMT => 'http://maps.google.com/?q=%s',
            self::P_DATEPICKER => ['language' => 'en', 'format' => 'dd/mm/yy'],
        ],
        'fr' => [
            self::P_NAME => 'Français',
            self::P_LOCALES => ['fr_FR', 'French_France', 'fr'],
            self::P_DECIMAL_CHAR => ',',
            self::P_THOUSANDS_CHAR => ' ',
            self::P_MON_DECIMAL_CHAR => ',',
            self::P_MON_THOUSANDS_CHAR => ' ',
            self::P_MON_CODE => 'EUR',
            self::P_MON_CHAR => '€',
            self::P_MON_FMT => '%s %s', // value / currency
            self::P_DATE_DT => 'd/m/y',
            self::P_DATEFULL_DT => 'd/m/Y',
            self::P_DATETIME_DT => 'd/m/y H:i',
            self::P_DATETIMESEC_DT => 'd/m/y H:i:s',
            self::P_DATETIMESEC_FMT => '%u/%u/%u %2u:%2u:%2u',
            self::P_DATEREV_FMT => '%3$u-%2$u-%1$u',
            self::P_DATEREV_RE => '$3-$2-$1',
            self::P_DATEMON_FMT => '%s %u', // month / year
            self::P_DATEMON_SHORT_FMT => "%s '%u", // month / year
            self::P_LIST_DELIM => ' ;',
            self::P_LIST_DELIM_HTML => '&nbsp;;',
            self::P_COLON => ' :',
            self::P_COLON_HTML => '&nbsp;:',
            self::P_MONTHS => [
                1 => 'Janvier',
                'Février',
                'Mars',
                'Avril',
                'Mai',
                'Juin',
                'Juillet',
                'Août',
                'Septembre',
                'Octobre',
                'Novembre',
                'Décembre',
            ],
            self::P_MONTHS_SHORT => [
                1 => 'janv.',
                'févr.',
                'mars',
                'avr.',
                'mai',
                'juin',
                'juil.',
                'août',
                'sept.',
                'oct.',
                'nov.',
                'déc.',
            ],
            self::P_WDAYS_SHORT => ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
            self::P_GMAPS_FMT => 'http://maps.google.fr/?q=%s',
            self::P_DATEPICKER => ['language' => 'fr', 'format' => 'dd/mm/yy', 'weekStart' => 1],
        ],
        'ru' => [
            self::P_NAME => 'Русский',
            self::P_LOCALES => ['ru_RU', 'Russian_Russia', 'ru'],
            self::P_DECIMAL_CHAR => ',',
            self::P_THOUSANDS_CHAR => ' ',
            self::P_MON_DECIMAL_CHAR => ',',
            self::P_MON_THOUSANDS_CHAR => ' ',
            self::P_MON_CODE => 'RUR',
            self::P_MON_CHAR => 'R',
            self::P_MON_FMT => '%s %s', // value / currency
            self::P_DATE_DT => 'd.m.y',
            self::P_DATEFULL_DT => 'd.m.Y',
            self::P_DATETIME_DT => 'd.m.y H:i',
            self::P_DATETIMESEC_DT => 'd.m.Y H:i:s',
            self::P_DATETIMESEC_FMT => '%u.%u.%u %2u:%2u:%2u',
            self::P_DATEREV_FMT => '%3$u-%2$u-%1$u',
            self::P_DATEREV_RE => '$3-$2-$1',
            self::P_DATEMON_FMT => '%s %u', // month / year
            self::P_DATEMON_SHORT_FMT => "%s '%u", // month / year
            self::P_LIST_DELIM => ',',
            self::P_LIST_DELIM_HTML => ',',
            self::P_COLON => ':',
            self::P_COLON_HTML => ':',
            self::P_MONTHS => [
                1 => 'Январь',
                'Февраль',
                'Март',
                'Апрель',
                'Май',
                'Июнь',
                'Июль',
                'Август',
                'Сентябрь',
                'Октябрь',
                'Ноябрь',
                'Декабрь',
            ],
            self::P_MONTHS_SHORT => [
                1 => 'янв.',
                'февр.',
                'март',
                'апр.',
                'май',
                'июнь',
                'июль',
                'авг.',
                'сент.',
                'окт.',
                'нояб.',
                'дек.',
            ],
            self::P_WDAYS_SHORT => ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
            self::P_GMAPS_FMT => 'http://maps.google.ru/?q=%s',
            self::P_DATEPICKER => ['language' => 'ru', 'format' => 'dd.mm.yy', 'weekStart' => 1],
        ],
    ];

    /** @var string $lang current language */
    public static string $lang = 'en';

    /** @var array $formats current nls parameters (set from self::$store[self::$list]) */
    public static array $formats = [];

    /** @var string $charset current charset */
    public static string $charset = 'UTF-8';

    /** get the user preferred language code and store it in a cookie. check $_GET and $_COOKIE globals for presence of
     * $cookie_lang entry
     *
     * @param string $cookie_lang cookie variable name to store user language
     * @param array $languages list of languages that the application understands, ex: ['en', 'fr']
     * @param string $default_lang default language if cannot guess from user agent
     * @return string
     */
    public static function getLang(string $cookie_lang, array $languages = [], string $default_lang = 'en'): string
    {
        if (empty($languages)) {
            $languages = array_keys(self::$store);
        }

        // set language from GET...
        if (isset($_GET[$cookie_lang]) and isset(self::$store[$_GET[$cookie_lang]])) {
            $ln = $_GET[$cookie_lang];
            if (empty($_COOKIE[$cookie_lang]) or $_COOKIE[$cookie_lang] != $ln) {
                self::setLang($cookie_lang, $ln);
            }
            return $ln;
        }
        // ...or guess language if cookie empty
        if (empty($_COOKIE[$cookie_lang]) or empty(self::$store[$_COOKIE[$cookie_lang]])) {
            $ln = self::guessLang($languages, $default_lang);
            self::setLang($cookie_lang, $ln);
            return $ln;
        } else {
            // ...or return value from cookie
            return $_COOKIE[$cookie_lang];
        }
    }

    /** store cookie with user language
     *
     * @param string $cookie_lang cookie variable name to store user language
     * @param string $ln language 2-letter code
     */
    public static function setLang(string $cookie_lang, string $ln)
    {
        setcookie($cookie_lang, $ln, $_SERVER['REQUEST_TIME'] + 60 * 60 * 24 * 30, '/');
    }

    /** determine language code from Accept-Language http header (if matches
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
    public static function guessLang($languages, $default_lang = 'en')
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // convert from en-US to en_US form and break on groups by , symbol
            foreach (explode(',', strtr($_SERVER['HTTP_ACCEPT_LANGUAGE'], '-', '_')) as $group) {
                // in each group only take part before ; symbol (if present)
                [$lang] = explode(';', ltrim($group));
                if (array_search($lang, $languages) !== false) {
                    return $lang;
                }
                // if lang does not match as a whole, try to match the first 2 letters of lang
                $ln = substr($lang, 0, 2);
                if (array_search($ln, $languages) !== false) {
                    return $ln;
                }
            }
        }

        return $default_lang;
    }

    /** initialize application and framework locales. select application text domain.
     *
     * @param string $app_domain application locale domain name
     * @param string $app_locale_dir gettext directory containing locale hierarchy
     * @param string $lang 2-letter language code
     * @return string current language
     */
    public static function init(string $app_domain, string $app_locale_dir, string $lang): string
    {
        // Nls configuration
        if (isset(self::$store[$lang])) {
            self::$lang = $lang;
        }
        self::$formats = self::$store[self::$lang];

        Text::binddomain(self::FW_DOMAIN, __DIR__ . '/../locale', self::$lang);
        Text::binddomain($app_domain, $app_locale_dir, self::$lang);

        return self::$lang;
    }

    /** convert date from nls representation to standard format 2012-12-31 23:59:59
     *
     * @param ?string $value the value to convert
     * @param bool $datetime whether to include time
     * @return string|bool date string or false on error
     */
    public static function toDate(?string $value, bool $datetime = false)
    {
        $d1 = $d2 = $d3 = $h = $m = $s = null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}):(\d{2}))?/', $value, $m)) {
            if (isset($m[6])) {
                [, $year, $month, $day, $h, $m, $s] = $m;
            } else {
                [, $year, $month, $day] = $m;
            }
        } else {
            sscanf($value, self::$formats[self::P_DATETIMESEC_FMT], $d1, $d2, $d3, $h, $m, $s);
            [$year, $month, $day] = explode('-', sprintf(self::$formats[self::P_DATEREV_FMT], $d1, $d2, $d3));
        }

        if (empty($year)) {
            $year = date('Y');
        } elseif ($year < 50) {
            $year += 2000;
        } elseif ($year < 100) {
            $year += 1900;
        }

        if (!checkdate($month, $day, $year)) {
            return false;
        } else {
            return $datetime
                ? sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $h, $m, $s)
                : sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    /** convert numeric value to string representation with 2 decimal places and an NLS separator
     *
     * @param float|int $value
     * @return float|string
     */
    public static function asFloat($value)
    {
        $v = round($value, 2);

        return (strpos($v, '.') and self::$formats[self::P_DECIMAL_CHAR] != '.')
            ? str_replace('.', self::$formats[self::P_DECIMAL_CHAR], $v)
            : $v;
    }

    /** convert numeric value to string representation with NLS separators (thousands and decimals)
     *
     * @param float|int $value
     * @return string
     */
    public static function asNumber($value): string
    {
        $v = round($value, 2);

        return number_format(
            $value,
            max(0, strlen(strrchr($v, '.')) - 1),
            self::$formats[self::P_DECIMAL_CHAR],
            self::$formats[self::P_THOUSANDS_CHAR]
        );
    }
}
