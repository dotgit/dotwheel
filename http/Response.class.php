<?php

/**
 * base class for user-defined responses
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\http;

require_once (__DIR__.'/Http.class.php');
require_once (__DIR__.'/../db/Db.class.php');
require_once (__DIR__.'/../ui/Html.class.php');
require_once (__DIR__.'/../ui/HtmlPage.class.php');
require_once (__DIR__.'/../util/Nls.class.php');

use dotwheel\db\Db;
use dotwheel\ui\Html;
use dotwheel\ui\HtmlPage;
use dotwheel\util\Nls;

class Response
{
    /** @var string	request name(used as page title etc.) */
	public static $name = '';
	/** @var string	request page description */
	public static $description;
	/** @var array	input parameters that need to be passed to the next page */
	public static $url_params = array();
	/** @var int	db connection mode: Db::MODE_READ | Db::MODE_WRITE | null */
	public static $db_mode;
	/** @var array list of html-encoded errors */
	public static $errors = array();



	/** add error message(s) to the list
	 * @param string $msg       adds one or more messages to self::$errors
     *                          (all messages stored in html)
	 * @param bool $html        whether the $msg is already html-encoded
	 */
	public static function addError($msg, $html=false)
	{
		if (is_array($msg))
			static::$errors = array_merge(static::$errors, $html ? $msg : array_map('Html::encode', $msg));
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

    /** error message on html page */
    public static function outputHtmlError($msg=null)
    {
        static::outputHtml('<section><h1>'.Html::encode(static::$name).'</h1>'
            . '<ul><li>'.implode('</li><li>', static::$errors).'</li></ul>'
            . '</section>'
            );
    }

    /** error message on json page */
    public static function outputJsonError($msg=null)
    {
        static::outputJson(array('title'=>$msg
            , 'origin'=>static::$name
            , 'errors'=>static::$errors
            ));
    }

    /** error message on asis page */
    public static function outputAsisError($msg=null)
    {
        static::outputAsis(Html::encode(static::$name)."\n".implode("\n", static::$errors));
    }

    /** sets page name, checks request parameters and user access rights, loads
     * current context
	 * @return array|bool on any error adds the error message and returns false,
     * otherwise context
	 */
	public static function getContext()
	{
		return array();
	}

    /** error message on context inavailability */
    public static function outputErrorContext()
    {
        static::outputError(dgettext(Nls::FW_DOMAIN, 'Input verification error'));
    }

	/** executes the requested operation
     * @param mixed $context command context serving as a base for generated page
	 * @return mixed on any error adds the error message and returns false
	 *					otherwise return value depends on request:
	 *					- html string if requested an html file
	 *					- array if requested json data
	 *					- redirect location if requested a dml operation
	 */
	public static function exec($context)
	{
        switch (Request::$output)
        {
        case Request::OUT_HTML:
            return static::execHtml($context);
        case Request::OUT_CMD:
            return static::execRedirect($context);
        case Request::OUT_JSON:
            return static::execEnum($context);
        case Request::OUT_ASIS:
            return static::execText($context);
        }
	}

    /** execute a command using context and produce produce an html document */
    public static function execHtml($context)
    {
        return '';
    }

    /** execute a command using context and produce a redirect */
    public static function execRedirect($context)
    {
        return true;
    }

    /** execute a command using context and produce a structure */
    public static function execEnum($context)
    {
        return true;
    }

    /** execute a command using context and produce a text */
    public static function execText($context)
    {
        return '';
    }

    /** error message on execution fault */
    public static function outputErrorExec()
    {
        static::outputError(dgettext(Nls::FW_DOMAIN, 'Command execution error'));
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
        HtmlPage::add(array(HtmlPage::TITLE=>static::$name));
        if (isset(static::$description))
            HtmlPage::add(array(HtmlPage::META_DESCRIPTION=>static::$description));

        echo "<!DOCTYPE html>\n"
            . HtmlPage::getHead()
            . '<body>'
            . $html
            . HtmlPage::getTail()
            ;
	}

    /** output a redirect heading for the user browser
     * @param bool|string $redirect true for default redirect, otherwise new url
     */
	public static function outputCmd($redirect)
    {
        if ($redirect === true)
            header('Location: '.Http::getRedirect(Request::$next, static::$url_params));
        elseif (is_string($redirect))
            header("Location: $redirect");
    }

    /** produce and output the result array in json format
     * @param array $result the array to serialize
     */
	public static function outputJson($result)
    {
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /** output message as is
     * @param string $msg the message to output
     */
	public static function outputAsis($msg)
    {
        echo $msg;
    }

	/** runs the sequense of getContext() followed by exec() and outputs the return
     * value within a convenient template (html page, json string, redirect header,
     * etc.)
	 * @return bool on any error outputs an error template and returns false, otherwise
     * true
	 */
	public static function run()
	{
        if (($context = static::getContext()) !== false)
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
            static::outputErrorContext();

		return false;
	}
}
