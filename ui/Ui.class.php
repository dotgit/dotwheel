<?php

/**
 * html layout helpers
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\ui;

require_once (__DIR__.'/BootstrapUi.class.php');
require_once (__DIR__.'/HtmlPage.class.php');
require_once (__DIR__.'/../http/Request.class.php');
require_once (__DIR__.'/../util/Misc.class.php');
require_once (__DIR__.'/../util/Nls.class.php');

use dotwheel\http\Request;
use dotwheel\util\Misc;
use dotwheel\util\Nls;

class Ui extends BootstrapUi
{
    const REQUIRED_CLASS = '_req';

    const TABLE_NEW_GROUP_CLASS = '_sw';
    const TABLE_TOTAL_ROW_CLASS = '_ttl';
    const TABLE_EMPTY_CLASS = '_tbl_empty';

    const GRID_GUTTER_CLASS = '_gtr';

    const INI_STATIC_URL = 1;
    const INI_STATIC_PATH_CSS_INITIAL   = 2;

    static $static_url = '/';
    static $static_path_css_initial = '';



    /** predefines Config and Modal globals that will handle configuration parameters
     * (messages, date formats, etc.) and a default dialog on html page
     */
    public static function init_globals()
    {
        $msg_lang = json_encode(Nls::$lang);
        $datepicker = Nls::$formats[Nls::P_DATEPICKER];
        $msg_ok = json_encode(dgettext(Nls::FW_DOMAIN, 'OK'));
        $msg_save = json_encode(dgettext(Nls::FW_DOMAIN, 'Save'));
        $msg_cancel = json_encode(dgettext(Nls::FW_DOMAIN, 'Cancel'));
        $msg_close = json_encode(dgettext(Nls::FW_DOMAIN, 'Close'));
        $msg_submit = json_encode(dgettext(Nls::FW_DOMAIN, 'Submit'));

        HtmlPage::add(array(HtmlPage::STYLE_SRC=>array('init'=>Request::$static_url.Request::$static_path_css)
            , HtmlPage::SCRIPT=>array(__METHOD__.'-init'=><<<EOscr
var Config={lang:$msg_lang,datepicker:$datepicker,msg:{ok:$msg_ok,save:$msg_save,cancel:$msg_cancel,close:$msg_close,submit:$msg_submit}};
var Modal;
EOscr
            )));
    }

    /** registers preview_txt global js function, installs keyup listeners on source field
     * and initializes target div with formatted default content
     * @param string $id_text       id attribute of source field
     * @param string $id_preview    id attribute of container div to display preview
     */
    public static function registerPreviewTxt($id_text, $id_preview)
    {
        HtmlPage::add(array(HtmlPage::SCRIPT=>array(__METHOD__=><<<EOpr
function preview_txt(txt){return txt
    .replace('&','&amp;')
    .replace('<','&lt;')
    .replace('>','&gt;')
    .replace(/\/([^/\\r\\n]*)\//gm,'<i>$1</i>')
    .replace(/\*([^*\\r\\n]*)\*/gm,'<b>$1</b>')
    .replace(/^/gm,'<p>')
    .replace(/$/gm,'</p>')
    .replace(/^<p>---(.*)---<\/p>$/gm,'<h5>$1</h5>')
    .replace(/^<p>-(.*)<\/p>$/gm,'<li>$1</li>')
    ;}
EOpr
                )
            , HtmlPage::DOM_READY=>array(__METHOD__=><<<EOdr
jQuery('#$id_text').keyup(function(et){jQuery('#$id_preview').html(preview_txt(jQuery(et.target).val()));});
jQuery('#$id_preview').html(preview_txt(jQuery('#$id_text').val()));
EOdr
                )
            ));
    }
}
