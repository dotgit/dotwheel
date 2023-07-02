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
    public const CGI_OUTPUT = 'o';
    public const CGI_NEXT = 'n';

    public const CO_CMD = 'c';
    public const CO_JSON = 'j';
    public const CO_ASIS = 'a';

    public const OUT_HTML = 1;
    public const OUT_CMD = 2;
    public const OUT_JSON = 3;
    public const OUT_ASIS = 4;
    public const OUT_CLI = 5;

    public const INI_ROOT = 1;
    public const INI_ROOT_URL = 2;

    /** @var string output mode for request (OUT_HTML, OUT_CMD, OUT_JSON, etc.) */
    public static string $output;

    /**
     * @var string  client-oriented path to document root from current
     * controller ('' if the script is in the root (/script.php),
     * '../' if the script is in the second-level subdirectory
     * (/dir/script.php), '../../' for /dir1/dir2/script.php) etc.
     */
    public static string $root;

    /** @var string url to use on redirecting. like http://localhost/ */
    public static string $root_url;

    /** @var string next view to redirect on successful command execution, like '/dir/index.php' */
    public static string $next;


    /** set request $output variable based on CGI mode and input parameters
     *
     * @return int current output mode
     */
    public static function initOutputMode()
    {
        // identify $output
        if (isset($_REQUEST[self::CGI_OUTPUT])) {
            switch ($_REQUEST[self::CGI_OUTPUT]) {
                case self::CO_CMD:
                    self::$output = self::OUT_CMD;
                    break;
                case self::CO_JSON:
                    self::$output = self::OUT_JSON;
                    break;
                case self::CO_ASIS:
                    self::$output = self::OUT_ASIS;
                    break;
                default:
                    self::$output = self::OUT_HTML;
            }
        } elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            self::$output = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
                ? self::OUT_JSON
                : self::OUT_ASIS;
        } elseif (isset($_SERVER['REQUEST_METHOD'])) {
            self::$output = $_SERVER['REQUEST_METHOD'] == 'POST'
                ? self::OUT_CMD
                : self::OUT_HTML;
        } else {
            self::$output = self::OUT_CLI;
        }

        return self::$output;
    }

    /** initialize the request variables: $root, $root_url, $next, $details
     *
     * @param array $params
     * @return true
     */
    public static function init(array $params): bool
    {
        self::$root = Params::extract($params, self::INI_ROOT);
        self::$root_url = Params::extract($params, self::INI_ROOT_URL);

        // check output mode is set
        if (empty(self::$output)) {
            self::initOutputMode();
        }

        // identify $next
        self::$next = (!empty($_REQUEST[self::CGI_NEXT]) && is_scalar($_REQUEST[self::CGI_NEXT]))
            ? ltrim(
                substr(
                    $_REQUEST[self::CGI_NEXT],
                    0,
                    strspn(
                        $_REQUEST[self::CGI_NEXT],
                        'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ/_-.',
                        0,
                        256
                    )
                ),
                '/'
            )
            : '';

        return true;
    }

    /** return array with CGI request headers
     *
     * @return array
     */
    public static function getHttpHeaders(): array
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            foreach ($_SERVER as $k => $v) {
                if (strncmp($k, 'HTTP_', 5) == 0) {
                    $headers[strtr(substr($k, 5), '_', '-')] = $v;
                }
            }
        }

        return array_change_key_case(
            $headers + ['remote-addr' => Http::remoteAddr()],
            CASE_LOWER
        );
    }
}
