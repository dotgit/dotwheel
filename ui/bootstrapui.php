<?php

/**
 * Description of BootstrapUi
 *
 * @author stas trefilov
 *
 * methods to remove:
 * alert*()
 * breadcrumbs()
 * collapseContainer()
 * collapseOpenerButton()
 * pagination*()
 * register*()
 * well()
 */

namespace Dotwheel\Ui;

use Dotwheel\Util\Misc;
use Dotwheel\Util\Params;

class BootstrapUi
{
    // font-awesome support classes
    const ICN_BASE  = 'fa';
    const ICN_2X    = 'fa-2x';
    const ICN_LG    = 'fa-lg';
    const ICN_FIXED = 'fa-fw';
    const ICN_STACK = 'fa-stack';

    // font-awesome icon classes
    const ICN_CALENDAR  = 'fa-calendar';
    const ICN_WARNING   = 'fa-exclamation-triangle';

    const PGN_ACTIVE    = 1;
    const PGN_LAST      = 2;
    const PGN_LIST      = 3;
    const PGN_LINK_1    = 4;

    const MDL_FOCUS_FN  = 'focusModalBtn';

    const P_WIDTH           = 1;
    const P_CONTENT         = 2;
    const P_CONTENT_ATTR    = 3;
    const P_HEADER          = 4;
    const P_HEADER_ATTR     = 5;
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

    // for P_WIDTH
    const W_XSMALL          = 'xs';
    const W_SMALL           = 'sm';
    const W_MIDDLE          = 'md';
    const W_LARGE           = 'lg';
    const W_XSMALL_OFFSET   = 'xs-offset';
    const W_SMALL_OFFSET    = 'sm-offset';
    const W_MIDDLE_OFFSET   = 'md-offset';
    const W_LARGE_OFFSET    = 'lg-offset';
    const W_XSMALL_PUSH     = 'xs-push';
    const W_SMALL_PUSH      = 'sm-push';
    const W_MIDDLE_PUSH     = 'md-push';
    const W_LARGE_PUSH      = 'lg-push';
    const W_XSMALL_PULL     = 'xs-pull';
    const W_SMALL_PULL      = 'sm-pull';
    const W_MIDDLE_PULL     = 'md-pull';
    const W_LARGE_PULL      = 'lg-pull';
    const WIDTH_1           = 12;
    const WIDTH_11_12       = 11;
    const WIDTH_5_6         = 10;
    const WIDTH_3_4         = 9;
    const WIDTH_2_3         = 8;
    const WIDTH_7_12        = 7;
    const WIDTH_1_2         = 6;
    const WIDTH_5_12        = 5;
    const WIDTH_1_3         = 4;
    const WIDTH_1_4         = 3;
    const WIDTH_1_6         = 2;
    const WIDTH_1_12        = 1;



    /** returns a div formatted as alert block
     * @param array $params {P_HEADER:'header', P_HEADER_ATTR:{header tag attributes}
     *                      , P_CLOSE:true // show close btn?
     *                      , P_CONTENT:'alert body'
     *                      , div tag arguments
     *                      }
     * @return string
     */
    public static function alert($params)
    {
        if (\is_array($params))
        {
            $body = Params::extract($params, self::P_CONTENT);
            if ($header = Params::extract($params, self::P_HEADER))
            {
                $header_attr = Params::extract($params, self::P_HEADER_ATTR, array());
                $body = '<h4'.Html::attr($header_attr).'>'.$header.'</h4>'.$body;
            }
            if ($close = Params::extract($params, self::P_CLOSE))
            {
                if (\is_array($close))
                    Params::add($close, 'alert', 'data-dismiss');
                else
                    $close = array('data-dismiss'=>'alert');
                $body = self::close($close) . $body;
                Params::add($params, 'alert-dismissable');
            }
            Params::add($params, 'alert');

            return '<div'.Html::attr($params).">$body</div>";
        }
        else
            return "<div class=\"alert\">$params</div>";
    }

    public static function alertWithIcon($params)
    {
        $body = Params::extract($params, self::P_CONTENT);
        Params::add($params, 'clearfix');
        $icon = self::icon(array(
            self::P_HEADER=>self::ICN_WARNING.' '.self::ICN_2X.' pull-left',
            'style'=>'margin:0.25em 0.5em 0 0;'
        ));

        return "<div".Html::attr($params).">$icon$body</div>";
    }

    /** format as comment line
     * @param array|string $comment {P_CONTENT:'comment', d.t.a.}|'comment to format as help block'
     * @return string
     */
    public static function asComment($comment)
    {
        if (\is_array($comment))
        {
            $c = Params::extract($comment, self::P_CONTENT);
            Params::add($comment, 'help-block');
            return "<div".Html::attr($comment).">$c</div>";
        }
        elseif (isset($comment))
            return "<div class=\"help-block\">$comment</div>";
        else
            return null;
    }

    /** format as form group
     * @param array|string $control {P_CONTENT:'form group content'
     *                              , P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.}
     *                              , P_TARGET:'label tag for attribute target'
     *                              , P_HEADER:'label content'
     *                              , P_HEADER_ATTR:{P_WIDTH:2, label tag attributes}
     *                              , d.t.a.
     *                              }
     *                              | 'content to format as form group'
     * @return string
     */
    public static function asFormGroup($control)
    {
        if (\is_array($control))
        {
            $content = self::fldToHtml($control);
            unset(
                $control[self::P_HEADER],
                $control[self::P_HEADER_ATTR],
                $control[self::P_CONTENT],
                $control[self::P_CONTENT_ATTR],
                $control[self::P_TARGET]
            );
            Params::add($control, 'form-group');

            return "<div".Html::attr($control).">$content</div>";
        }
        elseif (isset($control))
            return "<div class=\"form-group\">$control</div>";
        else
            return null;
    }

    public static function asFormGroupHorizontal($control)
    {
        if (isset($control))
        {
            if (!\is_array($control))
                $control = array(self::P_CONTENT=>$control);

            $header_attr = Params::extract($control, self::P_HEADER_ATTR, array());
            $h_w = Params::extract($header_attr, self::P_WIDTH, self::WIDTH_1_4);
            $content_attr = Params::extract($control, self::P_CONTENT_ATTR, array());
            $c_w = Params::extract($content_attr, self::P_WIDTH, self::WIDTH_3_4);
            if (! isset($control[self::P_HEADER])
                and \is_int($h_w)
                and \is_int($c_w)
            )
            {
                $c_w = array(self::W_SMALL_OFFSET=>$h_w, self::W_SMALL=>$c_w);
                $h_w = null;
            }

            // replace headers args with widths
            if ($h_w)
                $header_attr = static::width2Attr($h_w, $header_attr);
            if ($header_attr)
                Params::add($header_attr, 'control-label');
            $control[self::P_HEADER_ATTR] = $header_attr;

            // replace content args with widths
            if ($c_w)
                $content_attr = static::width2Attr($c_w, $content_attr);
            if ($content_attr)
                $control[self::P_CONTENT_ATTR] = $content_attr;

            return self::asFormGroup($control);
        }
        else
            return null;
    }

    /** formats the control to be displayed as horizontal form row
     * @param array|string $control {P_HEADER_ATTR:{P_WIDTH:2, label tag attributes}
     *                              , P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.}
     *                              , d.t.a.
     *                              }
     *                              | 'content to format as form group'
     * @return string
     */
    public static function asFormGroupHorizontalRow($control)
    {
        if (isset($control))
        {
            if (!\is_array($control))
                $control = array(self::P_CONTENT=>$control);

            Params::add($control, 'row');

            return self::asFormGroupHorizontal($control);
        }
        else
            return null;
    }

    /** get button element
     * @param array $params {{P_TARGET:'pane id'
     *                          , P_HEADER:'tab label'
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
            if (\is_array($item))
            {
                $target = Params::extract($item, self::P_TARGET);
                $header = Params::extract($item, self::P_HEADER);
                if (Params::extract($item, self::P_ACTIVE))
                {
                    Params::add($item, 'active');
                    $items[] = '<li'.Html::attr($item).">$header</li>\n";
                }
                else
                    $items[] = '<li'.Html::attr($item)."><a href=\"$target\">$header</a></li>\n";
                unset($params[$k]);
            }
            else
                $items[] = "<li class=\"active\">$item</li>";
        }
        Params::add($params, 'breadcrumb');

        return '<ul'.Html::attr($params).'>'.\implode('', $items).'</ul>';
    }

    /** get button element
     * @param array $params {P_HEADER:button value, button tag attributes}
     * @return string
     */
    public static function button($params)
    {
        $header = Params::extract($params, self::P_HEADER);
        $params += array('type'=>'button');
        Params::add($params, 'btn');

        return '<button'.Html::attr($params).">$header</button>";
    }

    /** get close icon for alert modal
     * @param array $params {button tag attributes}
     * @return string
     */
    public static function close($params=array())
    {
        self::registerAlert();

        Params::add($params, 'close');
        Params::add($params, 'button', 'type');

        return '<button'.Html::attr($params).">&times;</button>";
    }

    /** returns collapsed container (hidden by default)
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
     *                      , P_HEADER:'button text'
     *                      , P_HEADER_ATTR:{class:ICN_FILTER}
     *                      , button div attributes
     *                      }
     * @return string
     */
    public static function collapseOpenerButton($params)
    {
        static $cnt = 0;

        self::registerButton();
        self::registerCollapse();

        $id = Params::extract($params, 'id', 'clps_btn_'.++$cnt);
        $id_target = Params::extract($params, self::P_TARGET);
        $prefix = ($header_attr = Params::extract($params, self::P_HEADER_ATTR, array()))
            ? ('<i'.Html::attr($header_attr).'></i> ')
            : '';
        $prefix .= ($header = Params::extract($params, self::P_HEADER))
            ? "$header "
            : '';

        HtmlPage::add(array(HtmlPage::DOM_READY=>
<<<EOco
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

        return self::button(array(self::P_HEADER=>"$prefix<span class=\"caret\"></span>") + $params);
    }

    /** returns a collapsible group
     * @param array $params {P_HEADER:'group label'
     *                      , P_HEADER_ATTR:{additional label div attributes}
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

        $header_attr = Params::extract($params, self::P_HEADER_ATTR, array());
        Params::add($header_attr, 'collapse', 'data-toggle');
        if (isset($id))
            Params::add($header_attr, "#$id", 'href');

        $header = Params::extract($params, self::P_HEADER);
        $content = Params::extract($params, self::P_CONTENT);
        $content_attr = Params::extract($params, self::P_CONTENT_ATTR);
        $addon_prefix = Params::extract($params, self::P_PREFIX);
        $addon_suffix = Params::extract($params, self::P_SUFFIX);
        $footer = Params::extract($params, self::P_FOOTER);
        $wrap_fmt = Params::extract($params, self::P_WRAP_FMT, '%s');

        Params::add($params, 'panel-collapse');
        Params::add($params, 'collapse');

        return self::panel(array(
            self::P_HEADER=>'<a'.Html::attr($header_attr).'><div>'.$header.'</div></a>',
            self::P_CONTENT=>$content,
            self::P_CONTENT_ATTR=>$content_attr,
            self::P_PREFIX=>$addon_prefix,
            self::P_SUFFIX=>$addon_suffix,
            self::P_FOOTER=>$footer,
            self::P_WRAP_FMT=>Misc::sprintfEscape('<div'.Html::attr($params).'>')."$wrap_fmt</div>"
        ));
    }

    /** generate dropdown list
     * @param string $items ['item 1 html',
     *  {P_HEADER:'item 2 html', li tag attributes}, null, 'item post divider']
     * @param array $attr   hash of ul tag attributes
     * @return string
     */
    public static function dropdown($items, $attr=array())
    {
        Params::add($attr, 'dropdown-menu');
        Params::add($attr, 'menu', 'role');

        $li = array();
        foreach ($items as $item)
        {
            if (isset($item))
            {
                if (\is_array($item))
                {
                    $label = Params::extract($item, self::P_HEADER);
                    Params::add($item, 'presentatoion', 'role');
                    $li_attr = Html::attr($item);
                }
                else
                {
                    $label = $item;
                    $li_attr = ' role="presentation"';
                }
            }
            else
            {
                $label = '';
                $li_attr = ' role="presentation" class="divider"';
            }

            $li[] = "<li$li_attr>$label</li>";
        }

        return '<ul'.Html::attr($attr).'>'.
            \implode('', $li).
            '</ul>';
    }

    /** display button with dropdown menu
     * @param string $params {P_HEADER_ATTR: {button attributes},
     *  P_HEADER: 'button label, encoded',
     *  P_CONTENT_ATTR: {dropdown attributes},
     *  P_CONTENT: [dropdown items],
     *  parent div attributes
     * }
     * @return string
     */
    public static function dropdownButton($params)
    {
        $btn_attr = Params::extract($params, self::P_HEADER_ATTR, array());
        $header = Params::extract($params, self::P_HEADER);
        $dropdown_attr = Params::extract($params, self::P_CONTENT_ATTR, array());
        $items = Params::extract($params, self::P_CONTENT);

        Params::add($params, 'dropdown');
        Params::add($btn_attr, 'btn dropdown-toggle');
        Params::add($btn_attr, 'dropdown', 'data-toggle');

        return \sprintf(
<<<EObt
<div%s>
  <button%s>
    %s <span class="caret"></span>
  </button>
  %s
</div>
EObt
            ,
            Html::attr($params),
            Html::attr($btn_attr),
            $header,
            self::dropdown($items, $dropdown_attr)
        );
    }

    /** format as form group
     * @param array|string $control {P_CONTENT:'form group content'
     *                              , P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.}
     *                              , P_TARGET:'label tag for attribute target'
     *                              , P_HEADER:'label content'
     *                              , P_HEADER_ATTR:{P_WIDTH:2, label tag attributes}
     *                              , d.t.a.
     *                              }
     *                              | 'content to format as form group'
     * @return string
     */
    public static function fldToHtml($control)
    {
        if (!\is_array($control))
            return $control;

        $h = Params::extract($control, self::P_HEADER);
        $h_attr = Params::extract($control, self::P_HEADER_ATTR, array());
        if ($w = Params::extract($h_attr, self::P_WIDTH))
            $h_attr = static::width2Attr($w, $h_attr);

        if ($t = Params::extract($control, self::P_TARGET))
            Params::add($h_attr, $t, 'for');

        if (isset($h))
            $label = '<label'.Html::attr($h_attr).">$h</label>";
        else
            $label = null;

        $content = Params::extract($control, self::P_CONTENT);
        $content_attr = Params::extract($control, self::P_CONTENT_ATTR, array());
        if ($w = Params::extract($content_attr, self::P_WIDTH))
            $content_attr = static::width2Attr($w, $content_attr);
        if ($content_attr)
            $content = "<div".Html::attr($content_attr).">$content</div>";

        return "$label$content";
    }

    /** extracts prefix / suffix addons from the Ui parameters and returns sprintf
     * format to wrap the field html
     * @param array $ui {P_PREFIX:'input prefix addon'
     *      |{P_CONTENT:'prefix content'
     *          , P_HEADER_ATTR:{input group attributes}
     *          , P_ADDON_BTN:true
     *          , prefix arguments
     *      }
     *  , P_SUFFIX:'input suffix addon'
     *      |{P_CONTENT:'suffix content'
     *          , P_HEADER_ATTR:{input group attributes}
     *          , P_ADDON_BTN:true
     *          , suffix arguments
     *      }
     *  }
     * @return string   '%s' if no prefixes / suffixes detected, otherwise '...%s...'
     */
    public static function fmtAddons($ui)
    {
        $header = array();

        if ($prefix = Params::extract($ui, self::P_PREFIX))
        {
            if (\is_array($prefix))
            {
                $header = Params::extract($prefix, self::P_HEADER_ATTR, $header);
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
            if (\is_array($suffix))
            {
                $header = Params::extract($suffix, self::P_HEADER_ATTR, $header);
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
            Params::add($header, 'input-group');
            $header_attr = Html::attr($header);
            $prefix = Misc::sprintfEscape("<div$header_attr>$prefix");
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
        // phase 1: count columns in the row to get the number of columns
        $fld_count = 0;
        foreach ($columns as $k=>$col)
            if (\is_array($col) or \is_int($k))
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

        // phase 2: build row of columns
        $attr = array();
        $cols = array();
        foreach ($columns as $k=>$col)
        {
            if (\is_array($col))
            {
                if ($width = Params::extract($col, self::P_WIDTH, $width_default))
                    $col = static::width2Attr($width, $col);
                $content = Params::extract($col, self::P_CONTENT);
                $cols[] = '<div'.Html::attr($col).'>'.$content.'</div>';
            }
            elseif (\is_int($k))
            {
                $a = static::width2Attr($width_default);
                $cols[] = '<div'.Html::attr($a).'>'.$col.'</div>';
            }
            else
                $attr[$k] = $col;
        }
        Params::add($attr, 'row');

        return '<div'.Html::attr($attr).'>'.\implode('', $cols).'</div>';
    }

    /** get icon html
     * @param string|array $icon    icon code|{P_HEADER:'icon code', i tag attributes}
     * @return string
     */
    public static function icon($icon)
    {
        if (\is_array($icon))
        {
            $label = Params::extract($icon, self::P_HEADER);
            Params::add($icon, self::ICN_BASE);
            Params::add($icon, $label);

            return '<i'.Html::attr($icon).'></i>';
        }
        else
            return '<i class="'.self::ICN_BASE." $icon\"></i>";
    }

    public static function iconStack($icons)
    {
        $attr = array();
        $icns = array();
        foreach ($icons as $k=>$icn)
            if (\is_int($k))
                $icns[] = self::icon($icn);
            else
                $attr[$k] = $icn;
        Params::add($attr, self::ICN_STACK);

        return '<span'.Html::attr($attr).'>'.implode('', $icns).'</span>';
    }

    /** returns a div of list-group class
     * @param array $items  array of strings representing list items
     * @param array $attr   list-group attributes
     * @return string
     */
    public static function listGroup($items, $attr=array())
    {
        Params::add($attr, 'list-group');

        return '<div'.Html::attr($attr).'>'.implode('', $items).'</div>';
    }

    /** returns a BUTTON, A or DIV element of list-group-item class based on
     * whether the $attr contains:
     *
     * for BUTTON: type="button",
     * for A: href,
     * for DIV: none of the above.
     *
     * @param string $content   html content of the item
     * @param array $attr       list-group-item attributes
     * @return string
     */
    public static function listGroupItem($content, $attr=array())
    {
        Params::add($attr, 'list-group-item');
        $tag = isset($attr['href'])
            ? 'a'
            : ((isset($attr['type']) && $attr['type'] == 'button')
                ? 'button'
                : 'div'
            );

        return "<$tag".Html::attr($attr).">$content</$tag>";
    }

    /** returns a modal dialog window with specified header, body and buttons
     * @param array $params {P_HEADER:'dialog title'
     *                      , P_CONTENT:'dialog body'
     *                      , P_FOOTER:'dialog buttons row'
     *                      , P_WRAP_FMT:'%s' // wrap the form around the header / content / footer
     *                      , P_CLOSE:close button tag attributes
     *                      , container div attributes
     *                      }
     * @return string
     */
    public static function modal($params)
    {
        self::registerModal();

        $id = Params::extract($params, 'id');
        $close = Params::extract($params, self::P_CLOSE);
        $header = Params::extract($params, self::P_HEADER);
        $body = Params::extract($params, self::P_CONTENT);
        $body_attr = Params::extract($params, self::P_CONTENT_ATTR, array());
        $footer = Params::extract($params, self::P_FOOTER);
        $size = Params::extract($params, self::P_WIDTH);
        $wrap_fmt = Params::extract($params, self::P_WRAP_FMT, '%s');
        $focus_modal_btn = self::MDL_FOCUS_FN;

        Params::add($params, $id, 'id');
        Params::add($params, 'modal');
        Params::add($params, 'dialog', 'role');
        Params::add($params, 'true', 'aria-hidden');
        Params::add($params, 'true', 'data-keyboard');
        Params::add($body_attr, 'modal-body');

        if (\is_array($close))
        {
            Params::add($close, 'modal', 'data-dismiss');
            $close_html = self::close($close);
        }
        elseif ($close === false)
            $close_html = null;
        else
            $close_html = self::close(array('data-dismiss'=>'modal'));
        if (isset($header))
            $header = "<div class=\"modal-header\">$close_html<h4 class=\"modal-title\">$header</h4></div>";
        if (isset($body))
            $body = "<div".Html::attr($body_attr).">$body</div>";
        if (isset($footer))
            $footer = "<div class=\"modal-footer\">$footer</div>";
        if (isset($size))
            $size = " modal-$size";

        HtmlPage::add(array(
            HtmlPage::SCRIPT=>array(__METHOD__=>
<<<EOsc
function $focus_modal_btn(\$mdl){
    var \$btn=\$mdl.find('.btn:enabled').not('.btn-default');
    if(\$btn.length){
        if(\$btn.filter('.btn-primary').length)
            \$btn.filter('.btn-primary').first().focus();
        else
            \$btn.first().focus();
    }
    else
        $('[data-dismiss="modal"]:enabled',\$mdl).first().focus();
}

EOsc
            ),
            HtmlPage::DOM_READY=>array(__METHOD__."-$id"=>
                "$('#$id').on('shown.bs.modal',function(){{$focus_modal_btn}($(this));});"
            ),
        ));

        return \sprintf(
<<<EOfmt
<div%s>
  <div class="modal-dialog%s">
    <div class="modal-content">
      %s
    </div>
  </div>
</div>

EOfmt
            ,
            Html::attr($params),
            $size,
            \sprintf($wrap_fmt, "$header$body$footer")
        );
    }

    /** html-formatted bootstrap tabs
     * @param array $items  {{P_TARGET:'pane id'
     *      , P_HEADER:'tab label'
     *      , P_FOOTER:'tab label postfix'
     *      , P_HEADER_ATTR:a tag attributes
     *      , P_CONTENT:'pane content'
     *      , P_CONTENT_ATTR:a tag attributes
     *      , P_ACTIVE:bool
     *      }
     *  , li tag attributes
     *  }
     * @param array $params {P_PREFIX: 'prefix text'
     *  , P_SUFFIX: 'suffix text'
     *  , ul tag attributes
     *  }
     * @return type
     */
    public static function nav($items, $params=array())
    {
        self::registerTab();

        Params::add($params, 'nav');
        if (\strpos($params['class'], 'nav-pills') === false)
        {
            Params::add($params, 'nav-tabs');
            $toggle = 'tab';
        }
        else
            $toggle = 'pill';

        $labels = array();
        $panes = array();
        foreach ($items as $k=>$item)
        {
            if (\is_array($item))
            {
                $header = Params::extract($item, self::P_HEADER);
                $header_attr = Params::extract($item, self::P_HEADER_ATTR, array());
                $footer = Params::extract($item, self::P_FOOTER);
                $content = Params::extract($item, self::P_CONTENT);
                if (isset($content))
                {
                    $id = Params::extract($item, self::P_TARGET);
                    $content_attr = Params::extract($item, self::P_CONTENT_ATTR, array());
                    Params::add($content_attr, $id, 'id');
                    Params::add($content_attr, 'tab-pane');
                    if (Params::extract($item, self::P_ACTIVE))
                    {
                        Params::add($item, 'active');
                        Params::add($content_attr, 'active');
                    }
                    Params::add($header_attr, "#$id", 'href');
                    Params::add($header_attr, "$toggle", 'data-toggle');
                    $labels[] = '<li'.Html::attr($item)."><a".Html::attr($header_attr).">$header</a>$footer</li>";
                    $panes[] = '<div'.Html::attr($content_attr).">$content</div>";
                    unset($items[$k]);
                }
                else
                {
                    Params::add($header_attr, Params::extract($item, self::P_TARGET), 'href');
                    if (Params::extract($item, self::P_ACTIVE))
                        Params::add($item, 'active');
                    $labels[] = '<li'.Html::attr($item)."><a".Html::attr($header_attr).">$header</a>$footer</li>";
                }
            }
            else
                $labels[] = "<li><a href=\"#\">$item</a></li>";
        }
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);

        return
            '<ul'.Html::attr($params).">$prefix".\implode('', $labels)."$suffix</ul>".
            ($panes
                ? ('<div class="tab-content">'.\implode('', $panes).'</div>')
                : ''
            );
    }

    /** html-formatted pagination based on butons
     * @param array $params {PGN_ACTIVE:current page number
     *                      , PGN_LAST: last page number
     *                      , PGN_LIST: array of pages to display
     *                      , PGN_LINK_1: sprintf-formatted url with one parameter for page number
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
        $tail = \count($pages) - 1;

        if ($pages[0] > 1)
        {
            $ret[] =
                '<div class="btn-group">'.
                '<a class="btn" href="'.\sprintf($link_1, 1).'">1</a>'.
                '</div>';
            $ret[] =
                '<div class="btn-group">'.
                '<a class="btn" href="'.\sprintf($link_1, $pages[0] - 1).'">&larr; '.($pages[0] - 1).'</a>'.
                '</div>';
        }

        $ret[] = '<div class="btn-group">';
        foreach ($pages as $p)
            $ret[] = '<a class="btn'.($p == $active_page ? ' active' : '').'" href="'.\sprintf($link_1, $p).'">'.$p.'</a>';
        $ret[] = '</div>';

        if ($pages[$tail] < $last_page)
        {
            $ret[] =
                '<div class="btn-group">'.
                '<a class="btn" href="'.\sprintf($link_1, $pages[$tail] + 1).'">'.($pages[$tail] + 1).' &rarr;</a>'.
                '</div>';
            $ret[] =
                '<div class="btn-group">'.
                '<a class="btn" href="'.\sprintf($link_1, $last_page).'">'.$last_page.'</a>'.
                '</div>';
        }

        return \implode('', $ret);
    }

    /** html-formatted bootstrap pagination
     * @param array $params {PGN_ACTIVE:current page number
     *                      , PGN_LIST: array of pages to display
     *                      , PGN_LINK_1: sprintf-formatted url with one parameter for page number
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
            $s[] = '<li><a href="'.\sprintf($link_1, $active_page - 1).'">&larr;</a></li>';
        foreach ($pages as $n)
        {
            if ($n == $active_page)
                $s[] = '<li class="active"><span>'.$n.'</span></li>';
            else
                $s[] = '<li><a href="'.\sprintf($link_1, $n).'">'.$n.'</a></li>';
        }
        if ($active_page < $n)
            $s[] = '<li><a href="'.\sprintf($link_1, $active_page + 1).'">&rarr;</a></li>';
        $s[] = '</ul>';

        return \implode('', $s);
    }

    /** returns the panel html code
     * @param array $params {P_HEADER:'panel heading'
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
        if ($heading = Params::extract($params, self::P_HEADER))
            $heading = "<div class=\"panel-heading\">$heading</div>";
        if ($footer = Params::extract($params, self::P_FOOTER))
            $footer = "<div class=\"panel-footer clearfix text-right\">$footer</div>";
        $fmt = Params::extract($params, self::P_WRAP_FMT, '%s');
        $content_attr = Params::extract($params, self::P_CONTENT_ATTR, array());
        Params::add($content_attr, 'panel-body');
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);
        if ($content = Params::extract($params, self::P_CONTENT))
            $content = "<div".Html::attr($content_attr).">$content</div>";
        Params::add($params, 'panel');
        Params::add($params, 'panel-default');

        return
            '<div'.Html::attr($params).'>'.
            $heading.
            \sprintf($fmt, $prefix.$content.$suffix.$footer).
            '</div>';
    }

    /** html-formatted pagination based on butons
     * @param array $params {P_CONTENT: content visible inside the bar
     *                      , P_WIDTH: width of the bar
     *                      , P_HEADER_ATTR: div tag arguments of the progress outer container
     *                      , div tag arguments of the bar container
     *                      }
     * @return string buttons representing pages
     */
    public static function progress($params)
    {
        $content = Params::extract($params, self::P_CONTENT);
        $width = Params::extract($params, self::P_WIDTH);
        Params::add($params, 'progress-bar');
        Params::add($params, 'progressbar', 'role');
        $attr = Html::attr(self::width2Attr($width, $params));
        $header_attr = Params::extract($params, self::P_HEADER_ATTR, array());
        Params::add($header_attr, 'progress');
        $h_attr = Html::attr($header_attr);

        return "<div$h_attr><div$attr>$content</div></div>";
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

    /** returns the popover html code
     * @param array $params {P_HEADER:'popover heading'
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
            if (\is_array($close))
                Params::add($close, 'popover', 'data-dismiss');
            else
                $close = array('data-dismiss'=>'popover');
            $close = self::close($close);
        }

        $title = Params::extract($params, self::P_HEADER);
        $id = Params::extract($params, self::P_TARGET);

        $options = array(
            'title'=>$title,
            'content'=>Params::extract($params, self::P_CONTENT),
            'placement'=>Params::extract($params, self::P_ALIGN, 'bottom'),
            'html'=>true,
            'container'=>'body',
        );

        HtmlPage::add(array(
            HtmlPage::DOM_READY=>array(__METHOD__."-$id"=>"$('#$id').popover(".\json_encode($options).');')
        ));

        return $params;
    }

    /** register tab js */
    public static function registerTab()
    {
    }

    /** register tooltip js */
    public static function registerTooltip()
    {
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
     *      | {'sm':WIDTH_1, 'lg':WIDTH_1_2}
     *      | '100%'
     * @param array $attrs      attributes array
     * @return array
     */
    public static function width2Attr($width, $attrs=array())
    {
        if (\is_int($width))
            Params::add($attrs, "col-sm-{$width}");
        elseif (\is_array($width))
            foreach ($width as $mode=>$w)
                Params::add($attrs, "col-$mode-$w");
        elseif (isset($width))
            Params::add($attrs, "width:{$width};", 'style', '');
        else
            return null;

        return $attrs;
    }
}
