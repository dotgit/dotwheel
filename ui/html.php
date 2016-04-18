<?php

/**
 * HTML output functions.
 *
 * Formats html elements. Functions return values that are safe to directly display
 * on a page.
 *
 * Parameters passed as {@code attr} are text strings starting with a space
 * character to be simply attached to the opening tag.
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Ui;

use Dotwheel\Nls\Nls;
use Dotwheel\Util\Params;

class Html
{
    const P_CAPTION         = 1;
    const P_CAPTION_ATTR    = 2;
    const P_COLGROUP        = 3;
    const P_TAG             = 4;
    const P_VALUES          = 5;
    const P_TD_ATTR         = 6;
    const P_DATETIME        = 7;
    const P_BLANK           = 8;
    const P_TYPE            = 9;
    const P_ITEMS           = 10;
    const P_DELIM           = 11;
    const P_PREFIX          = 12;
    const P_SUFFIX          = 13;
    const P_WRAP_FMT        = 14;
    const P_HEADER          = 15;
    const P_HEADER_ATTR     = 16;



    /** Html attributes */

    /** returns html tag attributes
     * @param array $params {src:'myframe.php', title:'My frame', class:'ifr_class'}
     * @return string       attributes of an html tag
     */
    public static function attr($params)
    {
        $ret = array();
        foreach ($params as $attr=>$value) {
            if (isset($value) and ! \is_int($attr)) {
                $ret[] = " $attr=\"".self::encodeAttr($value).'"';
            }
        }

        return $ret ? \implode('', $ret) : '';
    }

    /** returns a string with url-encoded parameters
     * @param string $prefix    prefix to use (normally '?')
     * @param array $params     hash of parameters to encode, like {a:'b',c:{d:'e'}}
     * @return string           url-encoded list, like '?a=b&c%5Bd%5D=e'
     */
    public static function urlArgs($prefix, $params)
    {
        $args = \is_array($params) ? \http_build_query($params) : '';

        return \strlen($args) ? "$prefix$args" : '';
    }



    /** Html tables */

    /** returns table open tag followed by a CAPTION and COLGROUP constructs
     * @param array $params {
     *  P_CAPTION:'My table',
     *  P_CAPTION_ATTR:{style:'color:red'},
     *  P_COLGROUP:[{width:"80%"},{width:"20%"}],
     *  table tag attributes
     * }
     * @return string
     */
    public static function tableStart($params = array())
    {
        $id = Params::extract($params, 'id');
        $caption_attr = Params::extract($params, self::P_CAPTION_ATTR);
        if ($caption = Params::extract($params, self::P_CAPTION)) {
            $caption = "<caption$caption_attr>$caption</caption>";
        }
        $colgroup_html = null;
        if ($colgroup = Params::extract($params, self::P_COLGROUP)) {
            $k = 0;
            $extra = 0;
            foreach ($colgroup as &$col) {
                $style = [];
                if ($align = Params::extract($col, 'align')) {
                    $style[] = "text-align:$align;";
                }
                if ($width = Params::extract($col, 'width')) {
                    $style[] = "width:$width;";
                }
                if ($style) {
                    $style_html = implode('', $style);
                    HtmlPage::add(array(
                        HtmlPage::STYLE=>array(
                            __METHOD__."-$id-$k"=>"table#$id td:first-child".\str_repeat(' + td', $k).
                            "{{$style_html}}".
                            "table#$id th:first-child".\str_repeat(' + th', $k).
                            "{{$style_html}}"
                        )
                    ));
                }
                ++$k;
                if ($col) {
                    ++$extra;
                }
            }
            if ($extra) {
                $colgroup_html = self::colgroup($colgroup);
            }
        }

        return '<table'.self::attr(array('id'=>$id) + $params).">$caption$colgroup_html";
    }

    /** returns table closing tag
     * @return string
     */
    public static function tableStop()
    {
        return '</table>';
    }

    /** returns table heading row implemented with THEAD construct
     * @param array $params is passed to self::tr()
     * @return string       table row wrapped by thead tags
     */
    public static function thead($params)
    {
        return '<thead>'.
            (($prefix = Params::extract($params, self::P_PREFIX))
                ? self::tr(array(
                    self::P_VALUES=>array($prefix),
                    self::P_TD_ATTR=>array('colspan'=>\count($params[self::P_VALUES]))
                ))
                : ''
            ).
            self::tr($params + array(self::P_TAG=>'th'));
    }

    /** returns table columns description implemented with COLGROUP construct
     * @param array $cols   [{width:"80%"},{width:"20%"}]
     * @return string
     */
    public static function colgroup($cols)
    {
        return '<colgroup><col'.
            \implode('><col', \array_map(function ($at) {return Html::attr($at);}, $cols)).
            "></colgroup>\n";
    }

    /** returns table row with a set of TD or TH cells
     * @param array $params {
     *  P_VALUES:{'cell1','cell2','cell3'},
     *  P_TD_ATTR:{null,null,' style="text-align:right"'}|null,
     *  P_TAG:'th'|'td'|null,
     *  tr tag attributes
     * }
     * @return string
     */
    public static function tr($params)
    {
        $attr = \array_diff_key($params, array(self::P_VALUES=>true, self::P_TD_ATTR=>true, self::P_TAG=>true));
        $res = '<tr'.($attr ? self::attr($attr) : '').'>';
        $tag = isset($params[self::P_TAG]) ? $params[self::P_TAG] : 'td';

        if (isset($params[self::P_TD_ATTR])) {
            foreach ($params[self::P_VALUES] as $k=> $v) {
                $res .= "<$tag".
                    (isset($params[self::P_TD_ATTR][$k]) ? self::attr($params[self::P_TD_ATTR][$k]) : '').
                    ">$v</$tag>";
            }
        } else {
            foreach ($params[self::P_VALUES] as $v) {
                $res .= "<$tag>$v</$tag>";
            }
        }

        return $res."</tr>\n";
    }
    /** Html forms input elements */

    /** input element(if id is set and name is omitted then name = id)
     * @param array $params {input tag attributes}
     * @return string
     * @see http://www.w3.org/TR/html4/interact/forms.html#h-17.4
     */
    public static function input($params)
    {
        if (isset($params['id']) and empty($params['name'])) {
            $params['name'] = $params['id'];
        }

        return '<input'.self::attr($params).'>';
    }

    /**
     * @param array $params {input tag attributes}
     * @return string
     */
    public static function inputText($params)
    {
        if (empty($params['type'])) {
            $params['type'] = 'text';
        }
        if (!isset($params['maxlength'])) {
            $params['maxlength'] = 255;
        }

        return self::input($params);
    }

    /**
     * @param array $params {textarea tag attributes}
     * @return string
     * @see http://www.w3.org/TR/html4/interact/forms.html#h-17.7
     */
    public static function inputTextarea($params)
    {
        if (isset($params['id']) and empty($params['name'])) {
            $params['name'] = $params['id'];
        }
        if (!isset($params['rows'])) {
            $params['rows'] = 5;
        }
        $value = Params::extract($params, 'value');
        $attr = self::attr($params);

        return "<textarea$attr>$value</textarea>";
    }

    /**
     * @param array $params {input tag attributes}
     * @return string
     */
    public static function inputInt($params)
    {
        return self::input($params + array('type'=>'text', 'maxlength'=>10));
    }

    /**
     * @param array $params {input tag attributes}
     * @return string
     */
    public static function inputCents($params)
    {
        if (isset($params['value'])) {
            $params['value'] = \str_replace('.', Nls::$formats[Nls::P_MON_DECIMAL_CHAR], $params['value'] / 100);
        }

        return self::input($params + array('type'=>'text', 'maxlength'=>10));
    }

    /**
     * @param array $params {P_DATETIME:true, input tag attributes}
     * @return string
     */
    public static function inputDate($params)
    {
        $datetime = Params::extract($params, self::P_DATETIME);
        if (!empty($params['value'])) {
            $params['value'] = (empty($params['type'])
                or $params['type'] == 'date'
                or $params['type'] == 'datetime'
                or $params['type'] == 'datetime-local'
            )
                ? self::asDateRfc($params['value'], $datetime)
                : self::asDateNls($params['value'], $datetime);
        }

        return self::input($params + array('type'=>'date', 'maxlength'=>20));
    }

    /**
     * @param array $params {
     *  P_ITEMS:{a:'Active',i:'Inactive'},
     *  P_BLANK:'First line message',
     *  value:'a',
     *  select tag attributes
     * }
     * @return string
     * @see http://www.w3.org/TR/html4/interact/forms.html#h-17.6
     */
    public static function inputSelect($params)
    {
        if (isset($params['id']) and empty($params['name'])) {
            $params['name'] = $params['id'];
        }
        $value = Params::extract($params, 'value');
        $items = array();

        if (($blank = Params::extract($params, self::P_BLANK)) !== null) {
            $items[] = \strlen($blank)
                ? ('<option value="">'.self::encode($blank)."</option>")
                : "<option></option>";
        }

        foreach (Params::extract($params, self::P_ITEMS, array()) as $k=> $v) {
            $items[] = ($k == $value && !empty($k))
                ? "<option value=\"$k\" selected=\"on\">$v</option>"
                : "<option value=\"$k\">$v</option>";
        }
        $attr = self::attr($params);

        return "<select$attr>\n".\implode('', $items).'</select>';
    }

    /** multiple checkboxes with labels (names are suffixed with *[k] and ids with *_k)
     * @param array $params {
     *  id:'fld',
     *  name:'field_name',
     *  value:'a,i'|{a:'a',i:'i'},
     *  P_ITEMS:{a:'Active',i:'Inactive'},
     *  P_DELIM:'&lt;br&gt;',
     *  P_PREFIX:'',
     *  P_SUFFIX:'',
     *  P_FMT:'%s',
     *  P_HEADER_ATTR:{}
     * }
     * @return string
     */
    public static function inputSet($params)
    {
        $id = Params::extract($params, 'id');
        $name = Params::extract($params, 'name');
        if (isset($id) and empty($name)) {
            $name = $id;
        }
        $value = Params::extract($params, 'value');
        $delim = Params::extract($params, self::P_DELIM, '<br>');
        $item_prefix = Params::extract($params, self::P_PREFIX);
        $item_suffix = Params::extract($params, self::P_SUFFIX);
        if (!\is_array($value)) {
            if (isset($value)) {
                $_ = \explode(',', $value);
                $value = \array_combine($_, $_);
            } else {
                $value = array();
            }
        }

        $items = array();
        foreach (Params::extract($params, self::P_ITEMS) as $k=>$v) {
            $items[] = self::inputCheckbox(array(
                'name'=>"{$name}[$k]",
                'checked'=>isset($value[$k]) ? 'on' : null,
                'value'=>$k,
                self::P_HEADER=>$v,
            ) + $params);
        }

        return $item_prefix.\implode($delim, $items).$item_suffix;
    }

    /** returns html radios with labels
     * @param array $params {
     *  id:'fld',
     *  name:'field_name',
     *  value:'a',
     *  P_ITEMS:{a:'Active',i:'Inactive'},
     *  P_DELIM:'&lt;br&gt;',
     *  P_FMT:'%s',
     *  P_PREFIX:'',
     *  P_SUFFIX:'',
     *  P_HEADER_ATTR:{'class':'checkbox'}
     * }
     * @return string
     */
    public static function inputRadio($params)
    {
        $id = Params::extract($params, 'id');
        $name = Params::extract($params, 'name');
        if (isset($id) and empty($name)) {
            $name = $id;
        }
        $item_prefix = Params::extract($params, self::P_PREFIX);
        $item_suffix = Params::extract($params, self::P_SUFFIX);
        $delim = Params::extract($params, self::P_DELIM, '<br>');
        $fmt = Params::extract($params, self::P_WRAP_FMT);
        $value = Params::extract($params, 'value');
        if ($label_attr = Params::extract($params, self::P_HEADER_ATTR)) {
            $label_attr = Html::attr($label_attr);
        }

        $items = array();
        foreach (Params::extract($params, self::P_ITEMS, array()) as $k=>$v) {
            $item = "<label$label_attr><input".
                self::attr(array(
                    'type'=>'radio',
                    'name'=>$name,
                    'value'=>$k,
                    'checked'=>($k == $value and ! empty($k)) ? 'on' : null
                    ) + $params).
                ">$v</label>";
            $items[] = isset($fmt) ? \sprintf($fmt, $item) : $item;
        }

        return $item_prefix.\implode($delim, $items).$item_suffix;
    }

    /** returns html checkbox element
     * @param array $params {
     *  P_HEADER:'string',
     *  P_HEADER_ATTR:{label tag attributes},
     *  P_DELIM:' ',
     *  P_WRAP_FMT:'%s',
     *  input tag attributes
     * }
     * @return string
     */
    public static function inputCheckbox($params)
    {
        $attr = \array_diff_key($params, array(
            self::P_WRAP_FMT=>true,
            self::P_HEADER=>true,
            self::P_HEADER_ATTR=>true,
            self::P_DELIM=>true
        ));
        $fmt = isset($params[self::P_WRAP_FMT]) ? $params[self::P_WRAP_FMT] : '%s';
        $header = isset($params[self::P_HEADER]) ? $params[self::P_HEADER] : null;
        $header_attr = isset($params[self::P_HEADER_ATTR]) ? Html::attr($params[self::P_HEADER_ATTR]) : null;
        $delim = isset($params[self::P_DELIM]) ? $params[self::P_DELIM] : ' ';
        $checkbox = self::input(array('type'=>'checkbox') + $attr);
        if (isset($header)) {
            $checkbox = "<label$header_attr>$checkbox$delim$header</label>";
        } elseif (isset($header_attr)) {
            $checkbox = "<div$header_attr>$checkbox</div>";
        }

        return \sprintf($fmt, $checkbox);
    }



    /** Html values */

    /** translates special chars in the string to html entities.
     * @param string $str   value to convert
     * @return string
     */
    public static function encode($str)
    {
        return \htmlspecialchars($str, \ENT_NOQUOTES, Nls::$charset);
    }

    /** translates special chars in the string to html entities.
     * @param string $str   value to convert
     * @return string
     */
    public static function encodeAttr($str)
    {
        return \htmlspecialchars($str, \ENT_COMPAT, Nls::$charset);
    }

    /** translates special chars in the string to html entities, then converts newlines to &lt;br /&gt;.
     * @param string $str       value to convert
     * @param string $format    try some meta formatting
     * @return string
     */
    public static function encodeNl($str, $format = false)
    {
        if (!$format) {
            return \nl2br(\htmlspecialchars($str, \ENT_NOQUOTES, Nls::$charset));
        } else {
            return \nl2br(\preg_replace(
                array('#^[-*]\s+#m', '#([\(“‘«])\s+#u', '#\s+([»’”\);:/])#u'),
                array('&bull;&nbsp;', '\1&nbsp;', '&nbsp;\1'),
                \htmlspecialchars($str, \ENT_NOQUOTES, Nls::$charset)
            ));
        }
    }

    /** html formatted email address
     * @param string $email email address
     * @param int $width    max width of displayed string (0 == unrestricted)
     * @return string
     */
    public static function asEmail($email, $width = 0)
    {
        return '<a href="mailto:'.\htmlspecialchars($email, \ENT_COMPAT, Nls::$charset).'">'.
            \htmlspecialchars(
                ($width > 0 and \strlen($email) > $width) ? \substr_replace($email, '...', $width) : $email,
                \ENT_NOQUOTES,
                Nls::$charset
            ).
            '</a>';
    }

    /** html formatted url address
     * @param string $url   url address(with or without http:// prefix)
     * @param int $width    max width of displayed string(0 == unrestricted)
     * @return string
     */
    public static function asUrl($url, $width = 0)
    {
        $href = \strpos($url, ':') && \preg_match('/^\w+:\/\//', $url) ? $url : "http://$url";

        return "<a href=\"$href\" target=\"_blank\">".
            \htmlspecialchars(
                ($width > 0 and \strlen($url) > $width) ? \substr_replace($url, '...', $width) : $url,
                \ENT_NOQUOTES,
                Nls::$charset
            ).
            '</a>';
    }

    /** html formatted telephone number (whitespace replaced with &amp;nbsp;)
     * @param string $tel   telephone
     * @return string
     */
    public static function asTel($tel)
    {
        return \str_replace(array(' ', "\t"), '&nbsp;', \htmlspecialchars($tel, \ENT_NOQUOTES, Nls::$charset));
    }

    /** integer value using specified thousands separator or Nls format if empty
     * @param int $i        value to convert
     * @param string $sep   thousands separator
     * @return string
     */
    public static function asInt($i, $sep = null)
    {
        if (!isset($sep)) {
            $sep = Nls::$formats[Nls::P_THOUSANDS_CHAR];
        }

        return $sep === ' '
            ? \str_replace(' ', '&nbsp;', \number_format($i, 0, '', ' '))
            : \number_format($i, 0, '', $sep);
    }

    /** value with thousands/decimal separators and 2 decimal places.
     * Before conversion value is divided by 100. Spaces converted to &nbsp;
     * @param int $cts          value to convert
     * @param bool $show_cents  whether to show the decimal part. if <i>null</i>
     *  passed use compact mode (decimals displayed if exist, up to 2)
     * @return string
     */
    public static function asCents($cts, $show_cents = true)
    {
        if ($show_cents === null) {
        // minimum decimal places
            $dec = $cts % 100 ? ($cts % 10 ? 2 : 1) : 0;
        } else {
        // 2 or 0 decimal places
            $dec = $show_cents ? 2 : 0;
        }

        $value = \number_format(
            $cts / 100,
            $dec,
            Nls::$formats[Nls::P_MON_DECIMAL_CHAR],
            Nls::$formats[Nls::P_MON_THOUSANDS_CHAR]
        );

        return Nls::$formats[Nls::P_MON_THOUSANDS_CHAR] === ' '
            ? \str_replace(' ', '&nbsp;', $value)
            : $value;
    }

    /** date representation as YYYY-MM-DD
     * @param string $dt    YYYY-MM-DD representation of a date (YYYY-MM-DD HH:MM:SS
     *  if $datetime set)
     * @param bool $datetime
     * @return string
     */
    public static function asDateRfc($dt, $datetime = null)
    {
        if (\strtotime($dt)) {
            return $datetime ? \str_replace(' ', 'T', $dt) : \substr($dt, 0, 10);
        } else {
            return '';
        }
    }

    /** date representation in current Nls format.
     * @param string $dt    YYYY-MM-DD representation of a date (YYYY-MM-DD HH:MM:SS
     *                      if $datetime set)
     * @param bool $datetime
     * @return string
     */
    public static function asDateNls($dt, $datetime = null)
    {
        if ($tm = \strtotime($dt)) {
            return \date(Nls::$formats[$datetime ? Nls::P_DATETIME_DT : Nls::P_DATE_DT], $tm);
        } else {
            return '';
        }
    }

    /** month / year representation in current Nls format, like 'January 2015' for '2015-01-01'.
     * @param string $date      YYYY-MM-DD or YYYY-MM representation of a date
     * @param int $param_month  Nls::P_MONTHS for "January 2015" or Nls::P_MONTHS_SHORT for "Jan '15"
     * @return string Month representation of a date
     */
    public static function asMonth($date, $param_month = Nls::P_MONTHS)
    {
        if (\preg_match('/^(\d{4})-(\d{2})\b/', $date, $m)
            and isset(Nls::$formats[$param_month][(int)$m[2]])
        ) {
            return $param_month == Nls::P_MONTHS
                ? \sprintf(
                    Nls::$formats[Nls::P_DATEMON_FMT],
                    Nls::$formats[$param_month][(int)$m[2]],
                    $m[1]
                )
                : \sprintf(
                    Nls::$formats[Nls::P_DATEMON_SHORT_FMT],
                    Nls::$formats[$param_month][(int)$m[2]],
                    $m[1] % 100
                );
        } else {
            return '';
        }
    }

    /** returns an ABBR tag with short string tooltipped with a longer description
     * @param string $short short string
     * @param string $long  longer description
     * @return string
     */
    public static function asAbbr($short, $long)
    {
        return '<abbr title="'.\htmlspecialchars($long, \ENT_COMPAT, Nls::$charset).'">'.$short.'</abbr>';
    }
}
