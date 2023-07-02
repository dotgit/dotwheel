<?php

/**
 * handles html form display, list of required fields etc.
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Ui;

use Dotwheel\Db\Repo;
use Dotwheel\Nls\Nls;
use Dotwheel\Nls\Text;
use Dotwheel\Util\Params;

class HtmlTable
{
    public const CGI_FILTERS = 'f';
    public const CGI_SORT = 's';
    public const CGI_PAGE = 'p';

    public const SORT_REV_SUFFIX = '-';
    public const SORT_REV_SUFFIX_LENGTH = 1;

    public const P_ROWS = 1;
    public const P_TD = 2;
    public const P_TR = 3;
    public const P_FIELDS = 4;
    public const P_SORT = 5;
    public const P_PREFIX = 6;
    public const P_SUFFIX = 7;
    public const P_GROUP_CLASS = 8;
    public const P_TOTAL_CLASS = 9;
    public const P_FOOTERS = 10;

    public const R_VALUES = -1;
    public const R_TD = -2;
    public const R_TR = -3;

    public const F_WIDTH = 1;
    public const F_ALIGN = 2;
    public const F_REPO = 3;
    public const F_HEADER = 4;
    public const F_HEADER_TYPE = 41;
    public const F_HEADER_ABBR = 42;
    public const F_CHECKBOX = 5;
    public const F_SORT = 6;
    public const F_SORT_EXCLUDE = 61;
    public const F_SORT_GROUP = 62;
    public const F_FMT = 7;
    public const F_URL = 8;
    public const F_URL_FIELD = 81;
    public const F_URL_FMT = 82;
    public const F_URL_TARGET = 83;
    public const F_HIDDEN = 100;
    public const F_ASIS = 101;

    public const S_FIELD = 1;
    public const S_ICON = 2;
    public const S_REVERSE = 3;
    public const S_SCRIPT = 4;
    public const S_PARAMS = 5;
    public const S_TARGET = 6;

    public const L_LPT = 1;
    public const L_XL = 2;
    public const L_CSV = 3;

    public const PAGES_PER_BLOCK = 10;
    public const ITEMS_PER_PAGE = 100;

    /** @var int    autoincrement for automatic table id if id parameter omitted */
    protected static int $counter = 0;

    /** @var array  if totals row displayed then the values are stored here */
    public static array $totals = [];

    /** @var array  list of elements (tables) with corresponding details, like
     *  <pre>{
     *      users:
     *      {CGI_SORT:
     *          'u_lastname,r',
     *          CGI_FILTERS:{u_status:'online',u_lastname:'tref'},
     *          CGI_PAGE:2,
     *      },
     *      roles:...
     *  }</pre>
     *  details per table come from the following request parameters:
     *  <li>s (sort): s[users]=u_lastname,r
     *  <li>f (filters): f[users][u_status]=online&f[users][u_lastname]=tref
     *  <li>p (page): p[users]=2
     */
    public static array $details = [];


    /**
     * read request cgi parameters and fill in the self::$details
     */
    public static function init()
    {
        // identify $details
        if (!empty($_REQUEST[self::CGI_FILTERS]) and is_array($_REQUEST[self::CGI_FILTERS])) {
            self::$details[self::CGI_FILTERS] = $_REQUEST[self::CGI_FILTERS];
        }

        if (!empty($_REQUEST[self::CGI_SORT]) and is_array($_REQUEST[self::CGI_SORT])) {
            self::$details[self::CGI_SORT] = $_REQUEST[self::CGI_SORT];
        }

        if (!empty($_REQUEST[self::CGI_PAGE]) and is_array($_REQUEST[self::CGI_PAGE])) {
            self::$details[self::CGI_PAGE] = $_REQUEST[self::CGI_PAGE];
        }
    }

    /** retrieve cgi parameters passed for the $element_id and return them in translated form
     * (hash of filters, sort column name, the reverse sort attribute, page num)
     *
     * @param string $element_id element id to search in self::$details
     * @param array $sort_cols {fld1:true, fld2:true, ...}
     * @param string $sort_default default sort column, like 'fld1'
     * @return array [{filters}, 'field_name', <i>true</i> if reverse order or <i>false</i> otherwise, page_num]
     */
    public static function translateDetails(string $element_id, array $sort_cols, string $sort_default): array
    {
        if (empty(self::$details)) {
            return [null, $sort_default, false, null];
        }

        $filters = self::$details[self::CGI_FILTERS][$element_id] ?? null;

        $sort_fld = self::$details[self::CGI_SORT][$element_id] ?? $sort_default;
        if (isset($sort_cols[$sort_fld])) {
            $sort_rev = false;
        } elseif (isset($sort_cols[substr($sort_fld, 0, -self::SORT_REV_SUFFIX_LENGTH)])) {
            $sort_fld = substr($sort_fld, 0, -self::SORT_REV_SUFFIX_LENGTH);
            $sort_rev = true;
        } else {
            $sort_fld = null;
            $sort_rev = false;
        }

        $page = self::$details[self::CGI_PAGE][$element_id] ?? null;

        return [$filters, $sort_fld, $sort_rev, $page];
    }

    /** table in html form
     *
     * @param array $params list of table parameters:
     * <pre>
     * {
     *  id:'tbl1',
     *  P_GROUP_CLASS:'_grp',
     *  P_TOTAL_CLASS:'_ttl',
     *  P_FIELDS:{
     *      fld1:{
     *          F_WIDTH:'20%',
     *          F_ALIGN:'center',
     *          F_REPO:{field repository arguments},
     *          F_HEADER:{
     *              F_HEADER_TYPE:Repo::P_LABEL_SHORT|null,
     *              F_HEADER_ABBR:Repo::P_LABEL_LONG|true|null,
     *              th tag arguments,
     *          },
     *          F_CHECKBOX:true,   // replaces header with a checkbox and a toggler js code
     *          F_HIDDEN:true,
     *          F_ASIS:true,
     *          F_SORT:{
     *              F_SORT_EXCLUDE:true,
     *              F_SORT_GROUP:'fld2'|true,
     *          },
     *          F_FMT:'value: %s',
     *          F_URL:{
     *              F_URL_FIELD:'fld2',
     *              F_URL_ADDRESS:'/path/script.php?id=%u&mode=edit',
     *              F_URL_TARGET:'_blank',
     *          },
     *      },
     *      ...
     *  },
     *  P_SORT:{
     *      S_FIELD:'fld1',
     *      S_ICON:ICN_SORT_CHAR,
     *      S_REVERSE:true,
     *      S_SCRIPT:'/this/script.php',
     *      S_PARAMS:{
     *          CGI_SORT:{tbl_id:'fld_current'},  // CGI_SORT[tbl_id] param will be replaced by %s
     *          CGI_PAGE:{tbl_id:{f1:'on'}},      // CGI_PAGE[tbl_id] param for current table will be unset
     *      },
     *      S_TARGET:'_blank',
     *  },
     *  P_ROWS:{
     *      r1:{fld1:'value',fld2:'value',fld3:'value'},
     *      r2:{fld1:'value',fld2:'value',fld3:'value'},
     *  },
     *  P_FOOTERS:{
     *      r1:{fld1:'value',fld2:'value',fld3:'value'},
     *      r2:{fld1:'value',fld2:'value',fld3:'value'},
     *  },
     *  P_TD:{
     *      r1:{fld3:' td tag attributes'},
     *      ...
     *  },
     *  P_TR:{
     *      r2:' tr tag attributes'},
     *      ...
     *  },
     *  P_PREFIX:'',
     *  P_SUFFIX:'',
     *  table tag arguments,
     * }
     * @return ?string
     * @todo implement layout parameter
     */
    public static function get(array $params): ?string
    {
        if (empty($params[self::P_FIELDS])) {
            return null;
        }

        // initialize parameters
        //

        $table_id = Params::extract($params, 'id', 'tid_' . ++self::$counter);
        Params::add($params, $table_id, 'id');

        if ($sort = Params::extract($params, self::P_SORT)) {
            $sort_params = Params::extract($sort, self::S_PARAMS, []);
            unset($sort_params[self::CGI_SORT][$table_id], $sort_params[self::CGI_PAGE][$table_id]);
        } else {
            $sort_params = null;
        }

        $colgroup = [];
        $repo = [];
        $headers = [];
        $headers_td = [];
        $sort_group_key = null;
        $sort_group_old = null;
        $formats = [];
        $checkboxes = [];
        $urls_field = [];
        $urls_fmt = [];
        $urls_target = [];
        $hidden = [];
        $asis = [];

        $group_class = Params::extract($params, self::P_GROUP_CLASS, '_grp');

        foreach (Params::extract($params, self::P_FIELDS, []) as $field => $f) {
            if (!is_array($f)) {
                $f = [self::F_WIDTH => $f, self::F_ALIGN => null, self::F_SORT => [self::F_SORT_EXCLUDE => true]];
            }

            $colgroup[$field] = ($w = Params::extract($f, self::F_WIDTH))
                ? ['width' => $w]
                : [];
            $repo[$field] = Repo::get($field, $f[self::F_REPO] ?? []);

            if (isset($f[self::F_HEADER])) {
                if ($f[self::F_HEADER] !== false) {
                    $h = Params::extract($f[self::F_HEADER], self::F_HEADER_TYPE);
                    $abbr = Params::extract($f[self::F_HEADER], self::F_HEADER_ABBR);

                    if ($h) {
                        $headers[$field] = Html::encode(Repo::getLabel($field, $h, $repo[$field]));
                    } elseif ($abbr) {
                        $headers[$field] = Html::asAbbr(
                            Html::encode(Repo::getLabel($field, Repo::P_LABEL_SHORT, $repo[$field])),
                            Repo::getLabel($field, $abbr === true ? Repo::P_LABEL_LONG : $abbr, $repo[$field])
                        );
                    } elseif (is_scalar($f[self::F_HEADER])) {
                        $headers[$field] = $f[self::F_HEADER];
                    } else {
                        $headers[$field] = Html::encode(Repo::getLabel($field, null, $repo[$field]));
                    }

                    if (is_array($f[self::F_HEADER])) {
                        $headers_td[$field] = $f[self::F_HEADER];
                    }
                }
            } else {
                $headers[$field] = Html::encode(Repo::getLabel($field, null, $repo[$field]));
            }

            if (isset($f[self::F_CHECKBOX])) {
                $checkboxes[$field] = $f[self::F_CHECKBOX];
                $headers[$field] = Html::inputCheckbox(['id' => "{$table_id}_chk"]);
                HtmlPage::add([
                    HtmlPage::DOM_READY => [
                        "{$table_id}_chk" =>
                            <<<EOm
                            $('#{$table_id}_chk')
                            .change(function(){\$('input:checkbox[name^=\"$field\"]','#$table_id').prop('checked',this.checked);})
                            ;
                            EOm,
                    ],
                ]);
                if (!isset($f[self::F_ALIGN])) {
                    $f[self::F_ALIGN] = 'center';
                }
            }

            if (isset($sort)
                and isset($sort[self::S_FIELD])
                and empty($f[self::F_SORT][self::F_SORT_EXCLUDE])
                and isset($f[self::F_SORT][self::F_SORT_GROUP])
                and $sort[self::S_FIELD] == $field
            ) {
                $sort_group_key = $f[self::F_SORT][self::F_SORT_GROUP] === true
                    ? $field
                    : $f[self::F_SORT][self::F_SORT_GROUP];
            }

            if (isset($f[self::F_ALIGN])) {
                $colgroup[$field]['align'] = $f[self::F_ALIGN];
            }

            if (isset($f[self::F_FMT])) {
                $formats[$field] = $f[self::F_FMT];
            }

            if (isset($f[self::F_URL])
                and isset($f[self::F_URL][self::F_URL_FIELD])
                and isset($f[self::F_URL][self::F_URL_FMT])
            ) {
                $urls_field[$field] = $f[self::F_URL][self::F_URL_FIELD];
                $urls_fmt[$field] = $f[self::F_URL][self::F_URL_FMT];
                if (isset($f[self::F_URL][self::F_URL_TARGET])) {
                    $urls_target[$field] = $f[self::F_URL][self::F_URL_TARGET];
                }
            }

            if (isset($sort)
                and empty($f[self::F_SORT][self::F_SORT_EXCLUDE])
            ) {
                $s_sfx = ($sort[self::S_FIELD] == $field and empty($sort[self::S_REVERSE]))
                    ? self::SORT_REV_SUFFIX
                    : '';
                $headers[$field] = sprintf(
                    '<a href="%s%s"%s>%s</a>%s',
                    $sort[self::S_SCRIPT] ?? '',
                    Html::urlArgs(
                        '?',
                        array_merge_recursive($sort_params, [
                            self::CGI_SORT => [$table_id => "$field$s_sfx"],
                        ])
                    ),
                    isset($sort[self::S_TARGET]) ? " target=\"{$sort[self::S_TARGET]}\"" : '',
                    $headers[$field],
                    (isset($sort[self::S_FIELD]) and $sort[self::S_FIELD] == $field and isset($sort[self::S_ICON]))
                        ? "&nbsp;{$sort[self::S_ICON]}"
                        : ''
                );
            }

            if (isset($f[self::F_HIDDEN])) {
                $hidden[$field] = true;
                unset($headers[$field], $headers_td[$field]);
            }

            if (isset($f[self::F_ASIS])) {
                $asis[$field] = true;
            }
        }

        $rows_values = Params::extract($params, self::P_ROWS, []);
        $footers_values = Params::extract($params, self::P_FOOTERS, []);
        $rows_td = Params::extract($params, self::P_TD, []);
        $rows_tr = Params::extract($params, self::P_TR, []);
        $prefix = Params::extract($params, self::P_PREFIX);
        $suffix = Params::extract($params, self::P_SUFFIX);

        ob_start();

        // start table
        //

        echo Html::tableStart($params + [Html::P_COLGROUP => $colgroup]),
            Html::thead([Html::P_VALUES => $headers, Html::P_TD_ATTR => $headers_td, Html::P_PREFIX => $prefix]),
            '<tbody>';

        // cycle through the rows
        //

        foreach ($rows_values as $krow => $row) {
            $values = [];
            $td = $rows_td[$krow] ?? [];
            $tr = $rows_tr[$krow] ?? [];

            // grouping
            if (isset($sort_group_key) and $sort_group_old != $row[$sort_group_key]) {
                Params::add($tr, $group_class);
                $sort_group_old = $row[$sort_group_key];
            }

            // cycle through the fields
            //

            foreach ($repo as $field => $r) {
                // set cell value

                if (isset($checkboxes[$field])) {
                    // whether checkbox...
                    $values[$field] = Html::inputCheckbox([
                        'name' => "{$field}[{$row[$field]}]",
                        'value' => $row[$field],
                    ]);
                } elseif (isset($asis[$field])) {
                    // ...as is column
                    $values[$field] = $row[$field];
                } elseif (empty($hidden[$field])) {
                    // ...or normal value (if not hidden)
                    $v = Repo::asHtmlStatic($field, $row[$field], $r);
                    if (isset($urls_field[$field])) {
                        $v = sprintf(
                            '<a href="%s"%s>%s</a>',
                            sprintf($urls_fmt[$field], $row[$urls_field[$field]]),
                            isset($urls_target[$field]) ? " target=\"$urls_target[$field]\"" : '',
                            $v
                        );
                    }
                    $values[$field] = isset($formats[$field]) ? sprintf($formats[$field], $v) : $v;
                }
            }

            echo Html::tr([
                Html::P_VALUES => $values,
                Html::P_TD_ATTR => $td ?: null,
            ] + $tr);
        }

        // handle table footer rows
        //

        $tfoot = [];

        foreach ($footers_values as $krow => $row) {
            $values = [];
            $td = $rows_td[$krow] ?? [];
            $tr = $rows_tr[$krow] ?? [];

            // cycle through the fields
            //

            foreach ($repo as $field => $r) {
                // set cell value

                if (isset($asis[$field])) {
                    // whether as is column...
                    $values[$field] = $row[$field];
                } elseif (empty($hidden[$field])) {
                    // ...or normal value (if not hidden)
                    $v = Repo::asHtmlStatic($field, $row[$field], $r);
                    $values[$field] = isset($row[$field])
                        ? (isset($formats[$field]) ? sprintf($formats[$field], $v) : $v)
                        : null;
                }
            }

            $tfoot[] = Html::tr([
                Html::P_VALUES => $values,
                Html::P_TD_ATTR => $td ?: null,
            ] + $tr);
        }

        if (isset($suffix)) {
            $tfoot[] = Html::tr([
                Html::P_VALUES => [$suffix],
                Html::P_TD_ATTR => [' colspan="' . count($headers) . '"'],
            ]);
        }

        if ($tfoot) {
            echo '<tfoot>', implode('', $tfoot);
        }

        // stop table
        //

        echo Html::tableStop();

        return ob_get_clean();
    }

    /** draw the pagination element
     *
     * @param string $action script to load for the next page
     * @param array $hidden url params to pass to the next page
     * @param int $total total number of items
     * @param int $page current page (0-based)
     * @param string $table_id table id
     * @param int $items items per page
     * @return string pagination html
     */
    public static function pagination(
        string $action,
        array $hidden,
        int $total,
        int $page,
        string $table_id,
        int $items = self::ITEMS_PER_PAGE
    ): string {
        if ($total) {
            if ($items and $total > $items) {
                $str_link_3 = '<li%s><a href="%s">%s</a></li>';
                $str_act_1 = '<li class="active"><a>%s</a></li>';
                unset($hidden['p'][$table_id]);
                $args0 = isset($hidden) ? Html::urlArgs('?', $hidden) : '';
                $pages = (int)(($total - 1) / $items) + 1;
                $page_first = (int)($page / self::PAGES_PER_BLOCK) * self::PAGES_PER_BLOCK;
                $page_next = min($page_first + self::PAGES_PER_BLOCK, $pages);
                $url0 = "$action$args0";
                $url = $url0 . ($args0 ? "&p%5B$table_id%5D=" : "?p%5B$table_id%5D=");
                $parts = [];

                // first/prev page
                if ($page) {
                    $parts[] = sprintf($str_link_3, '', $url0, '&laquo;');
                    $parts[] = sprintf(
                        $str_link_3,
                        '',
                        $page > 1 ? ($url . ($page - 1)) : $url0,
                        '&larr; ' . Text::dget(Nls::FW_DOMAIN, 'Prev')
                    );
                }
                // numbered pages
                for ($i = $page_first; $i < $page_next; ++$i) {
                    $parts[] = $i == $page
                        ? sprintf($str_act_1, $i + 1)
                        : sprintf($str_link_3, '', $i ? "$url$i" : $url0, $i + 1);
                }
                // next/last page
                if ($page < $pages - 1) {
                    $parts[] = sprintf(
                        $str_link_3,
                        '',
                        $url . ($page + 1),
                        Text::dget(Nls::FW_DOMAIN, 'Next') . ' &rarr;'
                    );
                    $parts[] = sprintf($str_link_3, '', $url . ($pages - 1), '&raquo;');
                }

                return '<ul>' . implode('', $parts) . "</ul>";
            } else {
                return "[$total]";
            }
        } else {
            return '';
        }
    }
}
