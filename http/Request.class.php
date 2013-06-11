<?php

/**
 * class for storing supplementary request info
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\http;

require_once (__DIR__.'/../util/Nls.class.php');
require_once (__DIR__.'/../util/Params.class.php');

use dotwheel\util\Nls;
use dotwheel\util\Params;

class Request
{
    const OUT_HTML  = 1;
    const OUT_CMD   = 2;
    const OUT_JSON  = 3;
    const OUT_ASIS  = 4;
    const OUT_CLI   = 5;

    const DTL_FILTERS   = 1;
    const DTL_SORT      = 2;
    const DTL_PAGE      = 3;

    const INI_STATIC_URL        = 1;
    const INI_ROOT_LEVEL        = 3;
    const INI_COOKIE_DB         = 4;
    const INI_DATABASES         = 5;
    const INI_DB_DEFAULT        = 6;

    /**
     * @var string  client-oriented path to document root from current
     * controller ('' if the script is in the root (/script.php),
     * '../' if the script is in the second-level subdirectory
     * (/dir/script.php), '../../' for /dir1/dir2/script.php) etc.
     */
    public static $root = '';

    /** @var string url to use on redirecting. like http://localhost/ */
    public static $root_url = '/';

    /** @var string directory to hold application structure */
    public static $app_dir = '/';

    /** @var string the subdirectory of the current module, like 'dir' in /dir/index.php */
    public static $module;

    /** @var string the file name of the current script, like 'index' in /dir/index.php */
    public static $controller;

    /** @var string output mode for request (OUT_HTML, OUT_CMD, OUT_JSON, etc.) */
    public static $output;

    /** @var string next view to redirect on successful command execution, like '/dir/index.php' */
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



    /** initialises the request variables, opens session, initialises nls */
    public static function init($params)
    {
        self::$static_url = Params::extract($params, self::INI_STATIC_URL);
        $root_level = Params::extract($params, self::INI_ROOT_LEVEL);

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
            $level = substr_count($path, '/') - $root_level;
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

        return true;
    }

    /** returns current database connection name
     * @return string
     */
    public static function getDb()
    {
        return (empty($_SESSION[self::$cookie_db])
            or empty(self::$databases[(string)$_SESSION[self::$cookie_db]])
            )
            ? self::$db_default
            : $_SESSION[self::$cookie_db]
            ;
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
