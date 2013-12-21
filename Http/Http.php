<?php

/**
 * make post http requests using streams, shortening urls with external services etc.
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Http;

use Dotwheel\Ui\Html;
use Dotwheel\Util\Cache;
use Dotwheel\Util\Nls;

class Http
{
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

    /**
     * make an http POST request and return the response body and headers
     * @param string $url       url of the requested script
     * @param array $data       hash array of request variables
     * @param array $headers    hash array of http headers in the form:
     *  {Connection: 'close', Host: 'www.example.com', ...}
     * @return array            hash array in the form:
     *  {headers: ['HTTP/1.1 200 OK', 'Connection: close', ...], content: '<html></html>'}
     */
    public static function post($url, $data, $headers=array())
    {
        $data_url = \http_build_query($data);
        $data_len = \strlen($data_url);
        $headers += array('Connection'=>'close'
            , 'Content-Length'=>$data_len
            , 'Content-Type'=>'application/x-www-form-urlencoded; charset="'.Nls::$charset.'"'
            );
        \array_walk($headers, function(&$v, $k){$v = "$k: $v";});

        $fgc = \file_get_contents($url
            , false
            , stream_context_create(array('http'=>array('method'=>'POST'
                , 'header'=>\implode("\r\n", $headers)
                , 'content'=>$data_url
                , 'follow_location'=>false
                )))
            );

        if ($fgc === false)
            \error_log(\print_r($data_url, true), 3, '/tmp/http_post_upload_errors.txt');

        return array('content'=>$fgc ?: null
            , 'headers'=>isset($http_response_header) ? $http_response_header : null
            );
    }

    /** make an http POST request with file upload and returns the response body and headers
     * @param string $url   url of the requested script
     * @param array $data   hash array of request variables in the form:
     *  {var_name1: 'value1'
     *      , var_name2: {content: 'file content'
     *          , filename: 'document.txt'
     *          , headers: {Content-Type: 'text/plain; charset="utf-8"', ...}
     *          }
     *      }
     * @param array $headers    hash array of http headers in the form:
     *  {Connection: 'close', Host: 'www.example.com', ...}
     * @return array            hash array in the form:
     *  {headers: ['HTTP/1.1 200 OK', 'Connection: close', ...], content: '<html></html>'}
     */
    public static function postUpload($url, $data, $headers=array())
    {
        $boundary = \uniqid('', true);
        $parts = array();
        foreach ($data as $name=>$value)
        {
            if (\is_array($value))
            {
                if (isset($value['headers']))
                {
                    $h = $value['headers'];
                    \array_walk($h, function(&$v, $k){$v = "$k: $v\r\n";});
                }
                else
                    $h = array();
                $filename = isset($value['filename']) ? "; filename=\"{$value['filename']}\"" : '';
                $content = $value['content'];
            }
            else
            {
                $h = array();
                $filename = '';
                $content = $value;
            }

            $parts[] = "--$boundary\r\n"
                . "Content-Disposition: form-data; name=\"$name\"$filename\r\n".\implode('', $h)."\r\n"
                . $content
                ;
        }
        $data_url = \implode("\r\n", $parts)."\r\n--$boundary--";
        $data_len = \strlen($data_url);
        $headers += array('Connection'=>'close'
            , 'Content-Length'=>$data_len
            , 'Content-Type'=>"multipart/form-data; boundary=$boundary"
            );
        \array_walk($headers, function(&$v, $k){$v = "$k: $v";});

        $fgc = \file_get_contents($url
            , false
            , \stream_context_create(array('http'=>array('method'=>'POST'
                , 'header'=>\implode("\r\n", $headers)
                , 'content'=>$data_url
                , 'follow_location'=>false
                )))
            );

        if ($fgc === false)
            \error_log(\print_r($data_url, true), 3, '/tmp/http_post_upload_errors.txt');

        return array('content'=>$fgc ?: null
            , 'headers'=>isset($http_response_header) ? $http_response_header : null
            );
    }

    /**
     * @return string   the ip address of the client (together with that of proxy if used)
     */
    public static function remoteAddr()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            return "{$_SERVER['REMOTE_ADDR']} / {$_SERVER['HTTP_X_FORWARDED_FOR']}";
        else
            return $_SERVER['REMOTE_ADDR'];
    }

    /** prepare a shortened url by using bit.ly online API
     * @param string $url   urlencoded address
     * @return string       the shortened url
     */
    public static function shortenUrl($url, $login, $key)
    {
        if ($url_short = Cache::fetch("bit.ly:$url"))
            return $url_short;

        if ($url_short = @\file_get_contents('http://api.bit.ly/v3/shorten'
            . "?login=$login"
            . "&apiKey=$key"
            . "&uri=$url"
            . '&format=txt'
            ))
        {
            $u = \urlencode($url_short);
            Cache::store("bit.ly:$url", $u);
            return $u;
        }
        else
            return $url;
    }
}
