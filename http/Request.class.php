<?php

/**
 * class for storing supplementary request info
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\http;

require_once (__DIR__.'/../util/Params.class.php');

use dotwheel\util\Params;

class Request
{
    const CGI_OUTPUT    = 'o';
    const CGI_NEXT      = 'n';
    const CGI_FILTERS   = 'f';
    const CGI_SORT      = 's';
    const CGI_PAGE      = 'p';

    const SORT_REV_SUFFIX           = '-';
    const SORT_REV_SUFFIX_LENGTH    = 1;

    const OUT_HTML  = 1;
    const OUT_CMD   = 2;
    const OUT_JSON  = 3;
    const OUT_ASIS  = 4;
    const OUT_CLI   = 5;

    const INI_STATIC_URL    = 1;
    const INI_ROOT_LEVEL    = 2;
    const INI_COOKIE_DB     = 3;
    const INI_DATABASES     = 4;
    const INI_DB_DEFAULT    = 5;

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

    /** @var array  list of elements(tables) with corresponding details, like {users:{CGI_SORT:'u_lastname,r'
     *                  , CGI_FILTERS:{u_status:'online',u_lastname:'tref'}
     *                  , CGI_PAGE:2
     *                  }
     *              , roles:...
     *              }
     *              details per table come from the following request parameters:
     *              - s(sort): s[users]=u_lastname,r
     *              - f(filters): f[users][u_status]=online&f[users][u_lastname]=tref
     *              - p(page): p[users]=2
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
            self::$output = (! empty($_REQUEST[self::CGI_OUTPUT]) and $_REQUEST[self::CGI_OUTPUT] == 'a') ? self::OUT_ASIS : self::OUT_JSON;
        elseif (! empty($_REQUEST[self::CGI_OUTPUT]))
            self::$output = $_REQUEST[self::CGI_OUTPUT] == 'a' ? self::OUT_ASIS : ($_REQUEST[self::CGI_OUTPUT] == 'c' ? self::OUT_CMD : self::OUT_JSON);
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
        if (self::$module === 'cmd')
        {
            self::$module = '';
            self::$controller = 'cmd/'.self::$controller;
        }
        elseif ($suffix = strrchr(self::$module, '/')
            and $suffix === '/cmd'
            )
        {
            self::$module = substr(self::$module, 0, -4);
            self::$controller = 'cmd/'.self::$controller;
        }

        // identify $next
        self::$next = (! empty($_REQUEST[self::CGI_NEXT]) && is_scalar($_REQUEST[self::CGI_NEXT]))
            ? ltrim(substr($_REQUEST[self::CGI_NEXT], 0, strspn($_REQUEST[self::CGI_NEXT], 'abcdefghijklmnopqrstuvwxyz_-./', 0, 256)), '/')
            : ''
            ;

        // identify $details
        if (! empty($_REQUEST[self::CGI_FILTERS]) and is_array($_REQUEST[self::CGI_FILTERS]))
        {
            foreach ($_REQUEST[self::CGI_FILTERS] as $el=>$f)
                if (isset(self::$details[self::CGI_FILTERS]))
                    self::$details[self::CGI_FILTERS][$el] = (array)$f;
                else
                    self::$details[self::CGI_FILTERS] = array($el=>(array)$f);
        }
        if (! empty($_REQUEST[self::CGI_SORT]) and is_array($_REQUEST[self::CGI_SORT]))
        {
            foreach ($_REQUEST[self::CGI_SORT] as $el=>$s)
                if (isset(self::$details[self::CGI_SORT]))
                    self::$details[self::CGI_SORT][$el] = (string)$s;
                else
                    self::$details[self::CGI_SORT] = array($el=>(string)$s);
        }
        if (! empty($_REQUEST[self::CGI_PAGE]) and is_array($_REQUEST[self::CGI_PAGE]))
        {
            foreach ($_REQUEST[self::CGI_PAGE] as $el=>$p)
                if ((int)$p)
                {
                    if (isset(self::$details[self::CGI_PAGE]))
                        self::$details[self::CGI_PAGE][$el] = (int)$p;
                    else
                        self::$details[self::CGI_PAGE] = array($el=>(int)$p);
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

    /** returns array with CGI request headers
     * @return array
     */
    public static function getHttpHeaders()
    {
        return array_change_key_case(apache_request_headers(), CASE_LOWER);
    }

    /** check whether $sort_param exists as a key in $sort_cols and return the name and
     * @param string $element_id    element id to search in self::$details
     * @param array $sort_cols      {fld1:true, fld2:true, ...}
     * @param string $sort_default  default sort column, like 'fld1'
     * @return array [{filters}, 'field_name', <i>true</i> if reverse order or <i>false</i> otherwise, page_num]
     */
    public static function translateDetails($element_id, $sort_cols, $sort_default)
    {
        if (empty(self::$details))
            return array(null, $sort_default, false, null);

        $filters = isset(self::$details[self::CGI_FILTERS][$element_id]) ? self::$details[self::CGI_FILTERS][$element_id] : null;

        $sort_fld = isset(self::$details[self::CGI_SORT][$element_id]) ? self::$details[self::CGI_SORT][$element_id] : null;
        if (isset($sort_cols[$sort_fld]))
            $sort_rev = false;
        elseif (isset($sort_cols[substr($sort_fld, 0, -self::SORT_REV_SUFFIX_LENGTH)]))
        {
            $sort_fld = substr($sort_fld, 0, -self::SORT_REV_SUFFIX_LENGTH);
            $sort_rev = true;
        }
        else
        {
            $sort_fld = null;
            $sort_rev = false;
        }

        $page = isset(self::$details[self::CGI_PAGE][$element_id]) ? self::$details[self::CGI_PAGE][$element_id] : null;

        return array($filters, $sort_fld, $sort_rev, $page);
    }
}
