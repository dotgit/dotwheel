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
    const P_CONTENT     = 'content';
    const P_HEADERS     = 'headers';
    const P_FILENAME    = 'filename';

    const ERROR_LOG_FILE    = '/tmp/http-errors.log';



    /** creates a redirect URL
     * @param string $page  name of the script including module, like desk/index.php
     * @param array $params hash with parameters to attach
     * @param string $hash  url hash part (omit hash sign)
     * @return string returns a full URL to a specified view embedding the passed parameters to use in a Location header
     */
    public static function getRedirect($page, $params=array(), $hash=null)
    {
        return Request::$root_url.$page.Html::urlArgs('?', $params).(isset($hash) ? "#$hash" : '');
    }

    /** makes an http POST request and returns the response body and headers
     * @param string $url       url of the requested script
     * @param array $data       hash array of request variables
     * @param array $headers    hash array of http headers in the form:
     *  {'Connection':'close'
     *  , 'Host':'www.example.com'
     *  , ...
     *  }
     * @return array            hash array in the form:
     *  {P_HEADERS: ['HTTP/1.1 200 OK', 'Connection: close', ...]
     *  , P_CONTENT: 'html file content'
     *  }
     */
    public static function post($url, $data, $headers=array())
    {
        $data_url = \http_build_query($data);
        $headers += array(
            'Connection'=>'close',
            'Content-Length'=>\strlen($data_url),
            'Content-Type'=>'application/x-www-form-urlencoded; charset="'.Nls::$charset.'"'
        );
        $h = array();
        foreach ($headers as $k=>$v)
            $h[] = "$k: $v\r\n";

        $fgc = \file_get_contents(
            $url,
            false,
            \stream_context_create(array('http'=>array(
                'method'=>'POST',
                'header'=>\implode('', $h),
                'content'=>$data_url,
                'follow_location'=>false
            )))
        );

        if ($fgc === false)
            \error_log($url.' // '.\json_encode($data_url), 3, self::ERROR_LOG_FILE);

        return array(
            self::P_CONTENT=>$fgc ?: null,
            self::P_HEADERS=>isset($http_response_header) ? $http_response_header : null
        );
    }

    /** makes an http POST request with file upload and returns the response body and headers
     * @param string $url   url of the requested script
     * @param array $data   hash array of request variables in the form:
     *  {var_name1: 'value1'
     *  , var_name2: {P_CONTENT: 'file content'
     *      , P_FILENAME: 'document.txt'
     *      , P_HEADERS: {Content-Type: 'text/plain; charset="utf-8"', ...}
     *      }
     *  }
     * @param array $headers    hash array of http headers in the form:
     *  {'Connection':'close'
     *  , 'Host':'www.example.com'
     *  , ...
     *  }
     * @return array hash array in the form:
     *  {P_HEADERS: ['HTTP/1.1 200 OK', 'Connection: close', ...]
     *  , P_CONTENT: 'html file content'
     *  }
     */
    public static function postUpload($url, $data, $headers=array())
    {
        $boundary = \uniqid('', true);
        $parts = array();
        foreach ($data as $name=>$value)
        {
            if (\is_array($value))
            {
                if (isset($value[self::P_HEADERS]))
                {
                    $h = array();
                    foreach ($value[self::P_HEADERS] as $k=>$v)
                        $h[] = "$k: $v\r\n";
                }
                else
                    $h = array();
                $filename = isset($value['filename']) ? "; filename=\"{$value[self::P_FILENAME]}\"" : '';
                $content = $value[self::P_CONTENT];
            }
            else
            {
                $h = array();
                $filename = '';
                $content = $value;
            }

            $parts[] = "--$boundary\r\n".
                "Content-Disposition: form-data; name=\"$name\"$filename\r\n".\implode('', $h)."\r\n".
                $content;
        }
        $data_url = \implode("\r\n", $parts)."\r\n--$boundary--";
        $data_len = \strlen($data_url);
        $headers += array(
            'Connection'=>'close',
            'Content-Length'=>$data_len,
            'Content-Type'=>"multipart/form-data; boundary=$boundary"
        );
        $h = array();
        foreach ($headers as $k=>$v)
            $h[] = "$k: $v\r\n";

        $fgc = \file_get_contents(
            $url,
            false,
            \stream_context_create(array('http'=>array(
                'method'=>'POST',
                'header'=>\implode('', $h),
                'content'=>$data_url,
                'follow_location'=>false
            )))
        );

        if ($fgc === false)
            \error_log($url.' // '.\json_encode($data_url), 3, self::ERROR_LOG_FILE);

        return array(
            self::P_CONTENT=>$fgc ?: null,
            self::P_HEADERS=>isset($http_response_header) ? $http_response_header : null
        );
    }

    /**
     * @return string the ip address of the client (followed by the list of proxies if available)
     *  in the form '12.34.56.78' or '12.34.56.78 XFF 34.56.78.90, 56.78.90.12'
     * @link http://en.wikipedia.org/wiki/X-Forwarded-For
     */
    public static function remoteAddr()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            return "{$_SERVER['REMOTE_ADDR']} XFF {$_SERVER['HTTP_X_FORWARDED_FOR']}";
        else
            return $_SERVER['REMOTE_ADDR'];
    }

    /** prepares a shortened url by using bit.ly online API
     * @param string $url   urlencoded address
     * @return string the shortened url
     */
    public static function shortenUrl($url, $login, $key)
    {
        if ($url_short = @\file_get_contents('http://api.bit.ly/v3/shorten'.
            "?login=$login".
            "&apiKey=$key".
            "&uri=$url".
            '&format=txt'
        ))
        {
            return \urlencode($url_short);
        }
        else
            return $url;
    }
}
