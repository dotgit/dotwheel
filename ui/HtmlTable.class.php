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
require_once (__DIR__.'/../util/Misc.class.php');
require_once (__DIR__.'/../util/Nls.class.php');

use dotwheel\db\Repo;
use dotwheel\util\Misc;
use dotwheel\util\Nls;

class HtmlTable
{
    const P_ROWS    = 1;
    const P_FIELDS  = 2;
    const P_SORT    = 3;
    const P_UNIQUE  = 4;
    const P_LAYOUT  = 5;
    const P_EMPTY   = 6;
    const P_PREFIX  = 7;
    const P_SUFFIX  = 8;

    const R_VALUES  = -1;
    const R_TD      = -2;
    const R_TR      = -3;

    const F_WIDTH           = 1;
    const F_ALIGN           = 2;
    const F_REPOSITORY      = 3;
    const F_HEADER          = 4;
    const F_HEADER_LABEL    = 41;
    const F_HEADER_ABBR     = 42;
    const F_CHECKBOX        = 5;
    const F_CHECKBOX_NAME   = 51;
    const F_SORT            = 6;
    const F_SORT_EXCLUDE    = 63;
    const F_SORT_GROUP      = 64;
    const F_SORT_SCRIPT     = 65;
    const F_SORT_TARGET     = 66;
    const F_FORMAT          = 7;
    const F_URL             = 8;
    const F_URL_FIELD       = 81;
    const F_URL_ADDRESS     = 82;
    const F_URL_TARGET      = 83;
    const F_TOTAL           = 9;
    const F_TOTAL_SUM       = 91;
    const F_TOTAL_COUNT     = 92;
    const F_TOTAL_AVG       = 93;
    const F_TOTAL_TEXT      = 94;

    const S_PARAMS  = 1;
    const S_FIELD   = 2;

    const U_KEY     = 1;
    const U_FIELDS  = 2;

    const L_LPT = 1;
    const L_XL  = 2;
    const L_CSV = 3;

    const SORT_REV_SUFFIX   = '.';
    const PAGES_PER_BLOCK   = 10;
    const ITEMS_PER_PAGE    = 100;

    /** @var int    autoincrement for automatic table id if id parameter omitted */
    static protected $counter = 0;

    /** @var array  if totals row displayed then the values are stored here */
    public static $totals = array();



    /** returns the html code of a table
     * @param array $params list of table parameters:
     * <pre>
     *  {id:'tbl1'
     *  , rows:{values:{r1:{fld1:'value',fld2:'value',fld3:'value'}
     *          , r2:{fld1:'value',fld2:'value',fld3:'value'}
     *          }
     *      , td:{r1:{fld3:' td tag attributes'}}
     *      , tr:{r2:' tr tag attributes'}
     *      }
     *  , fields:{fld1:{width:'20%'
     *          , repository:{field repository arguments}
     *          , header:{label:Repo::PARAM_LABEL_SHORT|null,abbr:Repo::PARAM_LABEL_LONG|true|null, th tag arguments}
     *          , checkbox:{name:'fld1',form:'form_name'}   // replaces header with a checkbox and a toggler js code
     *          , sort:{exclude:true,group:'fld2'|true}
     *          , align:'center'
     *          , format:'<span class="tag">%s</span>'
     *          , url:{field:'fld2',address:'/path/script.php?id=%u&mode=edit',target:'_blank'}
     *          , total:'text'|(TOTAL_SUM|true)|TOTAL_COUNT|TOTAL_AVG
     *          }
     *      , fld2:{}
     *      }
     *  , unique:{key:'fld1',fields:'fld1,fld2,fld3'}
     *  , sort:{field:'fld1'
     *      , script:'/this/script.php'
     *      , params:{s:{tbl1:'fld_current'},f{tbl1:{f1:'on'}},...} // sort param for current table will be replaced by %s, page param for current table will be unset
     *      , target:'_blank'
     *      }
     *  , empty:{display:true,label:'no rows selected'}
     *  , layout:null|'lpt'|'xl'|'csv' // not(yet) implemented
     *  , table tag arguments
     *  }
     * </pre>
     * @todo implement layout parameter
     * @return string|null
     */
    public static function get($params)
    {
        if (empty($params[self::P_FIELDS]))
            return null;

        // initialize parameters
        //

        $table_id = isset($params['id']) ? $params['id'] : ('table_id'.++self::$counter);

        if ($sort = Misc::paramExtract($params, self::P_SORT))
        {
            $sort_params = Misc::paramExtract($sort, self::S_PARAMS, array());
            unset($sort_params['s'][$table_id], $sort_params['p'][$table_id]);
        }
        else
            $sort_params = null;

        $colgroup = array();
        $rep = array();
        $headers = array();
        $headers_td = array();
        $sort_group_key = null;
        $sort_group_old = null;
        $unique_key = null;
        $unique_old = null;
        $unique_fields = null;
        $aligns = array();
        $formats = array();
        $checkboxes = array();
        $urls_field = array();
        $urls_address = array();
        $urls_target = array();
        $totals = false;
        $totals_fn = array();
        $totals_cnt = array();
        foreach (Misc::paramExtract($params, self::P_FIELDS) as $field=>$f)
        {
            if (! is_array($f))
                $f = array(self::F_WIDTH=>$f, self::F_ALIGN=>null, self::F_SORT=>array(self::F_SORT_EXCLUDE=>true));

            if ($colgroup[$field] = Misc::paramExtract($f, self::F_WIDTH))
                $colgroup[$field] = " width=\"{$colgroup[$field]}\"";
            $rep[$field] = Repo::get($field, isset($f[self::F_REPOSITORY]) ? $f[self::F_REPOSITORY] : array());

            if (isset($f[self::F_HEADER]))
            {
                if (isset($f[self::F_HEADER][self::F_HEADER_LABEL]))
                    $l = Repo::getLabel($field, $rep[$field], Misc::paramExtract($f[self::F_HEADER], self::F_HEADER_LABEL));
                else
                    $l = Repo::getLabel($field, $rep[$field], Repo::P_LABEL);
                if (isset($f[self::F_HEADER][self::F_HEADER_ABBR]))
                {
                    $headers[$field] = Html::asAbbr($l, Repo::getLabel($field
                        , $rep[$field]
                        , $f[self::F_HEADER][self::F_HEADER_ABBR] === true
                            ? Repo::P_LABEL_LONG
                            : $f[self::F_HEADER][self::F_HEADER_ABBR]
                        ))
                        ;
                    unset($f[self::F_HEADER][self::F_HEADER_ABBR]);
                }
                else
                    $headers[$field] = Html::encode($l);
                if ($f[self::F_HEADER])
                    $headers_td[$field] = $f[self::F_HEADER];
            }
            else
                $headers[$field] = Html::encode(Repo::getLabel($field, $rep[$field]));

            if (isset($f[self::F_CHECKBOX]) and isset($f[self::F_CHECKBOX][self::F_CHECKBOX_NAME]))
            {
                $checkboxes[$field] = $f[self::F_CHECKBOX][self::F_CHECKBOX_NAME];
                $headers[$field] = Html::inputCheckbox(array('id'=>"{$table_id}_chk"));
                HtmlPage::add(array(HtmlPage::DOM_READY=>array("{$table_id}_chk"
                    =>"$('#{$table_id}_chk').change(function(){\$('input:checkbox[name^=\"{$checkboxes[$field]}\"]','#$table_id').attr('checked',this.checked);});"
                    )));
                if (! isset($f[self::F_ALIGN]))
                    $f[self::F_ALIGN] = 'center';
            }

            if (isset($sort)
                and isset($sort[self::S_FIELD])
                and empty($f[self::F_SORT][self::F_SORT_EXCLUDE])
                and isset($f[self::F_SORT][self::F_SORT_GROUP])
                and($sort[self::S_FIELD] == $field or $sort[self::S_FIELD] == $field.self::SORT_REV_SUFFIX)
                )
                $sort_group_key = $f[self::F_SORT][self::F_SORT_GROUP] === true ? $field : $f[self::F_SORT][self::F_SORT_GROUP];

            if (isset($f[self::F_ALIGN]))
            {
                $aligns[$field] = " align=\"{$f[self::F_ALIGN]}\"";
                if (isset($headers_td[$field]))
                    $headers_td[$field]['align'] = $f[self::F_ALIGN];
                else
                    $headers_td[$field] = array('align'=>$f[self::F_ALIGN]);
            }
            elseif (! isset($headers_td[$field]))
                $headers_td[$field] = array('align'=>'left');
            elseif (! isset($headers_td[$field]['align']))
                $headers_td[$field]['align'] = 'left';

            if (isset($f[self::F_FORMAT]))
                $formats[$field] = $f[self::F_FORMAT];

            if (isset($f[self::F_URL])
                and isset($f[self::F_URL][self::F_URL_FIELD])
                and isset($f[self::F_URL][self::F_URL_ADDRESS])
                )
            {
                $urls_field[$field] = $f[self::F_URL][self::F_URL_FIELD];
                $urls_address[$field] = $f[self::F_URL][self::F_URL_ADDRESS];
                if (isset($f[self::F_URL][self::F_URL_TARGET]))
                    $urls_target[$field] = $f[self::F_URL][self::F_URL_TARGET];
            }

            if (isset($f[self::F_TOTAL]))
            {
                $totals = true;
                $totals_fn[$field] = ($f[self::F_TOTAL] === true or $f[self::F_TOTAL] === self::F_TOTAL_SUM)
                    ? self::F_TOTAL_SUM
                    : ($f[self::F_TOTAL] === self::F_TOTAL_COUNT
                        ? self::F_TOTAL_COUNT
                        : ($f[self::F_TOTAL] === self::F_TOTAL_AVG
                            ? self::F_TOTAL_AVG
                            : self::F_TOTAL_TEXT
                            )
                        )
                    ;
                self::$totals[$field] = $totals_fn[$field] === self::F_TOTAL_TEXT ? Html::encode($f[self::F_TOTAL]) : 0;
                $totals_cnt[$field] = $totals_fn[$field] === self::F_TOTAL_TEXT ? null : 0;
            }

            if (isset($headers_td[$field]))
                $headers_td[$field] = Html::attr($headers_td[$field]);

            if (isset($sort)
                and empty($f[self::F_SORT][self::F_SORT_EXCLUDE])
                )
            {
                $field_suf = '';
                if (isset($sort[self::S_FIELD]) and($sort[self::S_FIELD] == $field or $sort[self::S_FIELD] == $field.self::SORT_REV_SUFFIX))
                {
                    if (strrpos($sort[self::S_FIELD], self::SORT_REV_SUFFIX) !== false)
                    {
                        $headers[$field] .= Ui::TBL_SORT_ICON_UP;
                    }
                    else
                    {
                        $headers[$field] .= Ui::TBL_SORT_ICON_DOWN;
                        $field_suf = self::SORT_REV_SUFFIX;
                    }
                }
                $headers[$field] = sprintf('<a href="%s"%s>%s</a>'
                    , (isset($sort[self::F_SORT_SCRIPT]) ? $sort[self::F_SORT_SCRIPT] : '')
                        . Html::urlArgs('?', array_merge_recursive($sort_params, array('s'=>array($table_id=>$field.$field_suf))))
                    , isset($sort[self::F_SORT_TARGET]) ? " target=\"{$sort[self::F_SORT_TARGET]}\"" : ''
                    , $headers[$field]
                    );
            }
        }

        if (isset($params[self::P_ROWS][self::R_VALUES]))
        {
            $rows_values = $params[self::P_ROWS][self::R_VALUES];
            $rows_td = isset($params[self::P_ROWS][self::R_TD]) ? $params[self::P_ROWS][self::R_TD] : array();
            $rows_tr = isset($params[self::P_ROWS][self::R_TR]) ? $params[self::P_ROWS][self::R_TR] : array();
        }
        else
        {
            $rows_values = $params[self::P_ROWS];
            $rows_td = array();
            $rows_tr = array();
        }
        unset($params[self::P_ROWS]);

        if (isset($params[self::P_UNIQUE]))
        {
            if (isset($sort[self::S_FIELD])
                and isset($params[self::P_UNIQUE][self::U_FIELDS])
                and isset($params[self::P_UNIQUE][self::U_KEY])
                and strpos($params[self::P_UNIQUE][self::U_FIELDS], $sort[self::S_FIELD]) !== false
                )
            {
                $unique_fields = array_flip(explode(',', $params[self::P_UNIQUE][self::U_FIELDS]));
                $unique_key = $params[self::P_UNIQUE][self::U_KEY];
            }
            unset($params[self::P_UNIQUE]);
        }

        $layout = Misc::paramExtract($params, self::P_LAYOUT);
        $prefix = Misc::paramExtract($params, self::P_PREFIX);
        $suffix = Misc::paramExtract($params, self::P_SUFFIX);

        $empty_display = true;
        $empty_label = dgettext(Nls::FW_DOMAIN, 'No records');
        if (isset($params[self::P_EMPTY]))
        {
            if (is_string($params[self::P_EMPTY]))
                $empty_label = $params[self::P_EMPTY];
            else
                $empty_display = $params[self::P_EMPTY];
            unset($params[self::P_EMPTY]);
        }

        ob_start();
        if ($rows_values)
        {
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
                    Misc::paramAdd($tr, Ui::TABLE_NEW_GROUP_CLASS);
                    $sort_group_old = $row[$sort_group_key];
                }

                // cycle through the fields
                //

                foreach ($rep as $field=>$r)
                {
                    // set cell align
                    if (isset($aligns[$field]))
                    {
                        if (isset($td[$field]))
                            $td[$field] .= $aligns[$field];
                        else
                            $td[$field] = $aligns[$field];
                    }

                    // set cell value

                    // whether checkbox...
                    if (isset($checkboxes[$field]))
                        $values[$field] = Html::inputCheckbox(array('name'=>$checkboxes[$field]."[{$row[$field]}]", 'value'=>$row[$field]));
                    // ...or empty duplicated value...
                    elseif (isset($unique_key) and $unique_old != $row[$unique_key] and isset($unique_fields[$field]))
                        $values[$field] = '';
                    // ...or normal value
                    else
                    {
                        $v = Repo::asHtml($field, $row[$field], $r);
                        if (isset($urls_field[$field]))
                            $v = sprintf('<a href="%s"%s>%s</a>'
                                , sprintf($urls_address[$field], $row[$urls_field[$field]])
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

            // add totals row
            //

            if ($totals)
            {
                $t = array();
                foreach ($rep as $field=>$r)
                {
                    if (isset(self::$totals[$field]))
                    {
                        if ($totals_fn[$field] === self::F_TOTAL_COUNT)
                            self::$totals[$field] = $totals_cnt[$field];
                        elseif ($totals_fn[$field] == self::F_TOTAL_AVG)
                            self::$totals[$field] /= $totals_cnt[$field];
                        $t[$field] = Repo::asHtml($field
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
                echo Html::tr(array(Html::P_VALUES=>$t, Html::P_TD_ATTR=>$aligns, 'class'=>Ui::TABLE_TOTAL_ROW_CLASS));
            }

            // finalize table
            //

            echo '</tbody>';
            if (isset($suffix))
                echo '<tfoot>'
                    , Html::tr(array(Html::P_VALUES=>array($suffix), Html::P_TD_ATTR=>array(' colspan="'.count($headers).'"')))
                    , '</tfoot>'
                    ;
            echo Html::tableStop();
        }
        else
            echo '<div'.Html::attr(array('class'=>Ui::TABLE_EMPTY_CLASS)).'>'
                , Html::encode($empty_label)
                , '</div>'
                ;

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
