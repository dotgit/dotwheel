<?php

/**
 * make post http requests using streams, shortening urls with external services etc.
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Http;

use Dotwheel\Nls\Nls;
use Dotwheel\Ui\Html;

class Http
{
    public const P_CONTENT = 'content';
    public const P_HEADERS = 'headers';
    public const P_FILENAME = 'filename';

    public const ERROR_LOG_FILE = '/tmp/http-errors.log';


    /** create a redirect URL
     *
     * @param ?string $page name of the script including module, like desk/index.php
     * @param array $params hash with parameters to attach
     * @param string|null $hash url hash part (omit hash sign)
     * @return string a full URL to specified view embedding the passed parameters to use in a Location header
     */
    public static function getRedirect(?string $page, array $params = [], ?string $hash = null): string
    {
        return Request::$root_url . $page . Html::urlArgs('?', $params) . (isset($hash) ? "#$hash" : '');
    }

    /** make an http POST request and return the response body and headers
     *
     * @param ?string $url url of the requested script
     * @param array $data hash array of request variables
     * @param array $headers hash array of http headers in the form:
     *  {'Connection':'close'
     *  , 'Host':'www.example.com'
     *  , ...
     *  }
     * @return array            hash array in the form:
     *  {P_HEADERS: ['HTTP/1.1 200 OK', 'Connection: close', ...]
     *  , P_CONTENT: 'html file content'
     *  }
     */
    public static function post(?string $url, array $data, array $headers = []): array
    {
        $data_url = http_build_query($data);
        $headers += [
            'Connection' => 'close',
            'Content-Length' => strlen($data_url),
            'Content-Type' => 'application/x-www-form-urlencoded; charset="' . Nls::$charset . '"',
        ];
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = "$k: $v\r\n";
        }

        $fgc = file_get_contents(
            $url,
            false,
            stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode('', $h),
                    'content' => $data_url,
                    'follow_location' => false,
                ],
            ])
        );

        if ($fgc === false) {
            error_log($url . ' // ' . json_encode($data_url), 3, self::ERROR_LOG_FILE);
        }

        return [
            self::P_CONTENT => $fgc ?: null,
            self::P_HEADERS => $http_response_header ?? null,
        ];
    }

    /** make an http POST request with file upload and return the response body and headers
     *
     * @param ?string $url url of the requested script
     * @param array $data hash array of request variables in the form:
     *  {var_name1: 'value1'
     *  , var_name2: {P_CONTENT: 'file content'
     *      , P_FILENAME: 'document.txt'
     *      , P_HEADERS: {Content-Type: 'text/plain; charset="utf-8"', ...}
     *      }
     *  }
     * @param array $headers hash array of http headers in the form:
     *  {'Connection':'close'
     *  , 'Host':'www.example.com'
     *  , ...
     *  }
     * @return array hash array in the form:
     *  {P_HEADERS: ['HTTP/1.1 200 OK', 'Connection: close', ...]
     *  , P_CONTENT: 'html file content'
     *  }
     */
    public static function postUpload(?string $url, array $data, array $headers = []): array
    {
        $boundary = uniqid('', true);
        $parts = [];
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                if (isset($value[self::P_HEADERS])) {
                    $h = [];
                    foreach ($value[self::P_HEADERS] as $k => $v) {
                        $h[] = "$k: $v\r\n";
                    }
                } else {
                    $h = [];
                }
                $filename = isset($value['filename']) ? "; filename=\"{$value[self::P_FILENAME]}\"" : '';
                $content = $value[self::P_CONTENT];
            } else {
                $h = [];
                $filename = '';
                $content = $value;
            }

            $parts[] = "--$boundary\r\n" .
                "Content-Disposition: form-data; name=\"$name\"$filename\r\n" . implode('', $h) . "\r\n" .
                $content;
        }
        $data_url = implode("\r\n", $parts) . "\r\n--$boundary--";
        $data_len = strlen($data_url);
        $headers += [
            'Connection' => 'close',
            'Content-Length' => $data_len,
            'Content-Type' => "multipart/form-data; boundary=$boundary",
        ];
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = "$k: $v\r\n";
        }

        $fgc = file_get_contents(
            $url,
            false,
            stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode('', $h),
                    'content' => $data_url,
                    'follow_location' => false,
                ],
            ])
        );

        if ($fgc === false) {
            error_log($url . ' // ' . json_encode($data_url), 3, self::ERROR_LOG_FILE);
        }

        return [
            self::P_CONTENT => $fgc ?: null,
            self::P_HEADERS => $http_response_header ?? null,
        ];
    }

    /**
     * @return string IP address of the client (followed by the list of proxies if available)
     *  in the form '12.34.56.78' or '12.34.56.78 XFF 34.56.78.90, 56.78.90.12'
     * @link http://en.wikipedia.org/wiki/X-Forwarded-For
     */
    public static function remoteAddr(): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return "{$_SERVER['REMOTE_ADDR']} XFF {$_SERVER['HTTP_X_FORWARDED_FOR']}";
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }

    /** prepare a shortened url by using bit.ly online API
     *
     * @param ?string $url urlencoded address
     * @param ?string $login bitly login
     * @param ?string $key bitly key
     * @return string the shortened url
     */
    public static function shortenUrl(?string $url, string $login, string $key): string
    {
        if ($url_short = @file_get_contents(
            'http://api.bit.ly/v3/shorten' .
            "?login=$login" .
            "&apiKey=$key" .
            "&uri=$url" .
            '&format=txt'
        )) {
            return urlencode($url_short);
        } else {
            return $url ?: '';
        }
    }
}
