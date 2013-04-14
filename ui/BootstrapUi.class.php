<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace dotwheel\ui;

require_once (__DIR__.'/../http/Request.class.php');
require_once (__DIR__.'/../ui/Html.class.php');
require_once (__DIR__.'/../ui/HtmlPage.class.php');
require_once (__DIR__.'/../ui/Ui.class.php');
require_once (__DIR__.'/../util/Misc.class.php');
require_once (__DIR__.'/../util/Nls.class.php');

use dotwheel\http\Request;
use dotwheel\ui\Html;
use dotwheel\ui\HtmlPage;
use dotwheel\ui\Ui;
use dotwheel\util\Misc;
use dotwheel\util\Nls;

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
    const P_LABEL           = 3;
    const P_LABEL_ATTR      = 4;
    const P_FOOTER          = 5;
    const P_FORM_TYPE       = 6;
    const P_FORM_REQ_MODAL  = 7;
    const P_TARGET          = 8;
    const P_ACTIVE          = 9;
    const P_CLOSE           = 10;

    const FORM_TYPE_HORIZONTAL  = 1;

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
    const WIDTH_MINI    = -1;
    const WIDTH_SMALL   = -2;
    const WIDTH_MEDIUM  = -3;
    const WIDTH_LARGE   = -4;
    const WIDTH_XLARGE  = -5;
    const WIDTH_XXLARGE = -6;

    public static $Input_sets_params = array(Html::P_LABEL_ATTR=>array('class'=>'checkbox'));
    public static $Input_enums_params = array(Html::P_LABEL_ATTR=>array('class'=>'radio'));



    /** returns a div formatted as alert block
     * @param array $params {P_LABEL:'alert body', P_LABEL_ATTR:{label tag attributes}
     *                      , P_CONTENT:'alert body'
     *                      , div tag arguments
     *                      }
     * @return string
     */
    public static function alert($params)
    {
        $body = Misc::paramExtract($params, self::P_CONTENT);
        if ($label = Misc::paramExtract($params, self::P_LABEL))
        {
            $label_attr = Misc::paramExtract($params, self::P_LABEL_ATTR, array());
            $body = '<h4'.Html::attr($label_attr).'>'.$label.'</h4>'.$body;
        }
        if ($close = Misc::paramExtract($params, self::P_CLOSE))
            $body = self::close(is_array($close) ? $close : array()) . $body;
        Misc::paramAdd($params, 'alert');

        return '<div'.Html::attr($params).">$body</div>";
    }

    /** format as comment line
     * @param string $comment   comment to format
     * @return string
     */
    public static function asComment($comment)
    {
        return isset($comment) ? "<p class=\"help-block\">$comment</p>" : null;
    }

    /** format as label
     * @param string $label label to format
     * @return string
     */
    public static function asLabel($label)
    {
        return isset($label) ? "<label>$label</label>" : null;
    }

    /** get button element
     * @param array $params {P_LABEL:button value, button tag attributes}
     * @return string
     */
    public static function button($params)
    {
        $label = Misc::paramExtract($params, self::P_LABEL);
        $params += array('type'=>'button');
        Misc::paramAdd($params, 'btn');

        return ' <button'.Html::attr($params).">$label</button>";
    }

    public static function close($params=array())
    {
        self::registerAlert();

        Misc::paramAdd($params, 'close');
        Misc::paramAdd($params, 'alert', 'data-dismiss');

        return '<a'.Html::attr($params).">&times;</a>";
    }

    /** returns collapsed container(hidden by default)
     * @param array $params {P_CONTENT:'content body'
     *                      , container tag attributes
     *                      }
     */
    public static function collapseContainer($params)
    {
        self::registerCollapse();

        $content = Misc::paramExtract($params, self::P_CONTENT);
        Misc::paramAdd($params, 'collapse');

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

        $id = Misc::paramExtract($params, 'id', 'clps_btn_'.$cnt++);
        $id_target = Misc::paramExtract($params, Ui::P_TARGET);
        $prefix = ($label_attr = Misc::paramExtract($params, Ui::P_LABEL_ATTR, array()))
            ? ('<i'.Html::attr($label_attr).'></i> ')
            : ''
            ;
        $prefix .= ($label = Misc::paramExtract($params, Ui::P_LABEL))
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

        Misc::paramAdd($params, $id, 'id');
        Misc::paramAdd($params, 'collapse', 'data-toggle');
        Misc::paramAdd($params, "#$id_target", 'data-target');
        Misc::paramAdd($params, 'dropdown');

        return self::button(array(self::P_LABEL=>"$prefix<span class=\"caret\"></span>") + $params);
    }

    /** returns sidebar with a label and a list of menu positions
     * @staticvar int $cnt
     * @param array $params {P_LABEL:''
     *                      , {P_LABEL:..., P_LABEL_ATTR:..., P_ACTIVE:...
     *                          , 1st a tag attributes
     *                          }
     *                      , {2nd...}
     *                      , container div attributes
     *                      }
     * @return string
     */
    public static function sideBar($params)
    {
        $container_label = Misc::paramExtract($params, self::P_LABEL);
        $container_attr = array();
        $controls = array();
        foreach ($params as $k=>$control)
        {
            if (is_array($control))
            {
                $label = Misc::paramExtract($control, self::P_LABEL);
                $label_attr = Misc::paramExtract($control, self::P_LABEL_ATTR, array());
                if (Misc::paramExtract($control, self::P_ACTIVE))
                    Misc::paramAdd($label_attr, 'active');
                if (isset($label))
                    $label = '<a'.Html::attr($control).">$label</a>";
                $controls[] = '<li'.Html::attr($label_attr).">$label</li>";
            }
            else
                $container_attr[$k] = $control;
        }
        Misc::paramAdd($container_attr, 'nav nav-list');

        return '<ul'.Html::attr($container_attr).'>'
            . (isset($container_label) ? "<li class=\"nav-header\">$container_label</li>" : '')
            . implode('', $controls)
            . '</ul>'
            ;
    }

    /** returns cycling blocks controlled with buttons
     * @staticvar int $cnt
     * @param array $params {{P_LABEL:..., P_CONTENT:..., 1st button tag attributes}
     *                      , {2nd...}
     *                      , container div attributes
     *                      }
     * @return string
     */
    public static function cycleButtons($params)
    {
        static $cnt = 0;

        self::registerButton();

        $container_args = array();
        $controls = array();
        $panes = array();
        $id = null;
        foreach ($params as $k=>$control)
        {
            if (is_array($control))
            {
                $id = Misc::paramExtract($control, 'id', 'ccl_btn_'.$cnt++);
                $label = Misc::paramExtract($control, self::P_LABEL);
                $content = Misc::paramExtract($control, self::P_CONTENT);
                Misc::paramAdd($control, $id, 'id');
                $pane = array();
                if (!$k)
                    Misc::paramAdd($control, 'active');
                else
                    Misc::paramAdd($pane, 'hide');
                $controls[] = self::button($control + array(self::P_LABEL=>$label));
                $panes[] = '<div'.Html::attr($pane).'>'.$content.'</div>';
                HtmlPage::add(array(HtmlPage::DOM_READY=>array(
                    __METHOD__."-$id"=>"$('#$id').click(function(){if (!$(this).is('.active'))cycle_from_to($(this).parent().next(),$k);});"
                    )));
            }
            else
                $container_args[$k] = $control;
        }

        HtmlPage::add(array(HtmlPage::SCRIPT=>array(__METHOD__.'-cycle_to'=><<<EOct
function cycle_from_to(el,k){
    $(el).children(':visible').slideUp().end().children().eq(k).slideDown();
}
EOct
            )));

        return '<div'.Html::attr($container_args).'>'
            . '<div class="btn-group" data-toggle="buttons-radio">'
            . implode('', $controls)
            . '</div>'
            . '<div class="_cycle">'
            . implode('', $panes)
            . '</div>'
            . '</div>'
            ;
    }

    /** returns field representation with label and comment
     * @param string $name  field name
     * @param mixed $value  field value
     * @param array $repo   {field repository attributes}
     * @return string
     */
    public static function fieldLabelView($name, $value, $repo=array())
    {
        $content = Repo::asHtml($name, $value, $repo);
        $label = Repo::getLabel($name, null, $repo);
        if (isset($label))
            $label = "$label<br>";
        $comment = Repo::getParam($name, Repo::P_COMMENT, $repo);
        if (isset($comment))
            $comment = "<p class=\"help-block\">$comment</p>";

        return "$label$content$comment";
    }

    /** returns container with fields in horizontal orientation(labels on the left)
     * @param array $params {P_LABEL:'label', P_LABEL_ATTR:{label tag attributes}
     *                      , P_CONTENT:'container content'
     *                      , container div tag attributes
     *                      }
     * @return string
     */
    public static function fieldsHorizontal($params)
    {
        $content = Misc::paramExtract($params, self::P_CONTENT);
        $label = Misc::paramExtract($params, self::P_LABEL);
        $label_attr = Misc::paramExtract($params, self::P_LABEL_ATTR, array());
        Misc::paramAdd($label_attr, 'control-label');
        Misc::paramAdd($params, 'control-group');

        return '<div'.Html::attr($params).'><label'.Html::attr($label_attr).">$label</label><div class=\"controls\">$content</div></div>";
    }

    /** returns a div with buttons
     * @param array $actions    {btn_name:{button tag attributes},...}
     * @return string
     */
    public static function formActions($actions)
    {
        $buttons = array();
        foreach ($actions as $k=>$attr)
        {
            if (is_array($attr))
            {
                $value = Misc::paramExtract($attr, 'value', $k);
                Misc::paramAdd($attr, 'btn');
                $buttons[] = '<button'.Html::attr($attr).">$value</button>";
            }
            else
                $buttons[] = $attr;
        }

        return '<div class="form-actions">'.implode(' ', $buttons).'</div>';
    }

    /**
     * @param array $params {P_FORM_TYPE:FORM_TYPE_HORIZONTAL
     *                      , P_FORM_REQ_MODAL:'modal div id'
     *                      , form tag attributes
     *                      }
     * @return array        form tag attributes
     */
    public static function formAttr($params)
    {
        if (Misc::paramExtract($params, self::P_FORM_TYPE) == self::FORM_TYPE_HORIZONTAL)
            Misc::paramAdd($params, 'form-horizontal');
        if ($chk_req = Misc::paramExtract($params, self::P_FORM_REQ_MODAL))
            Misc::paramAdd($params
                , "if (chk_required(this,'".Ui::REQUIRED_CLASS."','$chk_req'))aj_form_submit($(this),'$chk_req'); return false;"
                , 'onsubmit'
                , ';'
                );

        return $params;
    }

    /** returns a container of a fixed grid with rows and cells
     * @param array $rows   {{{P_WIDTH:..., P_CONTENT:'cell_content'}
     *                          , 'cell_content', 'row_attr':'value'
     *                          }
     *                      , 'row_content'
     *                      , container div tag attributes
     *                      }
     * @return string
     */
    public static function gridContainerFixed($rows)
    {
        $new_rows = array();
        foreach ($rows as $rkey=>$row)
        {
            if (is_array($row))
            {
                Misc::paramAdd($row, 'row');
                $new_rows[] = static::gridRow($row);
                unset($rows[$rkey]);
            }
            elseif (is_int($rkey))
            {
                $new_rows[] = static::gridRow(array($row, 'class'=>'row'));
                unset($rows[$rkey]);
            }
        }
        Misc::paramAdd($rows, 'container');

        return '<div'.Html::attr($rows).'>'
            . implode('', $new_rows)
            . '</div>'
            ;
    }

    public static function gridContainerFluid($rows)
    {
        $new_rows = array();
        foreach ($rows as $rkey=>$row)
        {
            if (is_array($row))
            {
                Misc::paramAdd($row, 'row-fluid');
                $new_rows[] = static::gridRow($row);
                unset($rows[$rkey]);
            }
            elseif (is_int($rkey))
            {
                $new_rows[] = static::gridRow(array($row, 'class'=>'row-fluid'));
                unset($rows[$rkey]);
            }
        }
        Misc::paramAdd($rows, 'container-fluid');

        return '<div'.Html::attr($rows).'>'
            . implode('', $new_rows)
            . '</div>'
            ;
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
                if ($width = Misc::paramExtract($col, self::P_WIDTH, $width_default))
                    $col = static::width2Attr($width, $col);
                $content = Misc::paramExtract($col, self::P_CONTENT);
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

        return '<div'.Html::attr($columns).'>'.implode('', $cols).'</div>';
    }

    /** returns a modal dialog window with specified header, body and buttons
     * @param array $params {P_LABEL:'dialog title'
     *                      , P_CONTENT:'dialog body'
     *                      , P_FOOTER:'dialog buttons row'
     *                      , container div attributes
     *                      }
     * @return string
     */
    public static function modal($params)
    {
        self::registerModal();

        $header = Misc::paramExtract($params, self::P_LABEL);
        $body = Misc::paramExtract($params, self::P_CONTENT);
        $footer = Misc::paramExtract($params, self::P_FOOTER);

        Misc::paramAdd($params, 'modal');
        Misc::paramAdd($params, 'hide');

        if (isset($header))
            $header = "<div class=\"modal-header\"><a class=\"close\" data-dismiss=\"modal\">&times;</a><h3>$header</h3></div>";
        if (isset($body))
            $body = "<div class=\"modal-body\">$body</div>";
        if (isset($footer))
            $footer = "<div class=\"modal-footer\">$footer</div>";

        return '<div'.Html::attr($params).'>'
            . $header
            . $body
            . $footer
            . '</div>'
            ;
    }

    /** returns attributes line for element
     * @param array $params {P_TARGET:'modal div id', tag attributes}
     */
    public static function modalMain($params)
    {
        $id = Misc::paramExtract($params, 'id', 'modal_main');
        HtmlPage::add(array(HtmlPage::HTML_FOOTER=>array(__METHOD__."-$id"=>Ui::modal($params + array(Ui::P_LABEL=>''
            , Ui::P_CONTENT=>''
            , Ui::P_FOOTER=>Ui::button(array(self::P_LABEL=>Html::encode(dgettext(Nls::FW_DOMAIN, 'Close'))
                , 'class'=>'btn btn-primary', 'data-dismiss'=>'modal'
                ))
            , 'id'=>$id
            )))));

        return $id;
    }

    /** html-formatted pagination based on butons
     * @param array $params {PG_ACTIVE:current page number
     * , PG_LAST: last page number
     * , PG_LIST: array of pages to display
     * , PG_LINK_1: sprintf-formatted url with one parameter for page number
     * }
     * @return string buttons representing pages
     */
    public static function paginationUsingLinear($params)
    {
        $active_page = Misc::paramExtract($params, self::PGN_ACTIVE);
        $last_page = Misc::paramExtract($params, self::PGN_LAST);
        $pages = Misc::paramExtract($params, self::PGN_LIST);
        $link_1 = Misc::paramExtract($params, self::PGN_LINK_1);

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
     * , PG_LIST: array of pages to display
     * , PG_LINK_1: sprintf-formatted url with one parameter for page number
     * }
     * @return string bootstrap pagination using unordered list
     */
    public static function paginationUsingLog($params)
    {
        $active_page = Misc::paramExtract($params, self::PGN_ACTIVE);
        $pages = Misc::paramExtract($params, self::PGN_LIST);
        $link_1 = Misc::paramExtract($params, self::PGN_LINK_1);

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

    /** register alerts js */
    public static function registerAlert()
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

    /**
     *
     * @param type $params
     * @return type
     */
    public static function tabs($params)
    {
        self::registerTab();

        $tabs = array();
        $panes = array();
        foreach ($params as $tab)
        {
            $id = Misc::paramExtract($tab, self::P_TARGET);
            $label = Misc::paramExtract($tab, self::P_LABEL);
            $pane = array('id'=>$id, 'class'=>'tab-pane');
            $content = Misc::paramExtract($tab, self::P_CONTENT);
            if (Misc::paramExtract($tab, self::P_ACTIVE))
            {
                Misc::paramAdd($tab, 'active');
                Misc::paramAdd($pane, 'active');
            }
            $tabs[] = '<li'.Html::attr($tab)."><a href=\"#$id\" data-toggle=\"tab\">$label</a></li>\n";
            $panes[] = '<div'.Html::attr($pane).">$content</div>\n";
        }

        return '<div class="tabbable">'
            . '<ul class="nav nav-tabs">'.implode('', $tabs)."</ul>\n"
            . '<div class="tab-content">'.implode('', $panes)."</div>\n"
            . "</div>\n"
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
        $body = Misc::paramExtract($params, self::P_CONTENT);
        Misc::paramAdd($params, 'well');

        return '<div'.Html::attr($params).">$body</div>";
    }

    /** inject width specification into attributes array
     * @param int|string $width width specification(nbr of grid units or css value)
     * @param array $attrs      attributes array
     * @return void
     */
    public static function width2Attr($width, $attrs=array())
    {
        if ($width == self::WIDTH_MINI)
            Misc::paramAdd($attrs, 'input-mini');
        elseif ($width == self::WIDTH_SMALL)
            Misc::paramAdd($attrs, 'input-small');
        elseif ($width == self::WIDTH_MEDIUM)
            Misc::paramAdd($attrs, 'input-medium');
        elseif ($width == self::WIDTH_LARGE)
            Misc::paramAdd($attrs, 'input-large');
        elseif ($width == self::WIDTH_XLARGE)
            Misc::paramAdd($attrs, 'input-xlarge');
        elseif ($width == self::WIDTH_XXLARGE)
            Misc::paramAdd($attrs, 'input-xxlarge');
        elseif (is_int($width))
            Misc::paramAdd($attrs, "span{$width}");
        else
            Misc::paramAdd($attrs, "width:{$width};", 'style', '');

        return $attrs;
    }
}
