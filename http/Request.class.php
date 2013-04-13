<?php

/**
 * class for storing supplementary request info
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\http;

require_once (__DIR__.'/../db/Db.class.php');
require_once (__DIR__.'/../util/Misc.class.php');
require_once (__DIR__.'/../util/Nls.class.php');

use dotwheel\db\Db;
use dotwheel\util\Misc;
use dotwheel\util\Nls;

class Request
{
    const HST_ROOT_LEVEL    = 1;
    const HST_DB            = 2;

    const OUT_HTML  = 1;
    const OUT_CMD   = 2;
    const OUT_JSON  = 3;
    const OUT_ASIS  = 4;
    const OUT_CLI   = 5;

    const DTL_FILTERS   = 1;
    const DTL_SORT      = 2;
    const DTL_PAGE      = 3;

    const INI_STATIC_URL        = 1;
    const INI_STATIC_PATH_CSS   = 2;
    const INI_STATIC_PATH_JS    = 3;
    const INI_HOSTS             = 4;
    const INI_COOKIE_LANG       = 5;
    const INI_COOKIE_DB         = 6;
    const INI_DATABASES         = 7;
    const INI_DB_DEFAULT        = 8;
    const INI_APP_DIR           = 9;
    const INI_APP_DOMAIN        = 10;

    /**
     * @var string  client-oriented path to document root from current
     * script('' if the script is in the root(/script.php),
     * '../' if the script is in the second-level subdirectory
     * (/dir/script.php), '../../' for /dir1/dir2/script.php)
     */
    public static $root = '';
    /** @var string url to use on redirecting. like http://localhost/ */
    public static $root_url = '/';
    /** @var string the subdirectory of the current module, like desk */
    public static $module;
    /** @var string the file name of the current script, like index.php */
    public static $controller;
    /** @var string output mode for request(OUT_HTML, OUT_CMD, OUT_JSON, etc.) */
    public static $output;
    /** @var string next view to redirect on successful command execution, like desk/index.php */
    public static $next;
    /** @var string list of elements(tables) with corresponding details, like {users:{DTL_SORT:'u_lastname,r'
     *                    , DTL_FILTERS:{u_status:'online',u_lastname:'tref'}
     *                    , DTL_PAGE:2
     *                    }
     *                , roles:...
     *                }
     *                details per table come from the following request parameters:
     *                - s(sort): s[users]=u_lastname,r
     *                - f(filters): f[users][u_status]=online&f[users][u_lastname]=tref
     *                - p(page): p[users]=2
     */
    public static $details = array();
    /** @var string URL of the static ressources directory */
    public static $static_url = '/static';
    /** @var string path to the initial css file in static directory */
    public static $static_path_css = '/css/common.css';
    /** @var string path to the initial js file in static directory */
    public static $static_path_js = '/js/common.js';
    /** @var string list of available databases */
    public static $databases;
    /** @var string database default connection */
    public static $db_default;
    /** @var string cookie name that stores the user database */
    public static $cookie_db;



    /** initialises the Config variables, opens session, initialises nls */
    public static function init($params)
    {
        self::$static_url = Misc::paramExtract($params, self::INI_STATIC_URL);
        self::$static_path_css = Misc::paramExtract($params, self::INI_STATIC_PATH_CSS);
        self::$static_path_js = Misc::paramExtract($params, self::INI_STATIC_PATH_JS);
        self::$db_default = Misc::paramExtract($params, self::INI_DB_DEFAULT);
        self::$databases = Misc::paramExtract($params, self::INI_DATABASES, array());
        self::$cookie_db = Misc::paramExtract($params, self::INI_COOKIE_DB);
        $cookie_lang = Misc::paramExtract($params, self::INI_COOKIE_LANG);
        $hosts = Misc::paramExtract($params, self::INI_HOSTS, array());

        // identify $output
        if (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
            self::$output = (! empty($_REQUEST['o']) and $_REQUEST['o'] == 'a') ? self::OUT_ASIS : self::OUT_JSON;
        elseif (! empty($_REQUEST['o']))
            self::$output = $_REQUEST['o'] == 'a' ? self::OUT_ASIS : ($_REQUEST['o'] == 'c' ? self::OUT_CMD : self::OUT_JSON);
        elseif (isset($_SERVER['REQUEST_METHOD']))
            self::$output = $_SERVER['REQUEST_METHOD'] == 'POST' ? self::OUT_CMD : self::OUT_HTML;
        else
            self::$output = self::OUT_CLI;

        // identify $root, $root_url and $module
        if (isset($_SERVER['SERVER_NAME']))
        {
            if (self::$output != self::OUT_JSON
                or empty($_SERVER['HTTP_REFERER'])
                )
            {
                // for direct requests use SCRIPT_NAME
                $path = $_SERVER['SCRIPT_NAME'];
            }
            else
            {
                // for json requests use HTTP_REFERER
                $path = substr($_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], '/', 8));    // first slash after https://...
                if ($p = strpos($path, '?'))
                    $path = substr($path, 0, $p);
            }
            $level = substr_count($path, '/')
                - (isset($hosts[$_SERVER['SERVER_NAME']])
                    ? $hosts[$_SERVER['SERVER_NAME']][self::HST_ROOT_LEVEL]
                    : 1
                    )
                ;
            self::$root = str_repeat('../', $level);

            $dir = dirname($path);
            $modules = array();
            while ($level--)
            {
                $modules[] = basename($dir);
                $dir = dirname($dir);
            }
            if (DIRECTORY_SEPARATOR == '\\')
                $dir = strtr($dir, '\\', '/');
            self::$root_url = ($_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http')
                . "://{$_SERVER['HTTP_HOST']}$dir"
                . (substr($dir, -1) == '/' ? '' : '/')
                ;
            if ($modules)
                self::$module = implode('/', array_reverse($modules));
        }
        self::$controller = basename($_SERVER['SCRIPT_NAME'], '.php');

        // identify $next
        self::$next = (! empty($_REQUEST['n']) && is_scalar($_REQUEST['n']))
            ? ltrim(substr($_REQUEST['n'], 0, strspn($_REQUEST['n'], 'abcdefghijklmnopqrstuvwxyz_-./', 0, 256)), '/')
            : ''
            ;

        // identify $details
        if (! empty($_REQUEST['f']) and is_array($_REQUEST['f']))
        {
            foreach ($_REQUEST['f'] as $el=>$f)
                if (isset(self::$details[$el]))
                    self::$details[$el][self::DTL_FILTERS] = (array)$f;
                else
                    self::$details[$el] = array(self::DTL_FILTERS=>(array)$f);
        }
        if (! empty($_REQUEST['s']) and is_array($_REQUEST['s']))
        {
            foreach ($_REQUEST['s'] as $el=>$s)
                if (isset(self::$details[$el]))
                    self::$details[$el][self::DTL_SORT] = (string)$s;
                else
                    self::$details[$el] = array(self::DTL_SORT=>(string)$s);
        }
        if (! empty($_REQUEST['p']) and is_array($_REQUEST['p']))
        {
            foreach ($_REQUEST['p'] as $el=>$p)
                if ((int)$p)
                {
                    if (isset(self::$details[$el]))
                        self::$details[$el][self::DTL_PAGE] = (string)$p;
                    else
                        self::$details[$el] = array(self::DTL_PAGE=>(string)$p);
                }
        }

        // open session
        session_start();

        // identify nls parameters from request...
        if (isset($_GET[$cookie_lang]))
        {
            $ln = $_GET[$cookie_lang];
            if (isset(Nls::$store[$ln])
                and(empty($_SESSION[$cookie_lang])
                    or $_SESSION[$cookie_lang] != $ln
                    )
                )
                $_SESSION[$cookie_lang] = $ln;
        }
        // ...if still undefined then guess
        if (empty($_SESSION[$cookie_lang]))
            $_SESSION[$cookie_lang] = Nls::guessLang($cookie_lang);
        Nls::init(Misc::paramExtract($params, self::INI_APP_DOMAIN), Misc::paramExtract($params, self::INI_APP_DIR), $_SESSION[$cookie_lang]);

        // identify db connection name from session or request
        if (empty($_SESSION[self::$cookie_db]))
        {
            if (isset($_COOKIE[self::$cookie_db])
                and isset(self::$databases[$_COOKIE[self::$cookie_db]])
                )
                $_SESSION[self::$cookie_db] = $_COOKIE[self::$cookie_db];
            elseif (isset($_SERVER['SERVER_NAME']) and isset($hosts[$_SERVER['SERVER_NAME']]))
                $_SESSION[self::$cookie_db] = $hosts[$_SERVER['SERVER_NAME']][self::HST_DB];
            else
                $_SESSION[self::$cookie_db] = self::$db_default;
        }

//@DEBUG: overwrite defaults
self::$static_url = self::$root.'static/';

        return true;
    }

    /** returns the specified detail of the specified element or default value
     * @param string $el    element id
     * @param int $detl     DTL_SORT|DTL_FILTER|DTL_PAGE
     * @param mixed $default
     * @return mixed
     */
    public static function getDetails($el, $detl, $default=null)
    {
        return isset(self::$details[$el][$detl])
            ? self::$details[$el][$detl]
            : $default
            ;
    }

    /** @return array {'f':{'tbl1':{'cn_active':1,'cn_postal':'75*'}}
     *                , 's':{'tbl1':'cn_name'}
     *                , 'p':0
     *                }
     */
    public static function getDetailsReversed()
    {
        $f = array();
        $s = array();
        $p = array();
        foreach (self::$details as $tbl=>$detl)
        {
            if (isset($detl[self::DTL_FILTERS]))
                $f[$tbl] = $detl[self::DTL_FILTERS];
            if (isset($detl[self::DTL_SORT]))
                $s[$tbl] = $detl[self::DTL_SORT];
            if (isset($detl[self::DTL_PAGE]))
                $p[$tbl] = $detl[self::DTL_PAGE];
        }
        return array('f'=>$f ? $f : null
            , 's'=>$s ? $s : null
            , 'p'=>$p ? $p : null
            );
    }
}
