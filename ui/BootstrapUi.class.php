<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace dotwheel\ui;

require_once (__DIR__.'/../ui/Html.class.php');
require_once (__DIR__.'/../ui/HtmlPage.class.php');
require_once (__DIR__.'/../util/Misc.class.php');
require_once (__DIR__.'/../util/Params.class.php');

use dotwheel\ui\Html;
use dotwheel\ui\HtmlPage;
use dotwheel\util\Misc;
use dotwheel\util\Params;

/**
 * Description of BootstrapUi
 *
 * @author stas trefilov
 */
class BootstrapUi
{
    const TBL_SORT_ICON_UP      = ' <i class="icon-arrow-up"></i>';
    const TBL_SORT_ICON_DOWN    = ' <i class="icon-arrow-down"></i>';

    const PGN_CLASS     = 'pagination pagination-centered';
    const PGN_ACTIVE    = 1;
    const PGN_LAST      = 2;
    const PGN_LIST      = 3;
    const PGN_LINK_1    = 4;

    const FORM_COMMENT_BLOCK_FMT = '<span class="help-block">%s</span>';
    const FORM_COMMENT_INLINE_FMT = '<span class="help-inline">%s</span>';

    const P_WIDTH           = 1;
    const P_CONTENT         = 2;
    const P_CONTENT_ATTR    = 3;
    const P_LABEL           = 4;
    const P_LABEL_ATTR      = 5;
    const P_FOOTER          = 6;
    const P_FORM_TYPE       = 7;
    const P_TARGET          = 8;
    const P_ACTIVE          = 9;
    const P_CLOSE           = 10;
    const P_WRAP_FMT        = 11;
    const P_HIDDEN          = 12;
    const P_READONLY        = 13;
    const P_STATIC          = 14;
    const P_PREFIX          = 15;
    const P_SUFFIX          = 16;
    const P_ADDON_BTN       = 17;
    const P_ALIGN           = 18;
    const P_REQUIRED        = 19;

    // for P_FORM_TYPE
    const FT_HORIZONTAL  = 1;

    // for P_ALIGN
    const A_TOP     = 'top';
    const A_RIGHT   = 'right';
    const A_BOTTOM  = 'bottom';
    const A_LEFT    = 'left';

    // for P_WIDTH
    const W_XSMALL      = 'xs';
    const W_SMALL       = 'sm';
    const W_MIDDLE      = 'md';
    const W_LARGE       = 'lg';
    const WIDTH_1       = 12;
    const WIDTH_11_12   = 11;
    const WIDTH_5_6     = 10;
    const WIDTH_3_4     = 9;
    const WIDTH_2_3     = 8;
    const WIDTH_7_12    = 7;
    const WIDTH_1_2     = 6;
    const WIDTH_5_12    = 5;
    const WIDTH_1_3     = 4;
    const WIDTH_1_4     = 3;
    const WIDTH_1_6     = 2;
    const WIDTH_1_12    = 1;



    /** returns a div formatted as alert block
     * @param array $params {P_LABEL:'alert body', P_LABEL_ATTR:{label tag attributes}
     *                      , P_CLOSE:true // show close btn?
     *                      , P_CONTENT:'alert body'
     *                      , div tag arguments
     *                      }
     * @return string
     */
    public static function alert($params)
    {
        $body = Params::extract($params, self::P_CONTENT);
        if ($label = Params::extract($params, self::P_LABEL))
        {
            $label_attr = Params::extract($params, self::P_LABEL_ATTR, array());
            $body = '<h4'.Html::attr($label_attr).'>'.$label.'</h4>'.$body;
        }
        if ($close = Params::extract($params, self::P_CLOSE))
        {
            if (is_array($close))
                Params::add($close, 'alert', 'data-dismiss');
            else
                $close = array('data-dismiss'=>'alert');
            $body = self::close($close) . $body;
            Params::add($params, 'alert-dismissable');
        }
        Params::add($params, 'alert');

        return '<div'.Html::attr($params).">$body</div>";
    }

    public static function alertWithIcon($params)
    {
        $body = Params::extract($params, self::P_CONTENT);
        Params::add($params, 'clearfix');
        $icon = self::icon(array(self::P_LABEL=>'icon-warning-sign icon-2x pull-left', 'style'=>'margin:0.25em 0.5em 0 0;'));
        return "<div".Html::attr($params).">$icon$body</div>";
    }

    /** format as comment line
     * @param array|string $comment {P_CONTENT:'comment', d.t.a.}|'comment to format as help block'
     * @return string
     */
    public static function asComment($comment)
    {
        if(is_array($comment))
        {
            $c = Params::extract($comment, self::P_CONTENT);
            Params::add($comment, 'help-block');
            return "<div".Html::attr($comment).">$c</div>";
        }
        else
            return isset($comment) ? "<div class=\"help-block\">$comment</div>" : null;
    }

    /** formats the control to be displayed as horizontal form row
     * @param array|string $control {P_LABEL_ATTR:{P_WIDTH:2, label tag attributes}
     *                              , P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.}
     *                              , d.t.a.
     *                              }
     *                              | 'content to format as form group'
     * @return string
     */
    public static function asFormGroupLine($control)
    {
        if(is_array($control))
        {
            $l_attr = Params::extract($control, self::P_LABEL_ATTR, array());
            if ($w = Params::extract($l_attr, self::P_WIDTH, self::WIDTH_1_4))
                $l_attr = static::width2Attr($w, $l_attr);
            Params::add($l_attr, 'control-label');
            $control[self::P_LABEL_ATTR] = $l_attr;

            $content_attr = Params::extract($control, self::P_CONTENT_ATTR, array());
            if ($w = Params::extract($content_attr, self::P_WIDTH, self::WIDTH_3_4))
                $content_attr = static::width2Attr($w, $content_attr);
            if ($content_attr)
                $control[self::P_CONTENT_ATTR] = $content_attr;

            Params::add($control, 'row');

            return self::asFormGroup($control);
        }
        elseif (isset($control))
            return '<div class="row"><div'.Html::attr(self::width2Attr(self::WIDTH_3_4, self::widthOffset2Attr(self::WIDTH_1_4))).'>'.$control.'</div></div>';
        else
            return null;
    }

    /** format as form group
     * @param array|string $control {P_CONTENT:'form group content'
     *                              , P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.}
     *                              , P_TARGET:'label tag for attribute target'
     *                              , P_LABEL:'label content'
     *                              , P_LABEL_ATTR:{P_WIDTH:2, label tag attributes}
     *                              , d.t.a.
     *                              }
     *                              | 'content to format as form group'
     * @return string
     */
    public static function asFormGroup($control)
    {
        if(is_array($control))
        {
            $l = Params::extract($control, self::P_LABEL);
            $l_attr = Params::extract($control, self::P_LABEL_ATTR, array());
            if ($w = Params::extract($l_attr, self::P_WIDTH))
                $l_attr = static::width2Attr($w, $l_attr);

            if ($t = Params::extract($control, self::P_TARGET))
                Params::add($l_attr, $t, 'for');

            if ($l_attr)
                $label = '<label'.Html::attr($l_attr).">$l</label>";
            elseif (isset($l))
                $label = "<label>$l</label>";
            else
                $l = null;

            $content = Params::extract($control, self::P_CONTENT);
            $content_attr = Params::extract($control, self::P_CONTENT_ATTR, array());
            if ($w = Params::extract($content_attr, self::P_WIDTH))
                $content_attr = static::width2Attr($w, $content_attr);
            if ($content_attr)
                $content = "<div".Html::attr($content_attr).">$content</div>";

            Params::add($control, 'form-group');

            return "<div".Html::attr($control).">$label$content</div>";
        }
        elseif (isset($control))
            return "<div class=\"form-group\">$control</div>";
        else
            return null;
    }

    /** get button element
     * @param array $params {{P_TARGET:'pane id'
     *                          , P_LABEL:'tab label'
     *                          , P_CONTENT:'pane content'
     *                          , P_ACTIVE:bool
     *                          }
     *                      , ul tag attributes
     *                      }
     * @return string
     */
    public static function breadcrumbs($params)
    {
        self::registerBreadcrumbs();

        $items = array();
        foreach ($params as $k=>$item)
        {
            if (is_array($item))
            {
                $target = Params::extract($item, self::P_TARGET);
                $label = Params::extract($item, self::P_LABEL);
                if (Params::extract($item, self::P_ACTIVE))
                {
                    Params::add($item, 'active');
                    $items[] = '<li'.Html::attr($item).">$label</li>\n";
                }
                else
                    $items[] = '<li'.Html::attr($item)."><a href=\"$target\">$label</a></li>\n";
                unset($params[$k]);
            }
            else
                $items[] = "<li class=\"active\">$item</li>";
        }
        Params::add($params, 'breadcrumb');

        return '<ul'.Html::attr($params).'>'.implode('', $items).'</ul>';
    }

    /** get button element
     * @param array $params {P_LABEL:button value, button tag attributes}
     * @return string
     */
    public static function button($params)
    {
        $label = Params::extract($params, self::P_LABEL);
        $params += array('type'=>'button');
        Params::add($params, 'btn');

        return '<button'.Html::attr($params).">$label</button>";
    }

    /** get close icon for alert modal
     * @param array $params {button tag attributes}
     * @return string
     */
    public static function close($params=array())
    {
        self::registerAlert();

        Params::add($params, 'close');

        return '<button'.Html::attr($params).">&times;</button>";
    }

    /** returns collapsed container(hidden by default)
     * @param array $params {P_CONTENT:'content body'
     *                      , container tag attributes
     *                      }
     */
    public static function collapseContainer($params)
    {
        self::registerCollapse();

        $content = Params::extract($params, self::P_CONTENT);
        Params::add($params, 'collapse');

        return '<div'.Html::attr($params).">$content</div>";
    }

    /** returns dropdown button coupled with collapsed container and a js
     * to control caret display
     * @staticvar int $cnt  to generate missing id
     * @param array $params {P_TARGET:'target id'
     *                      , P_LABEL:'button text'
     *                      , P_LABEL_ATTR:{class:'icon-filter'}
     *                      , button div attributes
     *                      }
     * @return string
     */
    public static function collapseOpenerButton($params)
    {
        static $cnt;

        self::registerButton();
        self::registerCollapse();

        $id = Params::extract($params, 'id', 'clps_btn_'.++$cnt);
        $id_target = Params::extract($params, self::P_TARGET);
        $prefix = ($label_attr = Params::extract($params, self::P_LABEL_ATTR, array()))
            ? ('<i'.Html::attr($label_attr).'></i> ')
            : ''
            ;
        $prefix .= ($label = Params::extract($params, self::P_LABEL))
            ? "$label "
            : ''
            ;

        HtmlPage::add(array(HtmlPage::DOM_READY=><<<EOco
$('#$id_target')
.on('show',function(){\$('#$id').addClass('dropup').removeClass('dropdown').button('toggle');})
.on('hide',function(){\$('#$id').addClass('dropdown').removeClass('dropup').button('toggle');})
;
EOco
            ));

        Params::add($params, $id, 'id');
        Params::add($params, 'collapse', 'data-toggle');
        Params::add($params, "#$id_target", 'data-target');
        Params::add($params, 'dropdown');

        return self::button(array(self::P_LABEL=>"$prefix<span class=\"caret\"></span>") + $params);
    }

    /** returns a collapsible group
     * @param array $params {P_LABEL:'group label'
     *                      , P_LABEL_ATTR:{additional label div attributes}
     *                      , P_CONTENT:'collapsible content'
     *                      , P_CONTENT_ATTR:{additional content div attributes}
     *                      , P_FOOTER:'panel footer'
     *                      , 'id':'content div id'
     *                      , additional content div attributes
     *                      }
     * @return string
     */
    public static function collapsible($params)
    {
        $id = isset($params['id']) ? $params['id'] : null;

        $label_attr = Params::extract($params, self::P_LABEL_ATTR, array());
        Params::add($label_attr, 'collapse', 'data-toggle');
        if (isset($id))
            Params::add($label_attr, "#$id", 'href');

        $label = Params::extract($params, self::P_LABEL);
        $content = Params::extract($params, self::P_CONTENT);
        $content_attr = Params::extract($params, self::P_CONTENT_ATTR);
        $addon_prefix = Params::extract($params, self::P_PREFIX);
        $addon_suffix = Params::extract($params, self::P_SUFFIX);
        $footer = Params::extract($params, self::P_FOOTER);

        Params::add($params, 'panel-collapse');
        Params::add($params, 'collapse');

        return self::panel(array(self::P_LABEL=>'<a'.Html::attr($label_attr).'><div>'.$label.'</div></a>'
            , self::P_CONTENT=>$content
            , self::P_CONTENT_ATTR=>$content_attr
            , self::P_PREFIX=>$addon_prefix
            , self::P_SUFFIX=>$addon_suffix
            , self::P_FOOTER=>$footer
            , self::P_WRAP_FMT=>Misc::sprintfEscape('<div'.Html::attr($params).'>').'%s</div>'
            ));
    }

    /** generate dropdown list
     * @param string $items ['item 1 html', 'item 2 html', null, 'item post divider']
     * @return string
     */
    public static function dropdown($items)
    {
        return '<ul class="dropdown-menu">'.implode('', array_map(function($item){return isset($item) ? "<li>$item</li>" : '<li class="divider"></li>';}, $items)).'</ul>';
    }

    /** extracts prefix / suffix addons from the Ui parameters and returns sprintf
     * format to wrap the field html
     * @param array $ui {P_PREFIX:"input prefix addon'|{P_CONTENT:'prefix content', P_ADDON_BTN:true, prefix arguments}
     *                  , P_SUFFIX:'input suffix addon'|{P_CONTENT:'suffix content', P_ADDON_BTN:true, suffix arguments}
     *                  }
     * @return string   '%s' if no prefixes / suffixes detected, otherwise '...%s...'
     */
    public static function fmtAddons($ui)
    {
        if ($prefix = Params::extract($ui, self::P_PREFIX))
        {
            if (is_array($prefix))
            {
                $cnt = Params::extract($prefix, self::P_CONTENT);
                $class = Params::extract($prefix, self::P_ADDON_BTN) ? 'input-group-btn' : 'input-group-addon';
                Params::add($prefix, $class);
                $prefix = '<div'.Html::attr($prefix).">$cnt</div>";
            }
            else
                $prefix = "<div class=\"input-group-addon\">$prefix</div>";
        }

        if ($suffix = Params::extract($ui, self::P_SUFFIX))
        {
            if (is_array($suffix))
            {
                $cnt = Params::extract($suffix, self::P_CONTENT);
                $class = Params::extract($suffix, self::P_ADDON_BTN) ? 'input-group-btn' : 'input-group-addon';
                Params::add($suffix, $class);
                $suffix = '<div'.Html::attr($suffix).">$cnt</div>";
            }
            else
                $suffix = "<div class=\"input-group-addon\">$suffix</div>";
        }

        if ($prefix or $suffix)
        {
            $prefix = Misc::sprintfEscape('<div class="input-group">'.$prefix);
            $suffix = Misc::sprintfEscape($suffix.'</div>');
        }

        return "$prefix%s$suffix";
    }

    /** returns a div wrapper containing other divs for individual columns
     * @param array $columns    {{P_WIDTH:..., P_CONTENT:'cell_content'}
     *                          ,'cell_content'
     *                          ,'row_attr':'value'
     *                          }
     * @return string
     */
    public static function gridRow($columns)
    {
        $fld_count = 0;
        foreach ($columns as $k=>$col)
            if (is_array($col) or is_int($k))
                ++$fld_count;
        switch ($fld_count)
        {
            case 1: $width_default = self::WIDTH_1; break;
            case 2: $width_default = self::WIDTH_1_2; break;
            case 3: $width_default = self::WIDTH_1_3; break;
            case 4: $width_default = self::WIDTH_1_4; break;
            case 5: case 6: $width_default = self::WIDTH_1_6; break;
            default: $width_default = self::WIDTH_1_12;
        }

        $cols = array();
        foreach ($columns as $k=>$col)
        {
            if (is_array($col))
            {
                if ($width = Params::extract($col, self::P_WIDTH, $width_default))
                    $col = static::width2Attr($width, $col);
                $content = Params::extract($col, self::P_CONTENT);
                $cols[] = '<div'.Html::attr($col).'>'.$content.'</div>';
                unset($columns[$k]);
            }
            elseif (is_int($k))
            {
                $attr = static::width2Attr($width_default);
                $cols[] = '<div'.Html::attr($attr).'>'.$col.'</div>';
                unset($columns[$k]);
            }
        }
        Params::add($columns, 'row');

        return '<div'.Html::attr($columns).'>'.implode('', $cols).'</div>';
    }

    /** get icon html
     * @param string|array $icon    icon code|{P_LABEL:'icon code', i tag attributes}
     * @return string
     */
    public static function icon($icon)
    {
        if (is_array($icon))
        {
            $label = Params::extract($icon, self::P_LABEL);
            Params::add($icon, $label);

            return '<i'.Html::attr($icon).'></i>';
        }
        else
            return "<i class=\"$icon\"></i>";
    }

    /** returns a modal dialog window with specified header, body and buttons
     * @param array $params {P_LABEL:'dialog title'
     *                      , P_CONTENT:'dialog body'
     *                      , P_FOOTER:'dialog buttons row'
     *                      , P_CLOSE:close button tag attributes
     *                      , container div attributes
     *                      }
     * @return string
     */
    public static function modal($params)
    {
        self::registerModal();

        $close = Params::extract($params, self::P_CLOSE);
        $header = Params::extract($params, self::P_LABEL);
        $body = Params::extract($params, self::P_CONTENT);
        $footer = Params::extract($params, self::P_FOOTER);

        Params::add($params, 'modal');
        Params::add($params, 'dialog', 'role');
        Params::add($params, 'true', 'aria-hidden');
        Params::add($params, 'true', 'data-keyboard');

        if (is_array($close))
            Params::add($close, 'modal', 'data-dismiss');
        else
            $close = array('data-dismiss'=>'modal');
        if (isset($header))
            $header = "<div class=\"modal-header\">".self::close($close)."<h4 class=\"modal-title\">$header</h4></div>";
        if (isset($body))
            $body = "<div class=\"modal-body\">$body</div>";
        if (isset($footer))
            $footer = "<div class=\"modal-footer\">$footer</div>";

        return '<div'.Html::attr($params).'>'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . $header
            . $body
            . $footer
            . '</div>'
            . '</div>'
            . '</div>'
            ;
    }

    /** html-formatted pagination based on butons
     * @param array $params {PG_ACTIVE:current page number
     *                      , PG_LAST: last page number
     *                      , PG_LIST: array of pages to display
     *                      , PG_LINK_1: sprintf-formatted url with one parameter for page number
     *                      }
     * @return string buttons representing pages
     */
    public static function paginationUsingLinear($params)
    {
        $active_page = Params::extract($params, self::PGN_ACTIVE);
        $last_page = Params::extract($params, self::PGN_LAST);
        $pages = Params::extract($params, self::PGN_LIST);
        $link_1 = Params::extract($params, self::PGN_LINK_1);

        if (empty($pages))
            return null;

        $ret = array ();
        $tail = count($pages) - 1;

        if ($pages[0] > 1)
        {
            $ret[] = '<div class="btn-group">'
                . '<a class="btn" href="'.sprintf($link_1, 1).'">1</a>'
                . '</div>'
                ;
            $ret[] = '<div class="btn-group">'
                . '<a class="btn" href="'.sprintf($link_1, $pages[0] - 1).'">&larr; '.($pages[0] - 1).'</a>'
                . '</div>'
                ;
        }

        $ret[] = '<div class="btn-group">';
        foreach ($pages as $p)
            $ret[] = '<a class="btn'.($p == $active_page ? ' active' : '').'" href="'.sprintf($link_1, $p).'">'.$p.'</a>';
        $ret[] = '</div>';

        if ($pages[$tail] < $last_page)
        {
            $ret[] = '<div class="btn-group">'
                . '<a class="btn" href="'.sprintf($link_1, $pages[$tail] + 1).'">'.($pages[$tail] + 1).' &rarr;</a>'
                . '</div>'
                ;
            $ret[] = '<div class="btn-group">'
                . '<a class="btn" href="'.sprintf($link_1, $last_page).'">'.$last_page.'</a>'
                . '</div>'
                ;
        }

        return implode('', $ret);
    }

    /** html-formatted bootstrap pagination
     * @param array $params {PG_ACTIVE:current page number
     *                      , PG_LIST: array of pages to display
     *                      , PG_LINK_1: sprintf-formatted url with one parameter for page number
     *                      }
     * @return string bootstrap pagination using unordered list
     */
    public static function paginationUsingLog($params)
    {
        $active_page = Params::extract($params, self::PGN_ACTIVE);
        $pages = Params::extract($params, self::PGN_LIST);
        $link_1 = Params::extract($params, self::PGN_LINK_1);

        if (empty($pages))
            return null;

        $s = array('<ul>');
        if ($active_page > 1)
            $s[] = '<li><a href="'.sprintf($link_1, $active_page - 1).'">&larr;</a></li>';
        foreach ($pages as $i=>$n)
        {
            if ($n == $active_page)
                $s[] = '<li class="active"><span>'.$n.'</span></li>';
            else
                $s[] = '<li><a href="'.sprintf($link_1, $n).'">'.$n.'</a></li>';
        }
        if ($active_page < $n)
            $s[] = '<li><a href="'.sprintf($link_1, $active_page + 1).'">&rarr;</a></li>';
        $s[] = '</ul>';

        return implode('', $s);
    }

    /** returns the panel html code
     * @param array $params {P_LABEL:'panel heading'
     *                      , P_FOOTER:'panel footer'
     *                      , P_PREFIX:'panel content prefix'
     *                      , P_SUFFIX:'panel content suffix'
     *                      , P_CONTENT:'panel content'
     *                      , P_CONTENT_ATTR:panel content div attributes
     *                      , P_WRAP_FMT:'%s-style format for content'
     *                      , panel div tag attributes
     *                      }
     * @return string
     */
    public static function panel($params)
    {
        if ($heading = Params::extract($params, self::P_LABEL))
            $heading = "<div class=\"panel-heading\">$heading</div>";
        if ($footer = Params::extract($params, self::P_FOOTER))
            $footer = "<div class=\"panel-footer\">$footer</div>";
        $fmt = Params::extract($params, self::P_WRAP_FMT, '%s');
        $content_attr = Params::extract($params, self::P_CONTENT_ATTR, array());
        Params::add($content_attr, 'panel-body');
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);
        if ($content = Params::extract($params, self::P_CONTENT))
            $content = "<div".Html::attr($content_attr).">$content</div>";
        Params::add($params, 'panel');
        Params::add($params, 'panel-default');

        return '<div'.Html::attr($params).'>'.$heading.sprintf($fmt, $prefix.$content.$suffix.$footer).'</div>';
    }

    /** returns the popover html code
     * @param array $params {P_LABEL:'popover heading'
     *                      , P_CONTENT:'popover content'
     *                      , P_ALIGN:'popover placement'
     *                      , P_TARGET:'opener element id'
     *                      , P_CLOSE:whether to display close btn
     *                      }
     * @return string
     */
    public static function registerPopoverOnElement($params)
    {
        self::registerTooltip();

        if ($close = Params::extract($params, self::P_CLOSE))
        {
            if (is_array($close))
                Params::add($close, 'popover', 'data-dismiss');
            else
                $close = array('data-dismiss'=>'popover');
            $close = self::close($close);
        }

        $title = Params::extract($params, self::P_LABEL);
        $id = Params::extract($params, self::P_TARGET);

        $options = array('title'=>$title
            , 'content'=>Params::extract($params, self::P_CONTENT)
            , 'placement'=>Params::extract($params, self::P_ALIGN, 'bottom')
            , 'html'=>true
            , 'container'=>'body'
            );

        HtmlPage::add(array(HtmlPage::DOM_READY=>array(__METHOD__."-$id"=>"$('#$id').popover(".json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).');')));

        return $params;
    }

    /** register alerts js */
    public static function registerAlert()
    {
    }

    /** register breadcrumb js */
    public static function registerBreadcrumbs()
    {
    }

    /** register button js */
    public static function registerButton()
    {
    }

    /** register collapse js */
    public static function registerCollapse()
    {
    }

    /** register dropdown js */
    public static function registerDropdown()
    {
    }

    /** register modal js */
    public static function registerModal()
    {
    }

    /** register tab js */
    public static function registerTab()
    {
    }

    /** register tooltip js */
    public static function registerTooltip()
    {
    }

    /** html-formatted bootstrap tabs
     * @param array $items  {{P_TARGET:'pane id'
     *                          , P_LABEL:'tab label'
     *                          , P_CONTENT:'pane content'
     *                          , P_ACTIVE:bool
     *                          }
     *                      , div tag attributes
     *                      }
     * @param array $params {}
     * @return type
     */
    public static function tabs($items, $params=array())
    {
        self::registerTab();

        $labels = array();
        $panes = array();
        foreach ($items as $k=>$tab)
        {
            if (is_array($tab))
            {
                $label = Params::extract($tab, self::P_LABEL);
                $content = Params::extract($tab, self::P_CONTENT);
                if (isset($content))
                {
                    $id = Params::extract($tab, self::P_TARGET);
                    $pane = array('id'=>$id, 'class'=>'tab-pane');
                    if (Params::extract($tab, self::P_ACTIVE))
                    {
                        Params::add($tab, 'active');
                        Params::add($pane, 'active');
                    }
                    $labels[] = '<li'.Html::attr($tab)."><a href=\"#$id\" data-toggle=\"tab\">$label</a></li>";
                    $panes[] = '<div'.Html::attr($pane).">$content</div>";
                    unset($items[$k]);
                }
                else
                {
                    $target = Params::extract($tab, self::P_TARGET);
                    if (Params::extract($tab, self::P_ACTIVE))
                        Params::add($tab, 'active');
                    $labels[] = '<li'.Html::attr($tab)."><a href=\"$target\">$label</a></li>";
                }
            }
            else
                $labels[] = "<li><a href=\"#\">$tab</a></li>";
        }
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);
        Params::add($params, 'nav nav-tabs');

        return '<ul'.Html::attr($params).">$prefix".implode('', $labels)."$suffix</ul>"
            . ($panes
                ? ('<div class="tab-content">'.implode('', $panes).'</div>')
                : ''
                )
            ;
    }

    /** returns a div formatted as a well block
     * @param array $params {P_CONTENT:'well block body'
     *                      , div tag arguments
     *                      }
     * @return string
     */
    public static function well($params)
    {
        $body = Params::extract($params, self::P_CONTENT);
        Params::add($params, 'well');

        return '<div'.Html::attr($params).">$body</div>";
    }

    /** inject width specification into attributes array
     * @param int|string $width width specification (nbr of grid units or css value)
     * @param array $attrs      attributes array
     * @return array
     */
    public static function width2Attr($width, $attrs=array())
    {
        if (is_int($width))
            Params::add($attrs, "col-sm-{$width}");
        elseif (is_array($width))
            foreach ($width as $mode=>$w)
                Params::add($attrs, "col-$mode-$w");
        elseif (isset($width))
            Params::add($attrs, "width:{$width};", 'style', '');
        else
            return null;

        return $attrs;
    }

    /** inject offset specification into attributes array
     * @param int|string $width width specification (nbr of grid units or css value)
     * @param array $attrs      attributes array
     * @return array
     */
    public static function widthOffset2Attr($width, $attrs=array())
    {
        if (is_int($width))
            Params::add($attrs, "col-sm-offset-{$width}");
        else
            Params::add($attrs, "margin-left:{$width};", 'style', '');

        return $attrs;
    }
}
