<?php

namespace Dotwheel\Util;

use Dotwheel\Http\Http;
use Dotwheel\Nls\Nls;
use Dotwheel\Ui\Html;
use Dotwheel\Ui\HtmlPage;

class GReCaptcha
{
    public const SRC_API = 'https://www.google.com/recaptcha/api.js';
    public const SRC_VERIFY = 'https://www.google.com/recaptcha/api/siteverify';

    public const JS_ONLOAD = 'onload';
    public const JS_RENDER = 'render';

    public const FLD_RESPONSE = 'g-recaptcha-response';

    public const POST_SECRET = 'secret';
    public const POST_RESPONSE = 'response';
    public const POST_REMOTE_IP = 'remoteip';

    public const A_SITEKEY = 'data-sitekey';
    public const A_THEME = 'data-theme';
    public const A_TYPE = 'data-type';
    public const A_SIZE = 'data-size';
    public const A_TABINDEX = 'data-tabindex';
    public const A_CALLBACK = 'data-callback';
    public const A_EXPIRED_CBK = 'data-expired-callback';

    public const THEME_DARK = 'dark';
    public const THEME_LIGHT = 'light';

    public const TYPE_AUDIO = 'audio';
    public const TYPE_IMAGE = 'image';

    public const SIZE_COMPACT = 'compact';
    public const SIZE_NORMAL = 'normal';

    public const RESP_SUCCESS = 'success';
    public const RESP_ERRORS = 'error-codes';

    public const ERR_TRANSPORT = 1;
    public const ERR_JSON = 2;
    public const ERR_UNSET = 3;

    public static $error;


    /**
     * @param ?string $sitekey
     * @param array $attr
     * @return string
     */
    public static function getHtml(?string $sitekey, array $attr = []): string
    {
        HtmlPage::add([
            HtmlPage::SCRIPT_SRC => [
                __METHOD__ => isset(Nls::$lang)
                    ? self::SRC_API . Html::urlArgs('?', ['hl' => Nls::$lang])
                    : self::SRC_API,
            ],
        ]);

        Params::add($attr, 'g-recaptcha');
        Params::add($attr, $sitekey, 'data-sitekey');

        return '<div' . Html::attr($attr) . '></div>';
    }

    /**
     * @param ?string $secret
     * @param ?string $response
     * @return bool
     */
    public static function verify(?string $secret, ?string $response): bool
    {
        $post = Http::post(self::SRC_VERIFY, [
            self::POST_SECRET => $secret,
            self::POST_RESPONSE => $response,
        ]);

        $content = $post[Http::P_CONTENT];
        if (isset($content)) {
            if ($json = json_decode($content, true)
                and is_array($json)
                and isset($json[self::RESP_SUCCESS])
            ) {
                if ($json[self::RESP_SUCCESS]) {
                    self::$error = null;
                    return true;
                } else {
                    self::$error = isset($json[self::RESP_ERRORS])
                        ? implode(',', $json[self::RESP_ERRORS])
                        : self::ERR_UNSET;
                    return false;
                }
            } else {
                self::$error = self::ERR_JSON;
                return false;
            }
        } else {
            self::$error = self::ERR_TRANSPORT;
            return false;
        }
    }
}
