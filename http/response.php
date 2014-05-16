<?php

/**
 * base class for user-defined responses
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Http;

use Dotwheel\Nls\Nls;
use Dotwheel\Nls\Text;
use Dotwheel\Ui\Html;
use Dotwheel\Ui\HtmlPage;

class Response
{
    const JSON_TITLE    = 'title';
    const JSON_ORIGIN   = 'origin';
    const JSON_ERRORS   = 'errors';

    /** @var string request name (used as page title etc.) */
    public static $name = '';
    /** @var string request page description */
    public static $description;
    /** @var array  input parameters that need to be passed to the next page */
    public static $url_params = array();
    /** @var string url hash part that needs to be passed to the next page */
    public static $url_hash = null;
    /** @var array  list of html-encoded errors */
    public static $errors = array();



    /** add error message(s) to the list
     * @param string $msg       adds one or more messages to self::$errors
     *                          (all messages stored in html)
     * @param bool $html        whether the $msg is already html-encoded [false]
     */
    public static function addError($msg, $html=false)
    {
        if (\is_array($msg))
            static::$errors = \array_merge(
                static::$errors,
                $html ? $msg : \array_map(function ($item) {return Html::encode($item);}, $msg)
            );
        else
            static::$errors[] = $html ? $msg : Html::encode($msg);
    }

    /** select an error output method based on request arguments and pass a
     * specified error message to it
     * @param string $msg error message
     */
    public static function outputError($msg=null)
    {
        switch (Request::$output)
        {
        case Request::OUT_HTML:
        case Request::OUT_CMD:
            \header('HTTP/1.1 400 Application Error');
            static::outputHtmlError($msg);
            break;
        case Request::OUT_JSON:
            static::outputJsonError($msg);
            break;
        case Request::OUT_ASIS:
            static::outputAsisError($msg);
            break;
        }
    }

    /** error message on html page */
    public static function outputHtmlError($msg=null)
    {
        static::outputHtml('<section><h1>'.Html::encode($msg).'</h1>'.
            (static::$errors
                ? ('<ul><li>'.\implode('</li><li>', static::$errors).'</li></ul>')
                : ''
            ).
            '</section>'
        );
    }

    /** error message on json page */
    public static function outputJsonError($msg=null)
    {
        static::outputJson(array(
            self::JSON_TITLE=>$msg,
            self::JSON_ORIGIN=>static::$name,
            self::JSON_ERRORS=>static::$errors,
        ));
    }

    /** error message on asis page */
    public static function outputAsisError($msg=null)
    {
        static::outputAsis(Html::encode($msg)."\n".\implode("\n", static::$errors));
    }

    /** sets page name, checks request parameters and user access rights, loads
     * current context
     * @return array|bool on any error adds the error message and returns false,
     * otherwise context
     */
    public static function init()
    {
        return array();
    }

    /** error message on context inavailability */
    public static function outputErrorInit()
    {
        static::outputError(Text::dget(Nls::FW_DOMAIN, 'Input verification error'));
    }

    /** executes the requested operation
     * @param mixed $context command context serving as a base for generated page
     * @return mixed on any error adds the error message and returns false
     *                    otherwise return value depends on request:
     *                    - html string if requested an html file
     *                    - array if requested json data
     *                    - redirect location if requested a dml operation
     */
    public static function exec($context)
    {
        return true;
    }

    /** error message on execution fault */
    public static function outputErrorExec()
    {
        static::outputError(Text::dget(Nls::FW_DOMAIN, 'Command execution error'));
    }

    /** select an output method based on request arguments and pass result to it
     * @param mixed $result the result to pass to selected output method
     */
    public static function output($result)
    {
        switch (Request::$output)
        {
        case Request::OUT_HTML:
            static::outputHtml($result);
            break;
        case Request::OUT_CMD:
            static::outputCmd($result);
            break;
        case Request::OUT_JSON:
            static::outputJson($result);
            break;
        case Request::OUT_ASIS:
            static::outputAsis($result);
            break;
        }
    }

    /** produce and output an html page using the passed parameter as contents
     * @param string $html contents of an html block on the page
     */
    public static function outputHtml($html)
    {
        \header('Content-Language: '.Nls::$lang);
        \header('Content-Type: text/html;charset='.Nls::$charset);

        HtmlPage::add(array(HtmlPage::TITLE=>static::$name));
        if (isset(static::$description))
            HtmlPage::add(array(HtmlPage::META_DESCRIPTION=>static::$description));

        echo "<!DOCTYPE html>\n".
            HtmlPage::getHead().
            '<body>'.
            $html.
            HtmlPage::getTail();
    }

    /** output a redirect heading for the user browser
     * @param bool|string $redirect true for default redirect, otherwise new url
     */
    public static function outputCmd($redirect)
    {
        if ($redirect === true)
            \header('Location: '.Http::getRedirect(Request::$next, static::$url_params, static::$url_hash));
        elseif (\is_string($redirect))
            \header("Location: $redirect");
    }

    /** produce and output the result array in json format
     * @param array $result the array to serialize
     */
    public static function outputJson($result)
    {
        \header('Content-Type: application/json;charset='.Nls::$charset);
        echo ($result === true)
            ? \json_encode(Http::getRedirect(Request::$next, static::$url_params, static::$url_hash))
            : \json_encode($result);
    }

    /** output message as is
     * @param string $msg the message to output
     */
    public static function outputAsis($msg)
    {
        echo $msg;
    }

    /** runs the sequense of init() followed by exec() and outputs the return
     * value within a convenient template (html page, json string, redirect header,
     * etc.)
     * @return bool on any error outputs an error template and returns false, otherwise
     * true
     */
    public static function run()
    {
        static::$url_params += Request::$details;

        if (($context = static::init()) !== false)
        {
            if (($result = static::exec($context)) !== false)
            {
                static::output($result);
                return true;
            }
            else
                static::outputErrorExec();
        }
        else
            static::outputErrorInit();

        return false;
    }
}
