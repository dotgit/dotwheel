<?php

namespace Dotwheel\Util;

use Dotwheel\Http\Http;
use Dotwheel\Nls\Nls;
use Dotwheel\Ui\Html;
use Dotwheel\Ui\HtmlPage;
use Dotwheel\Util\Params;

class GReCaptcha
{
    const SRC_API       = 'https://www.google.com/recaptcha/api.js';
    const SRC_VERIFY    = 'https://www.google.com/recaptcha/api/siteverify';

    const JS_ONLOAD = 'onload';
    const JS_RENDER = 'render';

    const FLD_RESPONSE  = 'g-recaptcha-response';

    const POST_SECRET       = 'secret';
    const POST_RESPONSE     = 'response';
    const POST_REMOTE_IP    = 'remoteip';

    const A_SITEKEY     = 'data-sitekey';
    const A_THEME       = 'data-theme';
    const A_TYPE        = 'data-type';
    const A_SIZE        = 'data-size';
    const A_TABINDEX    = 'data-tabindex';
    const A_CALLBACK    = 'data-callback';
    const A_EXPIRED_CBK = 'data-expired-callback';

    const THEME_DARK    = 'dark';
    const THEME_LIGHT   = 'light';

    const TYPE_AUDIO    = 'audio';
    const TYPE_IMAGE    = 'image';

    const SIZE_COMPACT  = 'compact';
    const SIZE_NORMAL   = 'normal';

    const RESP_SUCCESS  = 'success';
    const RESP_ERRORS   = 'error-codes';

    const ERR_TRANSPORT = 1;
    const ERR_JSON      = 2;
    const ERR_UNSET     = 3;

    public static $error;



    public static function getHtml($sitekey, $attr=[])
    {
        HtmlPage::add([HtmlPage::SCRIPT_SRC=>[
            __METHOD__=>isset(Nls::$lang)
                ? self::SRC_API.Html::urlArgs('?', ['hl'=>Nls::$lang])
                : self::SRC_API,
        ]]);

        Params::add($attr, 'g-recaptcha');
        Params::add($attr, $sitekey, 'data-sitekey');

        return '<div'.Html::attr($attr).'></div>';
    }

    public static function verify($secret, $response)
    {
        $post = Http::post(self::SRC_VERIFY, [
            self::POST_SECRET=>$secret,
            self::POST_RESPONSE=>$response,
        ]);

        $content = $post[Http::P_CONTENT];
        if (isset($content))
        {
            if ($json = \json_decode($content, true) and \is_array($json) and isset($json[self::RESP_SUCCESS]))
            {
                if ($json[self::RESP_SUCCESS])
                {
                    self::$error = null;
                    return true;
                }
                else
                {
                    self::$error = isset($json[self::RESP_ERRORS])
                        ? \implode(',', $json[self::RESP_ERRORS])
                        : self::ERR_UNSET;
                    return false;
                }
            }
            else
            {
                self::$error = self::ERR_JSON;
                return false;
            }
        }
        else
        {
            self::$error = self::ERR_TRANSPORT;
            return false;
        }
    }
}
