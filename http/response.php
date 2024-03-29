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
    public const JSON_TITLE = 'title';
    public const JSON_ORIGIN = 'origin';
    public const JSON_ERRORS = 'errors';

    /** @var string request name (used as page title etc.) */
    public static string $name = '';
    /** @var ?string request page description */
    public static ?string $description;
    /** @var array  input parameters that need to be passed to the next page */
    public static array $url_params = [];
    /** @var ?string url hash part that needs to be passed to the next page */
    public static ?string $url_hash = null;
    /** @var array  list of html-encoded errors */
    public static array $errors = [];


    /** add error message(s) to the list
     *
     * @param string|array $msg adds one or more messages to self::$errors (all messages stored in html). $msg may be an
     *  array of messages to add. in this case each individual message may be a string or an array with Html::P_HEADER
     *  item set with an error message. use Html::P_WRAP_FMT item to provide wrapper for the error messages
     *  Ex.1: 'text message'
     *  Ex.2: ['text message 1', 'text message 2']
     *  Ex.3: [{Html::P_WRAP_FMT:'&lt;div&gt;%s&lt;/div&gt;',
     *  {Html::P_HEADER:'html message 1'},
     *  {Html::P_HEADER:'html message 2'}
     * ]
     * @param bool $html whether the $msg is already html-encoded [false]
     */
    public static function addError($msg, bool $html = false)
    {
        if (is_array($msg)) {
            static::$errors = array_merge(
                static::$errors,
                $html
                    ? $msg
                    : array_map(function ($item) {return is_scalar($item) ? Html::encode($item) : $item;}, $msg)
            );
        } else {
            static::$errors[] = $html ? $msg : Html::encode($msg);
        }
    }

    /** select an error output method based on request arguments and pass a specified error message to it
     *
     * @param ?string $msg error message
     */
    public static function outputError(?string $msg = null)
    {
        switch (Request::$output) {
            case Request::OUT_HTML:
            case Request::OUT_CMD:
                header('HTTP/1.1 400 Application Error');
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

    /** output error message on html page
     *
     * @param ?string $msg error title message
     */
    public static function outputHtmlError(?string $msg = null)
    {
        $errors = [];
        if (static::$errors) {
            $wrap = '<ul>%s</ul>';
            $li = 0;
            foreach (static::$errors as $err) {
                if (is_array($err)) {
                    if (isset($err[Html::P_HEADER])) {
                        $errors[] = $err[Html::P_HEADER];
                    }
                    if (isset($err[Html::P_WRAP_FMT])) {
                        $wrap = $err[Html::P_WRAP_FMT];
                    }
                } else {
                    $errors[] = "<li>$err</li>";
                }
            }
            $errors_list = sprintf($wrap, implode('', $errors));
        } else {
            $errors_list = null;
        }

        static::outputHtml(
            sprintf(
                '<section><h1>%s</h1>%s</section>',
                Html::encode($msg),
                $errors_list
            )
        );
    }

    /** error message on json page
     *
     * @param ?string $msg error title message
     */
    public static function outputJsonError(?string $msg = null)
    {
        static::outputJson([
            self::JSON_TITLE => $msg,
            self::JSON_ORIGIN => static::$name,
            self::JSON_ERRORS => static::$errors,
        ]);
    }

    /** error message on asis page
     *
     * @param ?string $msg error title message
     */
    public static function outputAsisError(?string $msg = null)
    {
        static::outputAsis(Html::encode($msg) . "\n" . implode("\n", static::$errors));
    }

    /** set page name, check request parameters and user access rights, load current context
     *
     * @return array|bool on any error adds the error message and returns false, otherwise context
     */
    public static function init()
    {
        return [];
    }

    /** output error message on context unavailability */
    public static function outputErrorInit()
    {
        static::outputError(Text::dget(Nls::FW_DOMAIN, 'Input verification error'));
    }

    /** execute the requested operation
     *
     * @param mixed $context command context serving as a base for generated page
     * @return mixed on any error add the error message and return false, otherwise return value depends on request:
     *  - html string if requested an html file
     *  - array if requested json data
     *  - redirect location if requested a dml operation
     */
    public static function exec($context)
    {
        return true;
    }

    /** output error message on execution fault */
    public static function outputErrorExec()
    {
        static::outputError(Text::dget(Nls::FW_DOMAIN, 'Command execution error'));
    }

    /** select an output method based on request arguments and pass result to it
     *
     * @param mixed $result the result to pass to selected output method
     */
    public static function output($result)
    {
        switch (Request::$output) {
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
     *
     * @param ?string $html contents of an html block on the page
     */
    public static function outputHtml(?string $html)
    {
        header('Content-Language: ' . Nls::$lang);
        header('Content-Type: text/html;charset=' . Nls::$charset);

        HtmlPage::add([HtmlPage::TITLE => static::$name]);
        if (isset(static::$description)) {
            HtmlPage::add([HtmlPage::META_DESCRIPTION => static::$description]);
        }

        printf(
            "<!DOCTYPE html>\n%s<body>%s%s",
            HtmlPage::getHead(),
            $html,
            HtmlPage::getTail()
        );
    }

    /** output a redirect heading for the user browser
     *
     * @param bool|string $redirect true for default redirect, otherwise new url
     */
    public static function outputCmd($redirect)
    {
        if ($redirect === true) {
            header('Location: ' . Http::getRedirect(Request::$next, static::$url_params, static::$url_hash));
        } elseif (is_string($redirect) and strpos($redirect, ' ') === false) {
            header("Location: $redirect");
        } else {
            static::outputHtml($redirect);
        }
    }

    /** produce and output the result array in json format
     *
     * @param array|bool $result array to serialize
     */
    public static function outputJson($result)
    {
        header('Content-Type: application/json;charset=' . Nls::$charset);
        echo ($result === true)
            ? json_encode(Http::getRedirect(Request::$next, static::$url_params, static::$url_hash))
            : json_encode($result);
    }

    /** output message as is
     *
     * @param ?string $msg the message to output
     */
    public static function outputAsis($msg)
    {
        echo $msg;
    }

    /** run the sequence of init() followed by exec() and output return value within a convenient template (html page,
     * json string, redirect header, etc.)
     *
     * @return bool on any error outputs an error template and returns false, otherwise
     * true
     */
    public static function run()
    {
        if (($context = static::init()) !== false) {
            if (($result = static::exec($context)) !== false) {
                static::output($result);
                return true;
            } else {
                static::outputErrorExec();
            }
        } else {
            static::outputErrorInit();
        }

        return false;
    }
}
