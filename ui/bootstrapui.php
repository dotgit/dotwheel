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
    public const ICN_BASE = 'fa';
    public const ICN_LG = 'fa-lg';
    public const ICN_2X = 'fa-2x';
    public const ICN_3X = 'fa-3x';
    public const ICN_4X = 'fa-4x';
    public const ICN_5X = 'fa-5x';
    public const ICN_FIXED = 'fa-fw';
    public const ICN_UL = 'fa-ul';
    public const ICN_LI = 'fa-li';
    public const ICN_BORDER = 'fa-border';
    public const ICN_PULL_LEFT = 'fa-pull-left';
    public const ICN_PULL_RIGHT = 'fa-pull-right';
    public const ICN_SPIN = 'fa-spin';
    public const ICN_PULSE = 'fa-pulse';
    public const ICN_ROTATE_90 = 'fa-rotate-90';
    public const ICN_ROTATE_180 = 'fa-rotate-180';
    public const ICN_ROTATE_270 = 'fa-rotate-270';
    public const ICN_FLIP_HORIZONTAL = 'fa-flip-horizontal';
    public const ICN_FLIP_VERTICAL = 'fa-flip-vertical';
    public const ICN_STACK = 'fa-stack';
    public const ICN_STACK_1X = 'fa-stack-1x';
    public const ICN_STACK_2X = 'fa-stack-2x';
    public const ICN_INVERSE = 'fa-inverse';

    // font-awesome icon classes
    public const ICN_CALENDAR = 'fa-calendar';
    public const ICN_WARNING = 'fa-exclamation-triangle';

    public const PGN_ACTIVE = 1;
    public const PGN_LAST = 2;
    public const PGN_LIST = 3;
    public const PGN_LINK_1 = 4;

    public const MDL_FOCUS_FN = 'focusModalBtn';

    public const P_WIDTH = 1;
    public const P_CONTENT = 2;
    public const P_CONTENT_ATTR = 3;
    public const P_HEADER = 4;
    public const P_HEADER_ATTR = 5;
    public const P_FOOTER = 6;
    public const P_FOOTER_ATTR = 7;
    public const P_FORM_TYPE = 8;
    public const P_TARGET = 9;
    public const P_ACTIVE = 10;
    public const P_CLOSE = 11;
    public const P_WRAP_FMT = 12;
    public const P_HIDDEN = 13;
    public const P_READONLY = 14;
    public const P_STATIC = 15;
    public const P_PREFIX = 16;
    public const P_SUFFIX = 17;
    public const P_ADDON_BTN = 18;
    public const P_ALIGN = 19;
    public const P_REQUIRED = 20;

    // for P_FORM_TYPE
    public const FT_HORIZONTAL = 1;

    // for P_WIDTH
    public const W_XSMALL = 'xs';
    public const W_SMALL = 'sm';
    public const W_MIDDLE = 'md';
    public const W_LARGE = 'lg';
    public const W_XSMALL_OFFSET = 'xs-offset';
    public const W_SMALL_OFFSET = 'sm-offset';
    public const W_MIDDLE_OFFSET = 'md-offset';
    public const W_LARGE_OFFSET = 'lg-offset';
    public const W_XSMALL_PUSH = 'xs-push';
    public const W_SMALL_PUSH = 'sm-push';
    public const W_MIDDLE_PUSH = 'md-push';
    public const W_LARGE_PUSH = 'lg-push';
    public const W_XSMALL_PULL = 'xs-pull';
    public const W_SMALL_PULL = 'sm-pull';
    public const W_MIDDLE_PULL = 'md-pull';
    public const W_LARGE_PULL = 'lg-pull';
    public const WIDTH_1 = 12;
    public const WIDTH_11_12 = 11;
    public const WIDTH_5_6 = 10;
    public const WIDTH_3_4 = 9;
    public const WIDTH_2_3 = 8;
    public const WIDTH_7_12 = 7;
    public const WIDTH_1_2 = 6;
    public const WIDTH_5_12 = 5;
    public const WIDTH_1_3 = 4;
    public const WIDTH_1_4 = 3;
    public const WIDTH_1_6 = 2;
    public const WIDTH_1_12 = 1;

    /** return a div formatted as alert block
     *
     * @param array|string $params {P_HEADER:'header',
     *  P_CLOSE:true|{close button tag arguments} // show close btn?,
     *  P_PREFIX:'icon code',
     *  P_CONTENT:'alert body',
     *  div tag arguments,
     * }
     * | "string to output in alert modal"
     * @return string
     */
    public static function alert($params): string
    {
        if (is_array($params)) {
            $body = Params::extract($params, self::P_CONTENT);
            if ($header = Params::extract($params, self::P_HEADER)) {
                $body = "<strong>$header</strong>&nbsp; $body";
            }
            if ($prefix = Params::extract($params, self::P_PREFIX)) {
                $body = "$prefix$body";
            }
            if ($close = Params::extract($params, self::P_CLOSE)) {
                if (is_array($close)) {
                    Params::add($close, 'alert', 'data-dismiss');
                } else {
                    $close = ['data-dismiss' => 'alert'];
                }
                $body = self::close($close) . $body;
                Params::add($params, 'alert-dismissable');
            }
            Params::add($params, 'alert', 'role');
            Params::add($params, 'alert');
            Params::add($params, 'clearfix');

            return '<div' . Html::attr($params) . ">$body</div>";
        } else {
            return "<div class=\"alert\">$params</div>";
        }
    }

    /** format as comment line
     *
     * @param array|string $comment {P_CONTENT:'comment', d.t.a.}|'comment to format as help block'
     * @return ?string
     */
    public static function asComment($comment): ?string
    {
        if (is_array($comment)) {
            $c = Params::extract($comment, self::P_CONTENT);
            Params::add($comment, 'help-block');

            return "<div" . Html::attr($comment) . ">$c</div>";
        } elseif (isset($comment)) {
            return "<div class=\"help-block\">$comment</div>";
        } else {
            return null;
        }
    }

    /** format as form group
     *
     * @param array|string $control {P_CONTENT:'form group content',
     *  P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.},
     *  P_TARGET:'label tag for attribute target',
     *  P_HEADER:'label content',
     *  P_HEADER_ATTR:{P_WIDTH:2, label tag attributes},
     *  d.t.a.,
     * }
     * | 'content to format as form group'
     * @return ?string
     */
    public static function asFormGroup($control): ?string
    {
        if (is_array($control)) {
            $content = self::fldToHtml($control);
            unset(
                $control[self::P_HEADER],
                $control[self::P_HEADER_ATTR],
                $control[self::P_CONTENT],
                $control[self::P_CONTENT_ATTR],
                $control[self::P_TARGET]
            );
            Params::add($control, 'form-group');

            return "<div" . Html::attr($control) . ">$content</div>";
        } elseif (isset($control)) {
            return "<div class=\"form-group\">$control</div>";
        } else {
            return null;
        }
    }

    /** format as horizontal form group
     *
     * @param $control
     * @return ?string
     */
    public static function asFormGroupHorizontal($control): ?string
    {
        if (isset($control)) {
            if (!is_array($control)) {
                $control = [self::P_CONTENT => $control];
            }

            $header_attr = Params::extract($control, self::P_HEADER_ATTR, []);
            $h_w = Params::extract($header_attr, self::P_WIDTH, self::WIDTH_1_4);
            $content_attr = Params::extract($control, self::P_CONTENT_ATTR, []);
            $c_w = Params::extract($content_attr, self::P_WIDTH, self::WIDTH_3_4);
            if (!isset($control[self::P_HEADER])
                and is_int($h_w)
                and is_int($c_w)
            ) {
                $c_w = [self::W_SMALL_OFFSET => $h_w, self::W_SMALL => $c_w];
                $h_w = null;
            }

            // replace headers args with widths
            if ($h_w) {
                $header_attr = static::width2Attr($h_w, $header_attr);
            }
            if ($header_attr) {
                Params::add($header_attr, 'control-label');
            }
            $control[self::P_HEADER_ATTR] = $header_attr;

            // replace content args with widths
            if ($c_w) {
                $content_attr = static::width2Attr($c_w, $content_attr);
            }
            if ($content_attr) {
                $control[self::P_CONTENT_ATTR] = $content_attr;
            }

            return self::asFormGroup($control);
        } else {
            return null;
        }
    }

    /** formats the control to be displayed as horizontal form row
     *
     * @param array|string $control {P_HEADER_ATTR:{P_WIDTH:2, label tag attributes},
     *  P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.},
     *  d.t.a.,
     * }
     * | 'content to format as form group'
     * @return ?string
     */
    public static function asFormGroupHorizontalRow($control): ?string
    {
        if (isset($control)) {
            if (!is_array($control)) {
                $control = [self::P_CONTENT => $control];
            }
            Params::add($control, 'row');

            return self::asFormGroupHorizontal($control);
        } else {
            return null;
        }
    }

    /** get button element
     *
     * @param array $params {{P_TARGET:'pane id',
     *      P_HEADER:'tab label',
     *      P_ACTIVE:bool,
     *      P_CONTENT:'element content',
     *      },
     *  ul tag attributes,
     *  }
     * @return string
     */
    public static function breadcrumbs(array $params): string
    {
        self::registerBreadcrumbs();

        $items = [];
        foreach ($params as $k => $item) {
            if (is_array($item)) {
                $content = Params::extract($item, self::P_CONTENT);
                if (isset($content)) {
                    $items[] = $content;
                } else {
                    $target = Params::extract($item, self::P_TARGET);
                    $header = Params::extract($item, self::P_HEADER);
                    if (Params::extract($item, self::P_ACTIVE)) {
                        Params::add($item, 'active');
                        $items[] = '<li' . Html::attr($item) . ">$header</li>\n";
                    } else {
                        $items[] = '<li' . Html::attr($item) . "><a href=\"$target\">$header</a></li>\n";
                    }
                }
                unset($params[$k]);
            } else {
                $items[] = "<li class=\"active\">$item</li>";
            }
        }
        Params::add($params, 'breadcrumb');

        return '<ul' . Html::attr($params) . '>' . implode('', $items) . '</ul>';
    }

    /** get button element
     *
     * @param array $params {P_HEADER:button value, button tag attributes}
     * @return string
     */
    public static function button(array $params): string
    {
        $header = Params::extract($params, self::P_HEADER);
        $params += ['type' => 'button'];
        Params::add($params, 'btn');

        return '<button' . Html::attr($params) . ">$header</button>";
    }

    /** get close icon for alert modal
     *
     * @param array $params {button tag attributes}
     * @return string
     */
    public static function close(array $params = []): string
    {
        self::registerAlert();

        Params::add($params, 'close');
        Params::add($params, 'button', 'type');

        return '<button' . Html::attr($params) . '><span aria-hidden="true">&times;</span></button>';
    }

    /** return collapsed container (hidden by default)
     *
     * @param array $params {P_CONTENT:'content body',
     *  container tag attributes,
     * }
     */
    public static function collapseContainer(array $params)
    {
        self::registerCollapse();

        $content = Params::extract($params, self::P_CONTENT);
        Params::add($params, 'collapse');

        return '<div' . Html::attr($params) . ">$content</div>";
    }

    /** return dropdown button coupled with collapsed container and a js to control caret display
     *
     * @staticvar int $cnt  to generate missing id
     * @param array $params {P_TARGET:'target id',
     *  P_HEADER:'button text',
     *  P_HEADER_ATTR:{class:ICN_FILTER},
     *  button div attributes,
     * }
     * @return string
     */
    public static function collapseOpenerButton(array $params): string
    {
        static $cnt = 0;

        self::registerButton();
        self::registerCollapse();

        $id = Params::extract($params, 'id', 'clps_btn_' . ++$cnt);
        $id_target = Params::extract($params, self::P_TARGET);
        $prefix = ($header_attr = Params::extract($params, self::P_HEADER_ATTR, []))
            ? ('<i' . Html::attr($header_attr) . '></i> ')
            : '';
        $prefix .= ($header = Params::extract($params, self::P_HEADER)) ? "$header " : '';

        HtmlPage::add([
            HtmlPage::DOM_READY =>
                <<<EOco
                $('#$id_target')
                .on('show',function(){\$('#$id').addClass('dropup').removeClass('dropdown').button('toggle');})
                .on('hide',function(){\$('#$id').addClass('dropdown').removeClass('dropup').button('toggle');})
                ;
                EOco,
        ]);

        Params::add($params, $id, 'id');
        Params::add($params, 'collapse', 'data-toggle');
        Params::add($params, "#$id_target", 'data-target');
        Params::add($params, 'dropdown');

        return self::button([self::P_HEADER => "$prefix<span class=\"caret\"></span>"] + $params);
    }

    /** return a collapsible group
     *
     * @param array $params {P_HEADER:'group label',
     *  P_HEADER_ATTR:{additional label div attributes},
     *  P_CONTENT:'collapsible content',
     *  P_CONTENT_ATTR:{additional content div attributes},
     *  P_FOOTER:'panel footer',
     *  P_FOOTER_ATTR:{additional footer div attributes},
     *  'id':'content div id',
     *  additional content div attributes,
     * }
     * @return string
     */
    public static function collapsible(array $params): string
    {
        $id = $params['id'] ?? null;

        $header_attr = Params::extract($params, self::P_HEADER_ATTR, []);
        Params::add($header_attr, 'collapse', 'data-toggle');
        if (isset($id)) {
            Params::add($header_attr, "#$id", 'href');
        }

        $header = Params::extract($params, self::P_HEADER);
        $content = Params::extract($params, self::P_CONTENT);
        $content_attr = Params::extract($params, self::P_CONTENT_ATTR);
        $addon_prefix = Params::extract($params, self::P_PREFIX);
        $addon_suffix = Params::extract($params, self::P_SUFFIX);
        $footer = Params::extract($params, self::P_FOOTER);
        $footer_attr = Params::extract($params, self::P_FOOTER_ATTR);
        $wrap_fmt = Params::extract($params, self::P_WRAP_FMT, '%s');

        Params::add($params, 'panel-collapse');
        Params::add($params, 'collapse');

        return self::panel([
            self::P_HEADER => '<a' . Html::attr($header_attr) . '><div>' . $header . '</div></a>',
            self::P_CONTENT => $content,
            self::P_CONTENT_ATTR => $content_attr,
            self::P_PREFIX => $addon_prefix,
            self::P_SUFFIX => $addon_suffix,
            self::P_FOOTER => $footer,
            self::P_FOOTER_ATTR => $footer_attr,
            self::P_WRAP_FMT => Misc::sprintfEscape('<div' . Html::attr($params) . '>') . "$wrap_fmt</div>",
        ]);
    }

    /** generate dropdown list
     *
     * @param array $items ['item 1 html',
     *  {P_HEADER:'item 2 html', li tag attributes},
     *  null,
     *  'item post divider'
     * ]
     * @param array $attr hash of ul tag attributes
     * @return string
     */
    public static function dropdown(array $items, array $attr = []): string
    {
        Params::add($attr, 'dropdown-menu');
        Params::add($attr, 'menu', 'role');

        $li = [];
        foreach ($items as $item) {
            if (isset($item)) {
                if (is_array($item)) {
                    $label = Params::extract($item, self::P_HEADER);
                    Params::add($item, 'presentation', 'role');
                    $li_attr = Html::attr($item);
                } else {
                    $label = $item;
                    $li_attr = ' role="presentation"';
                }
            } else {
                $label = '';
                $li_attr = ' role="separator" class="divider"';
            }

            $li[] = "<li$li_attr>$label</li>";
        }

        return '<ul' . Html::attr($attr) . '>' . implode('', $li) . '</ul>';
    }

    /** display button with dropdown menu
     *
     * @param array $params {P_HEADER_ATTR: {button attributes},
     *  P_HEADER: 'button label, encoded',
     *  P_CONTENT_ATTR: {dropdown attributes},
     *  P_CONTENT: [dropdown items],
     *  parent div attributes
     * }
     * @return string
     */
    public static function dropdownButton(array $params): string
    {
        $btn_attr = Params::extract($params, self::P_HEADER_ATTR, []);
        $header = Params::extract($params, self::P_HEADER);
        $dropdown_attr = Params::extract($params, self::P_CONTENT_ATTR, []);
        $items = Params::extract($params, self::P_CONTENT);

        Params::add($params, 'dropdown');
        Params::add($btn_attr, 'btn dropdown-toggle');
        $btn_attr += [
            'type' => 'button',
            'data-toggle' => 'dropdown',
            'aria-haspopup' => 'true',
            'aria-expanded' => 'true',
        ];

        return sprintf(
            <<<EObt
            <div%s>
              <button%s>
                %s <span class="caret"></span>
              </button>
              %s
            </div>
            EObt,
            Html::attr($params),
            Html::attr($btn_attr),
            $header,
            self::dropdown($items, $dropdown_attr)
        );
    }

    /** format as form group
     *
     * @param array|string $control {P_CONTENT:'form group content',
     *  P_CONTENT_ATTR:{P_WIDTH:2, content d.t.a.},
     *  P_TARGET:'label tag for attribute target',
     *  P_HEADER:'label content',
     *  P_HEADER_ATTR:{P_WIDTH:2, label tag attributes},
     *  d.t.a.,
     * }
     * | 'content to format as form group'
     * @return string
     */
    public static function fldToHtml($control): string
    {
        if (!is_array($control)) {
            return $control;
        }

        $h = Params::extract($control, self::P_HEADER);
        $h_attr = Params::extract($control, self::P_HEADER_ATTR, []);
        if ($w = Params::extract($h_attr, self::P_WIDTH)) {
            $h_attr = static::width2Attr($w, $h_attr);
        }

        if ($t = Params::extract($control, self::P_TARGET)) {
            Params::add($h_attr, $t, 'for');
        }

        if (isset($h)) {
            $label = '<label' . Html::attr($h_attr) . ">$h</label>";
        } else {
            $label = null;
        }

        $content = Params::extract($control, self::P_CONTENT);
        $content_attr = Params::extract($control, self::P_CONTENT_ATTR, []);
        if ($w = Params::extract($content_attr, self::P_WIDTH)) {
            $content_attr = static::width2Attr($w, $content_attr);
        }
        if ($content_attr) {
            $content = "<div" . Html::attr($content_attr) . ">$content</div>";
        }

        return "$label$content";
    }

    /** extract prefix / suffix addons from the Ui parameters and return sprintf format to wrap the field html
     * @param array $ui {P_PREFIX:'input prefix addon'
     *      | {P_CONTENT:'prefix content',
     *          P_HEADER_ATTR:{input group attributes},
     *          P_ADDON_BTN:true,
     *          prefix arguments,
     *      },
     *  P_SUFFIX:'input suffix addon'
     *      | {P_CONTENT:'suffix content',
     *          P_HEADER_ATTR:{input group attributes},
     *          P_ADDON_BTN:true,
     *          suffix arguments,
     *      }
     * }
     * @return string   '%s' if no prefixes / suffixes detected, otherwise '...%s...'
     */
    public static function fmtAddons(array $ui): string
    {
        $header = [];

        if ($prefix = Params::extract($ui, self::P_PREFIX)) {
            if (is_array($prefix)) {
                $header = Params::extract($prefix, self::P_HEADER_ATTR, $header);
                $cnt = Params::extract($prefix, self::P_CONTENT);
                $class = Params::extract($prefix, self::P_ADDON_BTN) ? 'input-group-btn' : 'input-group-addon';
                Params::add($prefix, $class);
                $prefix = '<div' . Html::attr($prefix) . ">$cnt</div>";
            } else {
                $prefix = "<div class=\"input-group-addon\">$prefix</div>";
            }
        }

        if ($suffix = Params::extract($ui, self::P_SUFFIX)) {
            if (is_array($suffix)) {
                $header = Params::extract($suffix, self::P_HEADER_ATTR, $header);
                $cnt = Params::extract($suffix, self::P_CONTENT);
                $class = Params::extract($suffix, self::P_ADDON_BTN) ? 'input-group-btn' : 'input-group-addon';
                Params::add($suffix, $class);
                $suffix = '<div' . Html::attr($suffix) . ">$cnt</div>";
            } else {
                $suffix = "<div class=\"input-group-addon\">$suffix</div>";
            }
        }

        if ($prefix or $suffix) {
            Params::add($header, 'input-group');
            $header_attr = Html::attr($header);
            $prefix = Misc::sprintfEscape("<div$header_attr>$prefix");
            $suffix = Misc::sprintfEscape($suffix . '</div>');
        }

        return "$prefix%s$suffix";
    }

    /** return a div wrapper containing other divs for individual columns
     *
     * @param array $columns {
     *  {P_WIDTH:..., P_CONTENT:'cell_content'},
     *  'cell_content',
     *  'row_attr':'value',
     * }
     * @return string
     */
    public static function gridRow(array $columns): string
    {
        // phase 1: count columns in the row to get the number of columns
        $fld_count = 0;
        foreach ($columns as $k => $col) {
            if (is_array($col) or is_int($k)) {
                ++$fld_count;
            }
        }
        switch ($fld_count) {
            case 1:
                $width_default = self::WIDTH_1;
                break;
            case 2:
                $width_default = self::WIDTH_1_2;
                break;
            case 3:
                $width_default = self::WIDTH_1_3;
                break;
            case 4:
                $width_default = self::WIDTH_1_4;
                break;
            case 5:
            case 6:
                $width_default = self::WIDTH_1_6;
                break;
            default:
                $width_default = self::WIDTH_1_12;
        }

        // phase 2: build row of columns
        $attr = [];
        $cols = [];
        foreach ($columns as $k => $col) {
            if (is_array($col)) {
                if ($width = Params::extract($col, self::P_WIDTH, $width_default)) {
                    $col = static::width2Attr($width, $col);
                }
                $content = Params::extract($col, self::P_CONTENT);
                $cols[] = '<div' . Html::attr($col) . '>' . $content . '</div>';
            } elseif (is_int($k)) {
                $a = static::width2Attr($width_default);
                $cols[] = '<div' . Html::attr($a) . '>' . $col . '</div>';
            } else {
                $attr[$k] = $col;
            }
        }
        Params::add($attr, 'row');

        return '<div' . Html::attr($attr) . '>' . implode('', $cols) . '</div>';
    }

    /** get icon html
     *
     * @param string|array $icon icon code|{P_HEADER:'icon code', i tag attributes}
     * @return string
     */
    public static function icon($icon): string
    {
        if (is_array($icon)) {
            $label = Params::extract($icon, self::P_HEADER);
            Params::add($icon, self::ICN_BASE);
            Params::add($icon, $label);

            return '<i' . Html::attr($icon) . '></i>';
        } else {
            return '<i class="' . self::ICN_BASE . " $icon\"></i>";
        }
    }

    /** stackable icons
     *
     * @param array $icons
     * @return string
     */
    public static function iconStack(array $icons): string
    {
        $attr = [];
        $icns = [];
        foreach ($icons as $k => $icn) {
            if (is_int($k)) {
                $icns[] = self::icon($icn);
            } else {
                $attr[$k] = $icn;
            }
        }
        Params::add($attr, self::ICN_STACK);

        return '<span' . Html::attr($attr) . '>' . implode('', $icns) . '</span>';
    }

    /** return a div of list-group class
     *
     * @param array $items array of strings representing list items
     * @param array $attr list-group attributes
     * @return string
     */
    public static function listGroup(array $items, array $attr = []): string
    {
        Params::add($attr, 'list-group');

        return '<div' . Html::attr($attr) . '>' . implode('', $items) . '</div>';
    }

    /** return a BUTTON, A or DIV element of list-group-item class based on whether the $attr contains:
     * - for BUTTON: type="button",
     * - for A: href,
     * - for DIV: none of the above.
     *
     * @param string $content html content of the item
     * @param array $attr list-group-item attributes
     * @return string
     */
    public static function listGroupItem(string $content, array $attr = []): string
    {
        Params::add($attr, 'list-group-item');
        $tag = isset($attr['href'])
            ? 'a'
            : (
            (isset($attr['type']) and $attr['type'] == 'button')
                ? 'button'
                : 'div'
            );

        return "<$tag" . Html::attr($attr) . ">$content</$tag>";
    }

    /** return a modal dialog window with specified header, body and buttons
     *
     * @param array $params {P_HEADER:'dialog title',
     *  P_CONTENT:'dialog body',
     *  P_FOOTER:'dialog buttons row',
     *  P_WRAP_FMT:'%s' // wrap the form around the header / content / footer,
     *  P_CLOSE:close button tag attributes,
     *  container div attributes,
     * }
     * @return string
     */
    public static function modal(array $params): string
    {
        self::registerModal();

        $id = Params::extract($params, 'id');
        $close = Params::extract($params, self::P_CLOSE);
        $header = Params::extract($params, self::P_HEADER);
        $body = Params::extract($params, self::P_CONTENT);
        $body_attr = Params::extract($params, self::P_CONTENT_ATTR, []);
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

        if (is_array($close)) {
            Params::add($close, 'modal', 'data-dismiss');
            $close_html = self::close($close);
        } elseif ($close === false) {
            $close_html = null;
        } else {
            $close_html = self::close(['data-dismiss' => 'modal']);
        }
        if (isset($header)) {
            $header = "<div class=\"modal-header\">$close_html<h4 class=\"modal-title\">$header</h4></div>";
        }
        if (isset($body)) {
            $body = "<div" . Html::attr($body_attr) . ">$body</div>";
        }
        if (isset($footer)) {
            $footer = "<div class=\"modal-footer\">$footer</div>";
        }
        if (isset($size)) {
            $size = " modal-$size";
        }

        HtmlPage::add([
            HtmlPage::SCRIPT => [
                __METHOD__ =>
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
                    
                    EOsc,
            ],
            HtmlPage::DOM_READY => [
                __METHOD__ . "-$id" =>
                    "$('#$id').on('shown.bs.modal',function(){{$focus_modal_btn}($(this));});",
            ],
        ]);

        return sprintf(
            <<<EOfmt
            <div%s>
              <div class="modal-dialog%s">
                <div class="modal-content">
                  %s
                </div>
              </div>
            </div>
            
            EOfmt,
            Html::attr($params),
            $size,
            sprintf($wrap_fmt, "$header$body$footer")
        );
    }

    /** html-formatted bootstrap tabs
     *
     * @param array $items {{P_TARGET:'pane id',
     *  P_HEADER:'tab label',
     *  P_FOOTER:'tab label postfix',
     *  P_HEADER_ATTR:a tag attributes,
     *  P_CONTENT:'pane content',
     *  P_CONTENT_ATTR:a tag attributes,
     *  P_ACTIVE:bool,
     * },
     * li tag attributes
     * }
     * @param array $params {P_PREFIX: 'prefix text',
     *  P_SUFFIX: 'suffix text',
     *  ul tag attributes,
     * }
     * @return string
     */
    public static function nav(array $items, array $params = []): string
    {
        self::registerTab();

        Params::add($params, 'nav');
        if (strpos($params['class'], 'nav-pills') === false) {
            Params::add($params, 'nav-tabs');
            $toggle = 'tab';
        } else {
            $toggle = 'pill';
        }

        $labels = [];
        $panes = [];
        foreach ($items as $k => $item) {
            if (is_array($item)) {
                $header = Params::extract($item, self::P_HEADER);
                $header_attr = Params::extract($item, self::P_HEADER_ATTR, []);
                $footer = Params::extract($item, self::P_FOOTER);
                $content = Params::extract($item, self::P_CONTENT);
                if (isset($content)) {
                    $id = Params::extract($item, self::P_TARGET);
                    $content_attr = Params::extract($item, self::P_CONTENT_ATTR, []);
                    Params::add($content_attr, $id, 'id');
                    Params::add($content_attr, 'tab-pane');
                    if (Params::extract($item, self::P_ACTIVE)) {
                        Params::add($item, 'active');
                        Params::add($content_attr, 'active');
                    }
                    Params::add($header_attr, "#$id", 'href');
                    Params::add($header_attr, "$toggle", 'data-toggle');
                    $labels[] = '<li' . Html::attr($item) . "><a" . Html::attr($header_attr) .
                        ">$header</a>$footer</li>";
                    $panes[] = '<div' . Html::attr($content_attr) . ">$content</div>";
                    unset($items[$k]);
                } else {
                    Params::add($header_attr, Params::extract($item, self::P_TARGET), 'href');
                    if (Params::extract($item, self::P_ACTIVE)) {
                        Params::add($item, 'active');
                    }
                    $labels[] = '<li' . Html::attr($item) . "><a" . Html::attr($header_attr) .
                        ">$header</a>$footer</li>";
                }
            } else {
                $labels[] = "<li><a href=\"#\">$item</a></li>";
            }
        }
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);

        return
            '<ul' . Html::attr($params) . ">$prefix" . implode('', $labels) . "$suffix</ul>" .
            ($panes
                ? ('<div class="tab-content">' . implode('', $panes) . '</div>')
                : ''
            );
    }

    /** html-formatted pagination based on buttons
     *
     * @param array $params {PGN_ACTIVE:current page number,
     *  PGN_LAST: last page number,
     *  PGN_LIST: array of pages to display,
     *  PGN_LINK_1: sprintf-formatted url with one parameter for page number,
     * }
     * @return ?string buttons representing pages
     */
    public static function paginationUsingLinear(array $params): ?string
    {
        $active_page = Params::extract($params, self::PGN_ACTIVE);
        $last_page = Params::extract($params, self::PGN_LAST);
        $pages = Params::extract($params, self::PGN_LIST);
        $link_1 = Params::extract($params, self::PGN_LINK_1);

        if (empty($pages)) {
            return null;
        }

        $ret = [];
        $tail = count($pages) - 1;

        if ($pages[0] > 1) {
            $ret[] =
                '<div class="btn-group">' .
                '<a class="btn" href="' . sprintf($link_1, 1) . '">1</a>' .
                '</div>';
            $ret[] =
                '<div class="btn-group">' .
                '<a class="btn" href="' . sprintf($link_1, $pages[0] - 1) . '">&larr; ' . ($pages[0] - 1) . '</a>' .
                '</div>';
        }

        $ret[] = '<div class="btn-group">';
        foreach ($pages as $p) {
            $ret[] =
                '<a class="btn' . ($p == $active_page ? ' active' : '') . '" href="' . sprintf($link_1, $p) . '">' .
                $p .
                '</a>';
        }
        $ret[] = '</div>';

        if ($pages[$tail] < $last_page) {
            $ret[] =
                '<div class="btn-group">' .
                '<a class="btn" href="' . sprintf(
                    $link_1,
                    $pages[$tail] + 1
                ) . '">' . ($pages[$tail] + 1) . ' &rarr;</a>' .
                '</div>';
            $ret[] =
                '<div class="btn-group">' .
                '<a class="btn" href="' . sprintf($link_1, $last_page) . '">' . $last_page . '</a>' .
                '</div>';
        }

        return implode('', $ret);
    }

    /** html-formatted bootstrap pagination
     *
     * @param array $params {PGN_ACTIVE:current page number,
     *  PGN_LIST: array of pages to display,
     *  PGN_LINK_1: sprintf-formatted url with one parameter for page number,
     * }
     * @return ?string bootstrap pagination using unordered list
     */
    public static function paginationUsingLog(array $params): ?string
    {
        $active_page = Params::extract($params, self::PGN_ACTIVE);
        $pages = Params::extract($params, self::PGN_LIST);
        $link_1 = Params::extract($params, self::PGN_LINK_1);

        if (empty($pages)) {
            return null;
        }

        $s = ['<ul>'];
        if ($active_page > 1) {
            $s[] = '<li><a href="' . sprintf($link_1, $active_page - 1) . '">&larr;</a></li>';
        }
        foreach ($pages as $n) {
            if ($n == $active_page) {
                $s[] = '<li class="active"><span>' . $n . '</span></li>';
            } else {
                $s[] = '<li><a href="' . sprintf($link_1, $n) . '">' . $n . '</a></li>';
            }
        }
        if ($active_page < $n) {
            $s[] = '<li><a href="' . sprintf($link_1, $active_page + 1) . '">&rarr;</a></li>';
        }
        $s[] = '</ul>';

        return implode('', $s);
    }

    /** panel html code
     *
     * @param array $params {P_HEADER:'panel heading',
     *  P_HEADER_ATTR:panel heading div attributes,
     *  P_FOOTER:'panel footer',
     *  P_FOOTER_ATTR:panel footer div attributes,
     *  P_PREFIX:'panel content prefix',
     *  P_SUFFIX:'panel content suffix',
     *  P_CONTENT:'panel content',
     *  P_CONTENT_ATTR:panel content div attributes,
     *  P_WRAP_FMT:'%s-style format for content',
     *  panel div tag attributes,
     * }
     * @return string
     */
    public static function panel(array $params): string
    {
        if ($heading = Params::extract($params, self::P_HEADER)) {
            $title_attr = Params::extract($params, self::P_HEADER_ATTR, []);
            Params::add($title_attr, 'panel-heading');
            $heading = sprintf('<div%s>%s</div>', Html::attr($title_attr), $heading);
        }
        if ($footer = Params::extract($params, self::P_FOOTER)) {
            $footer_attr = Params::extract($params, self::P_FOOTER_ATTR, []);
            if (empty($footer_attr['class']) or strpos($footer_attr['class'], 'text-left') === false) {
                Params::add($footer_attr, 'text-right');
            }
            Params::add($footer_attr, 'panel-footer');
            Params::add($footer_attr, 'clearfix');
            $footer = sprintf('<div%s>%s</div>', Html::attr($footer_attr), $footer);
        }
        $fmt = Params::extract($params, self::P_WRAP_FMT, '%s');
        $content_attr = Params::extract($params, self::P_CONTENT_ATTR, []);
        Params::add($content_attr, 'panel-body');
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);
        if ($content = Params::extract($params, self::P_CONTENT)) {
            $content = "<div" . Html::attr($content_attr) . ">$content</div>";
        }
        Params::add($params, 'panel');
        Params::add($params, 'panel-default');

        return
            '<div' . Html::attr($params) . '>' .
            $heading .
            sprintf($fmt, $prefix . $content . $suffix . $footer) .
            '</div>';
    }

    /** html-formatted pagination based on buttons
     *
     * @param array $params {P_CONTENT: content visible inside the bar,
     *  P_WIDTH: width of the bar,
     *  P_HEADER_ATTR: div tag arguments of the progress outer container,
     *  div tag arguments of the bar container,
     * }
     * @return string buttons representing pages
     */
    public static function progress(array $params): string
    {
        $content = Params::extract($params, self::P_CONTENT);
        $width = Params::extract($params, self::P_WIDTH);
        Params::add($params, 'progress-bar');
        Params::add($params, 'progressbar', 'role');
        $attr = Html::attr(self::width2Attr($width, $params));
        $header_attr = Params::extract($params, self::P_HEADER_ATTR, []);
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

    /** register element popover on page and
     *
     * @param array $params {P_HEADER:'popover heading',
     *  P_CONTENT:'popover content',
     *  P_ALIGN:'popover placement',
     *  P_TARGET:'opener element id',
     *  P_CLOSE:whether to display close btn,
     * }
     * @return array
     */
    public static function registerPopoverOnElement(array $params)
    {
        self::registerTooltip();

        if ($close = Params::extract($params, self::P_CLOSE)) {
            if (is_array($close)) {
                Params::add($close, 'popover', 'data-dismiss');
            } else {
                $close = ['data-dismiss' => 'popover'];
            }
            $close = self::close($close);
        }

        $title = Params::extract($params, self::P_HEADER);
        $id = Params::extract($params, self::P_TARGET);

        $options = [
            'title' => $title,
            'content' => Params::extract($params, self::P_CONTENT),
            'placement' => Params::extract($params, self::P_ALIGN, 'bottom'),
            'html' => true,
            'container' => 'body',
        ];

        HtmlPage::add([
            HtmlPage::DOM_READY => [__METHOD__ . "-$id" => "$('#$id').popover(" . json_encode($options) . ');'],
        ]);

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

    /** return a div formatted as a well block
     *
     * @param array $params {P_CONTENT:'well block body',
     *  div tag arguments,
     * }
     * @return string
     */
    public static function well(array $params): string
    {
        $body = Params::extract($params, self::P_CONTENT);
        Params::add($params, 'well');

        return '<div' . Html::attr($params) . ">$body</div>";
    }

    /** inject width specification into attributes array
     *
     * @param array|int|string $width width specification (nbr of grid units or css value)
     *  | {'sm':WIDTH_1, 'lg':WIDTH_1_2}
     *  | '100%'
     * @param array $attrs attributes array
     * @return array
     */
    public static function width2Attr($width, array $attrs = []): ?array
    {
        if (is_int($width)) {
            Params::add($attrs, "col-sm-$width");
        } elseif (is_array($width)) {
            foreach ($width as $mode => $w) {
                Params::add($attrs, "col-$mode-$w");
            }
        } elseif (is_string($width)) {
            Params::add($attrs, "width:$width;", 'style', '');
        } else {
            return null;
        }

        return $attrs;
    }
}
