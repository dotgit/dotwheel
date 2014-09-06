<?php

/**
 * class for storing supplementary request info
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Http;

use Dotwheel\Util\Params;

class Request
{
    const CGI_OUTPUT    = 'o';
    const CGI_NEXT      = 'n';

    const CO_CMD    = 'c';
    const CO_JSON   = 'j';
    const CO_ASIS   = 'a';

    const OUT_HTML  = 1;
    const OUT_CMD   = 2;
    const OUT_JSON  = 3;
    const OUT_ASIS  = 4;
    const OUT_CLI   = 5;

    const INI_ROOT          = 1;
    const INI_ROOT_URL      = 2;

    /** @var string output mode for request (OUT_HTML, OUT_CMD, OUT_JSON, etc.) */
    public static $output;

    /**
     * @var string  client-oriented path to document root from current
     * controller ('' if the script is in the root (/script.php),
     * '../' if the script is in the second-level subdirectory
     * (/dir/script.php), '../../' for /dir1/dir2/script.php) etc.
     */
    public static $root;

    /** @var string url to use on redirecting. like http://localhost/ */
    public static $root_url;

    /** @var string next view to redirect on successful command execution, like '/dir/index.php' */
    public static $next;



    /** sets request $output variable based on CGI mode and input parameters
     * @return int current output mode
     */
    public static function initOutputMode()
    {
        // identify $output
        if (isset($_REQUEST[self::CGI_OUTPUT]))
        {
            switch ($_REQUEST[self::CGI_OUTPUT])
            {
                case self::CO_CMD: self::$output = self::OUT_CMD; break;
                case self::CO_JSON: self::$output = self::OUT_JSON; break;
                case self::CO_ASIS: self::$output = self::OUT_ASIS; break;
                default: self::$output = self::OUT_HTML;
            }
        }
        elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
            self::$output = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
                ? self::OUT_JSON
                : self::OUT_ASIS;
        elseif (isset($_SERVER['REQUEST_METHOD']))
            self::$output = $_SERVER['REQUEST_METHOD'] == 'POST'
                ? self::OUT_CMD
                : self::OUT_HTML;
        else
            self::$output = self::OUT_CLI;

        return self::$output;
    }

    /** initializes the request variables: $root, $root_url, $next, $details */
    public static function init($params)
    {
        self::$root = Params::extract($params, self::INI_ROOT);
        self::$root_url = Params::extract($params, self::INI_ROOT_URL);

        // check output mode is set
        if (empty(self::$output))
            self::initOutputMode();

        // identify $next
        self::$next = (! empty($_REQUEST[self::CGI_NEXT]) && \is_scalar($_REQUEST[self::CGI_NEXT]))
            ? \ltrim(\substr(
                $_REQUEST[self::CGI_NEXT],
                0,
                \strspn($_REQUEST[self::CGI_NEXT], 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ/_-.', 0, 256)
            ), '/')
            : '';

        return true;
    }

    /** returns array with CGI request headers
     * @return array
     */
    public static function getHttpHeaders()
    {
        return \array_change_key_case(\apache_request_headers() + array('remote-addr'=>Http::remoteAddr()), \CASE_LOWER);
    }
}
