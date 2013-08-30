<?php

/**
handles html form display, list of required fields etc.

[type: library]

@author stas trefilov
*/

namespace dotwheel\ui;

require_once (__DIR__.'/HtmlPage.class.php');
require_once (__DIR__.'/Ui.class.php');
require_once (__DIR__.'/../db/Repo.class.php');
require_once (__DIR__.'/../http/Request.class.php');
require_once (__DIR__.'/../util/Nls.class.php');
require_once (__DIR__.'/../util/Params.class.php');

use dotwheel\db\Repo;
use dotwheel\http\Request;
use dotwheel\util\Nls;
use dotwheel\util\Params;

class HtmlTable
{
    const P_ROWS        = 1;
    const P_TD          = 2;
    const P_TR          = 3;
    const P_FIELDS      = 4;
    const P_SORT        = 5;
    const P_PREFIX      = 6;
    const P_SUFFIX      = 7;
    const P_GROUP_CLASS = 8;
    const P_TOTAL_CLASS = 9;

    const R_VALUES  = -1;
    const R_TD      = -2;
    const R_TR      = -3;

    const F_WIDTH           = 1;
    const F_ALIGN           = 2;
    const F_REPO            = 3;
    const F_HEADER          = 4;
    const F_HEADER_LABEL    = 41;
    const F_HEADER_ABBR     = 42;
    const F_CHECKBOX        = 5;
    const F_SORT            = 6;
    const F_SORT_EXCLUDE    = 61;
    const F_SORT_GROUP      = 62;
    const F_FMT             = 7;
    const F_URL             = 8;
    const F_URL_FIELD       = 81;
    const F_URL_FMT         = 82;
    const F_URL_TARGET      = 83;
    const F_TOTAL           = 9;
    const F_TOTAL_SUM       = 91;
    const F_TOTAL_COUNT     = 92;
    const F_TOTAL_AVG       = 93;
    const F_TOTAL_TEXT      = 94;
    const F_HIDDEN          = 100;
    const F_ASIS            = 101;

    const S_FIELD   = 1;
    const S_ICON    = 2;
    const S_REVERSE = 3;
    const S_SCRIPT  = 4;
    const S_PARAMS  = 5;
    const S_TARGET  = 6;

    const L_LPT = 1;
    const L_XL  = 2;
    const L_CSV = 3;

    const PAGES_PER_BLOCK   = 10;
    const ITEMS_PER_PAGE    = 100;

    /** @var int    autoincrement for automatic table id if id parameter omitted */
    static protected $counter = 0;

    /** @var array  if totals row displayed then the values are stored here */
    public static $totals = array();



    /** returns the html code of a table
     * @param array $params list of table parameters:
     *  {id:'tbl1'
     *  , P_GROUP_CLASS:'_grp'
     *  , P_TOTAL_CLASS:'_ttl'
     *  , P_FIELDS:{fld1:{F_WIDTH:'20%'
     *          , F_ALIGN:'center'
     *          , F_REPO:{field repository arguments}
     *          , F_HEADER:{F_HEADER_LABEL:Repo::P_LABEL_SHORT|null, F_HEADER_ABBR:Repo::P_LABEL_LONG|true|null, th tag arguments}
     *          , F_CHECKBOX:true   // replaces header with a checkbox and a toggler js code
     *          , F_HIDDEN:true
     *          , F_ASIS:true
     *          , F_SORT:{F_SORT_EXCLUDE:true, F_SORT_GROUP:'fld2'|true}
     *          , F_FMT:'<span class="tag">%s</span>'
     *          , F_URL:{F_URL_FIELD:'fld2',F_URL_ADDRESS:'/path/script.php?id=%u&mode=edit',F_URL_TARGET:'_blank'}
     *          , F_TOTAL:(TOTAL_SUM|true)|TOTAL_COUNT|TOTAL_AVG|'text'
     *          }
     *      , fld2:{}
     *      }
     *  , P_SORT:{S_FIELD:'fld1'
     *      , S_ICON:'icon-caret-down'
     *      , S_REVERSE:true
     *      , S_SCRIPT:'/this/script.php'
     *      , S_PARAMS:{s:{tbl1:'fld_current'},f:{tbl1:{f1:'on'}},...} // sort param for current table will be replaced by %s, page param for current table will be unset
     *      , S_TARGET:'_blank'
     *      }
     *  , P_ROWS:{r1:{fld1:'value',fld2:'value',fld3:'value'}
     *      , r2:{fld1:'value',fld2:'value',fld3:'value'}
     *      }
     *  , P_TD:{r1:{fld3:' td tag attributes'}, ...}
     *  , P_TR:{r2:' tr tag attributes'}, ...}
     *  , P_PREFIX:''
     *  , P_SUFFIX:''
     *  , table tag arguments
     *  }
     * @todo implement layout parameter
     * @return string|null
     */
    public static function get($params)
    {
        if (empty($params[self::P_FIELDS]))
            return null;

        // initialize parameters
        //

        $table_id = Params::extract($params, 'id', 'tid_'.++self::$counter);
        Params::add($params, $table_id, 'id');

        if ($sort = Params::extract($params, self::P_SORT))
        {
            $sort_params = Params::extract($sort, self::S_PARAMS, array());
            unset($sort_params[Request::CGI_SORT][$table_id], $sort_params[Request::CGI_PAGE][$table_id]);
        }
        else
            $sort_params = null;

        $colgroup = array();
        $repo = array();
        $headers = array();
        $headers_td = array();
        $sort_group_key = null;
        $sort_group_old = null;
        $aligns = array();
        $formats = array();
        $checkboxes = array();
        $urls_field = array();
        $urls_fmt = array();
        $urls_target = array();
        $totals = false;
        $totals_fn = array();
        $totals_cnt = array();
        $hidden = array();
        $asis = array();

        $group_class = Params::extract($params, self::P_GROUP_CLASS, '_grp');
        $total_class = Params::extract($params, self::P_TOTAL_CLASS, '_ttl');

        foreach (Params::extract($params, self::P_FIELDS, array()) as $field=>$f)
        {
            if (! is_array($f))
                $f = array(self::F_WIDTH=>$f, self::F_ALIGN=>null, self::F_SORT=>array(self::F_SORT_EXCLUDE=>true));

            $colgroup[$field] = ($w = Params::extract($f, self::F_WIDTH))
                ? array('width'=>$w)
                : array()
                ;
            $repo[$field] = Repo::get($field, isset($f[self::F_REPO]) ? $f[self::F_REPO] : array());

            if (isset($f[self::F_HEADER]))
            {
                $header = ($h = Params::extract($f[self::F_HEADER], self::F_HEADER_LABEL))
                    ? Repo::getLabel($field, $repo[$field], $h)
                    : Repo::getLabel($field, $repo[$field], Repo::P_LABEL)
                    ;
                $headers[$field] = ($abbr = Params::extract($f[self::F_HEADER], self::F_HEADER_ABBR))
                    ? Html::asAbbr($header, Repo::getLabel($field, $repo[$field], $abbr === true ? Repo::P_LABEL_LONG : $abbr))
                    : Html::encode($header)
                    ;
                if ($f[self::F_HEADER])
                    $headers_td[$field] = $f[self::F_HEADER];
            }
            else
                $headers[$field] = Html::encode(Repo::getLabel($field, $repo[$field]));

            if (isset($f[self::F_CHECKBOX]))
            {
                $checkboxes[$field] = $f[self::F_CHECKBOX];
                $headers[$field] = Html::inputCheckbox(array('id'=>"{$table_id}_chk"));
                HtmlPage::add(array(HtmlPage::DOM_READY=>array("{$table_id}_chk"
                    =>"$('#{$table_id}_chk').change(function(){\$('input:checkbox[name^=\"$field\"]','#$table_id').prop('checked',this.checked);});"
                    )));
                if (! isset($f[self::F_ALIGN]))
                    $f[self::F_ALIGN] = 'center';
            }

            if (isset($sort)
                and isset($sort[self::S_FIELD])
                and empty($f[self::F_SORT][self::F_SORT_EXCLUDE])
                and isset($f[self::F_SORT][self::F_SORT_GROUP])
                and $sort[self::S_FIELD] == $field
                )
                $sort_group_key = $f[self::F_SORT][self::F_SORT_GROUP] === true ? $field : $f[self::F_SORT][self::F_SORT_GROUP];

            if (isset($f[self::F_ALIGN]))
                $colgroup[$field]['align'] = $f[self::F_ALIGN];

            if (isset($f[self::F_FMT]))
                $formats[$field] = $f[self::F_FMT];

            if (isset($f[self::F_URL])
                and isset($f[self::F_URL][self::F_URL_FIELD])
                and isset($f[self::F_URL][self::F_URL_FMT])
                )
            {
                $urls_field[$field] = $f[self::F_URL][self::F_URL_FIELD];
                $urls_fmt[$field] = $f[self::F_URL][self::F_URL_FMT];
                if (isset($f[self::F_URL][self::F_URL_TARGET]))
                    $urls_target[$field] = $f[self::F_URL][self::F_URL_TARGET];
            }

            if (isset($f[self::F_TOTAL]))
            {
                $totals = true;
                if ($f[self::F_TOTAL] === true)
                    $totals_fn[$field] = self::F_TOTAL_SUM;
                elseif (is_string($f[self::F_TOTAL]))
                    $totals_fn[$field] = self::F_TOTAL_TEXT;
                else
                    $totals_fn[$field] = $f[self::F_TOTAL];
                self::$totals[$field] = $totals_fn[$field] === self::F_TOTAL_TEXT ? Html::encode($f[self::F_TOTAL]) : 0;
                $totals_cnt[$field] = $totals_fn[$field] === self::F_TOTAL_TEXT ? null : 0;
            }

            if (isset($sort)
                and empty($f[self::F_SORT][self::F_SORT_EXCLUDE])
                )
            {
                if (isset($sort[self::S_FIELD])
                    and $sort[self::S_FIELD] == $field
                    and isset($sort[self::S_ICON])
                    )
                {
                    $headers[$field] .= ' '.Ui::icon($sort[self::S_ICON]);
                    $suf = empty($sort[self::S_REVERSE]) ? Request::SORT_REV_SUFFIX : '';
                }
                else
                    $suf = '';
                $headers[$field] = sprintf('<a href="%s"%s>%s</a>'
                    , (isset($sort[self::S_SCRIPT]) ? $sort[self::S_SCRIPT] : '')
                        . Html::urlArgs('?', array_merge_recursive($sort_params, array(Request::CGI_SORT=>array($table_id=>$field.$suf))))
                    , isset($sort[self::S_TARGET]) ? " target=\"{$sort[self::S_TARGET]}\"" : ''
                    , $headers[$field]
                    );
            }

            if (isset($f[self::F_HIDDEN]))
            {
                $hidden[$field] = true;
                unset($headers[$field], $headers_td[$field]);
            }

            if (isset($f[self::F_ASIS]))
                $asis[$field] = true;
        }

        $rows_values = Params::extract($params, self::P_ROWS);
        $rows_td = Params::extract($params, self::P_TD, array());
        $rows_tr = Params::extract($params, self::P_TR, array());
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);

        ob_start();

        // start table
        //

        echo Html::tableStart($params + array(Html::P_COLGROUP=>$colgroup))
            , Html::thead(array(Html::P_VALUES=>$headers, Html::P_TD_ATTR=>$headers_td, Html::P_PREFIX=>$prefix))
            , '<tbody>'
            ;

        // cycle through the rows
        //

        foreach ($rows_values as $krow=>$row)
        {
            $values = array();
            $td = isset($rows_td[$krow]) ? $rows_td[$krow] : array();
            $tr = isset($rows_tr[$krow]) ? $rows_tr[$krow] : array();

            // grouping
            if (isset($sort_group_key) and $sort_group_old != $row[$sort_group_key])
            {
                Params::add($tr, $group_class);
                $sort_group_old = $row[$sort_group_key];
            }

            // cycle through the fields
            //

            foreach ($repo as $field=>$r)
            {
                // set cell value

                // whether checkbox...
                if (isset($checkboxes[$field]))
                    $values[$field] = Html::inputCheckbox(array('name'=>"{$field}[{$row[$field]}]", 'value'=>$row[$field]));
                // ...as is column
                elseif (isset($asis[$field]))
                    $values[$field] = $row[$field];
                // ...or normal value (if not hidden)
                elseif (empty($hidden[$field]))
                {
                    $v = Repo::asHtmlStatic($field, $row[$field], $r);
                    if (isset($urls_field[$field]))
                        $v = sprintf('<a href="%s"%s>%s</a>'
                            , sprintf($urls_fmt[$field], $row[$urls_field[$field]])
                            , isset($urls_target[$field]) ? " target=\"{$urls_target[$field]}\"" : ''
                            , $v
                            );
                    $values[$field] = isset($formats[$field]) ? sprintf($formats[$field], $v) : $v;
                    if ($totals and isset($row[$field]) and isset($totals_fn[$field]) and $totals_fn[$field] !== self::F_TOTAL_TEXT)
                    {
                        self::$totals[$field] += $row[$field];
                        ++$totals_cnt[$field];
                    }
                }
            }

            echo Html::tr(array(Html::P_VALUES=>$values, Html::P_TD_ATTR=>$td ? $td : null) + $tr);
        }

        $tfoot = null;

        // add totals row
        //

        if ($totals)
        {
            $t = array();
            foreach ($repo as $field=>$r)
            {
                if (isset(self::$totals[$field]))
                {
                    if ($totals_fn[$field] === self::F_TOTAL_COUNT)
                        self::$totals[$field] = $totals_cnt[$field];
                    elseif ($totals_fn[$field] == self::F_TOTAL_AVG)
                        self::$totals[$field] /= $totals_cnt[$field];
                    $t[$field] = Repo::asHtmlStatic($field
                        , self::$totals[$field]
                        , $totals_fn[$field] === self::F_TOTAL_TEXT
                            ? array(Repo::P_CLASS=>Repo::C_TEXT)
                            : (Repo::isNumericClass($r)
                                ? $r
                                : array(Repo::P_CLASS=>Repo::C_INT)
                                )
                        );
                }
                else
                    $t[$field] = '';
            }
            $tfoot .= Html::tr(array(Html::P_VALUES=>$t, 'class'=>$total_class));
        }

        // add suffix
        //

        if (isset($suffix))
            $tfoot .= Html::tr(array(Html::P_VALUES=>array($suffix), Html::P_TD_ATTR=>array(' colspan="'.count($headers).'"')));

        if ($tfoot)
            echo '<tfoot>', $tfoot;

        echo Html::tableStop();

        return ob_get_clean();
    }

    /** draw the pagination element
     * @param type $action      script to load for the next page
     * @param type $hidden      url params to pass to the next page
     * @param type $total       total number of items
     * @param type $page        current page(0-based)
     * @param type $table_id    table id
     * @param type $items       items per page
     * @return string           an html to visualize the pagination
     */
    public static function pagination($action, $hidden, $total, $page, $table_id, $items=self::ITEMS_PER_PAGE)
    {
        if ($total)
        {
            if ($items and $total > $items)
            {
                $str_link_3 = '<li%s><a href="%s">%s</a></li>';
                $str_act_1 = '<li class="active"><a>%s</a></li>';
                unset($hidden['p'][$table_id]);
                $args0 = isset($hidden) ? Html::urlArgs('?', $hidden) : '';
                $pages = (int)(($total-1) / $items) + 1;
                $page_first = (int)($page / self::PAGES_PER_BLOCK) * self::PAGES_PER_BLOCK;
                $page_next = min($page_first + self::PAGES_PER_BLOCK, $pages);
                $url0 = $action.$args0;
                $url = $url0 . ($args0 ? "&p%5B$table_id%5D=" : "?p%5B$table_id%5D=");
                $parts = array();

                // first/prev page
                if ($page)
                {
                    $parts[] = sprintf($str_link_3, '', $url0, '&laquo;');
                    $parts[] = sprintf($str_link_3, '', $page > 1 ? ($url.($page-1)) : $url0, '&larr; '.dgettext(Nls::FW_DOMAIN, 'Prev'));
                }
                // numbered pages
                for ($i = $page_first; $i < $page_next; ++$i)
                    $parts[] = $i == $page
                        ? sprintf($str_act_1, $i+1)
                        : sprintf($str_link_3, '', $i ? "$url$i" : $url0, $i+1)
                        ;
                // next/last page
                if ($page < $pages-1)
                {
                    $parts[] = sprintf($str_link_3, '', $url.($page+1), dgettext(Nls::FW_DOMAIN, 'Next').' &rarr;');
                    $parts[] = sprintf($str_link_3, '', $url.($pages-1), '&raquo;');
                }

                return ' <div class="'.Ui::PGN_CLASS.'"><ul>'.implode('', $parts)."</ul></div>";
            }
            else
                return ' <span class="'.Ui::PGN_CLASS."\">[$total]</span>";
        }
        else
            return '';
    }
}
