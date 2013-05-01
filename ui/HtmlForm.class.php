<?php

/**
handles html form display, list of required fields etc.

[type: library]

@author stas trefilov
*/

namespace dotwheel\ui;

require_once (__DIR__.'/HtmlPage.class.php');
require_once (__DIR__.'/Html.class.php');
require_once (__DIR__.'/Ui.class.php');
require_once (__DIR__.'/../db/Repo.class.php');
require_once (__DIR__.'/../util/Misc.class.php');
require_once (__DIR__.'/../util/Params.class.php');

use dotwheel\db\Repo;
use dotwheel\util\Misc;
use dotwheel\util\Params;

class HtmlForm
{
    const P_SETS    = 1;
    const P_VALUES  = 2;
    const P_HIDDEN  = 3;

    const SET_LEGEND            = -1;
    const SET_LEGEND_CONTENT    = -11;

    const FIELD_MODE        = 1;
    const FIELD_LABEL       = 2;
    const FIELD_CONTENT     = 3;
    const FIELD_WIDTH       = 4;
    const FIELD_COMMENT     = 5;
    const FIELD_REQUIRED    = 6;
    const FIELD_INPUT       = 7;
    const FIELD_REPOSITORY  = 8;
    const FIELD_FMT         = 9;
    const FIELD_NAME        = 10;
    const FIELD_VALUE       = 11;
    const FIELD_WITH_NEXT   = 12;

    const MODE_EDIT = 1;

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

    static protected $counter = 0;

    /**
     * @param array $params {P_HIDDEN:{hidden inputs}
     *                      , P_VALUES:{field_name1:'value',...}
     *                      , P_SETS:[{SET_LEGEND:'legend'|{SET_LEGEND_CONTENT:'legend', legend tag attributes}
     *                          , 0:{field_name1:{FIELD_MODE:...
     *                                  , FIELD_REQUIRED:...
     *                                  , FIELD_LABEL:...
     *                                  , FIELD_COMMENT:'comment'
     *                                  , FIELD_INPUT:{input tag attributes}
     *                                  , FIELD_REPOSITORY:{repository params for unregistered field}
     *                                  , FIELD_MARGIN:true
     *                                  , FIELD_WIDTH:WIDTH_1_2
     *
     *                                  , d.t.a. (for field)
     *                                  }
     *                              , 0:'field content string', ...
     *                              , div tag attributes(for row)
     *                              }
     *                          , 1:{0:{FIELD_REQUIRED:...
     *                                  , FIELD_LABEL:...
     *                                  , FIELD_CONTENT:...
     *                                  , d.t.a. (for field)
     *                                  }
     *                              , ...
     *                              , d.t.a. (for row)
     *                              }
     *                          , 2:'just a string'
     *                          , ...
     *                          , fieldset tag attributes
     *                          }
     *                          ]
     *                      , form tag attributes
     *                      }
     * @return string       html representation of a form
     */
    public static function getStacked($params)
    {
        $values = Params::extract($params, self::P_VALUES, array());
        $hidden = Params::extract($params, self::P_HIDDEN, array());
        $upload = null;
        $sets = array();

        foreach (Params::extract($params, self::P_SETS, array()) as $set)
        {
            if (is_array($set))
            {
                // fieldset legend
                if (isset($set[self::SET_LEGEND]))
                {
                    if (is_array($set[self::SET_LEGEND]))
                    {
                        $legend = $set[self::SET_LEGEND][self::SET_LEGEND_CONTENT];
                        unset($set[self::SET_LEGEND][self::SET_LEGEND_CONTENT]);
                        $legend_attr = $set[self::SET_LEGEND];
                    }
                    else
                    {
                        $legend = $set[self::SET_LEGEND];
                        $legend_attr = array();
                    }
                    $legend = "<legend".Html::attr($legend_attr).">$legend</legend>";
                    unset($set[self::SET_LEGEND]);
                }
                else
                    $legend = null;

                // fieldset rows
                $rows = array();
                foreach ($set as $rkey=>$row)
                {
                    $columns = array();
                    // row is an array of fields
                    if (is_array($row))
                    {
                        $with_next_buffer = null;
                        foreach ($row as $fld=>$f)
                        {
                            // $f is an array of field params
                            if (is_array($f))
                            {
                                if (is_int($fld))
                                    $fld = null;

                                // field edit mode
                                $fedit = (Params::extract($f, self::FIELD_MODE) == self::MODE_EDIT);
                                $value = (isset($values[$fld])) ? $values[$fld] : null;

                                $content = $fedit
                                    ? self::htmlFieldEdit($f + array(self::FIELD_NAME=>$fld, self::FIELD_VALUE=>$value))
                                    : self::htmlFieldView($f + array(self::FIELD_NAME=>$fld, self::FIELD_VALUE=>$value))
                                    ;
                                $width = Params::extract($f, self::FIELD_WIDTH);

                                if (Params::extract($f, self::FIELD_WITH_NEXT))
                                {
                                    if ($with_next_buffer)
                                        $with_next_buffer[Ui::P_CONTENT] .= $content;
                                    else
                                        $with_next_buffer = isset($width)
                                            ? array(Ui::P_WIDTH=>$width, Ui::P_CONTENT=>$content)
                                            : array(Ui::P_CONTENT=>$content)
                                            ;
                                }
                                elseif ($with_next_buffer)
                                {
                                    $with_next_buffer[Ui::P_CONTENT] .= $content;
                                    $columns[] = $with_next_buffer;
                                    $with_next_buffer = null;
                                }
                                else
                                    $columns[] = isset($width)
                                        ? array(Ui::P_WIDTH=>$width, Ui::P_CONTENT=>$content, 'class'=>'control-group')
                                        : array(Ui::P_CONTENT=>$content, 'class'=>'control-group')
                                        ;
                                if ($fedit and Repo::getParam($fld, Repo::P_CLASS) == Repo::C_FILE)
                                    $upload = true;
                                unset($row[$fld]);
                            }
                            // $f is a content string
                            elseif (is_int($fld))
                            {
                                $columns[] = array(Ui::P_WIDTH=>Ui::WIDTH_1, Ui::P_CONTENT=>$f);
                                unset($row[$fld]);
                            }
                            // otherwise $f is a row attribute
                        }
                        $rargs = $row;
                        unset($set[$rkey]);
                    }
                    // $row is a content string
                    elseif (is_int($rkey))
                    {
                        $columns[] = $row;
                        $rargs = array();
                        unset($set[$rkey]);
                    }
                    // $row is a fieldset tag attribute
                    else
                        continue;

                    $rows[] = Ui::gridRow($columns + $rargs);
                }
                $sets[] = sprintf("<fieldset%s>%s%s</fieldset>\n"
                    , Html::attr($set)
                    , $legend
                    , implode("\n", $rows)
                    );
            }
            elseif ($set)
                $sets[] = $set;
        }

        if ($params || $hidden)
        {
            if ($upload)
            {
                $params['method'] = 'post';
                $params['enctype'] = 'multipart/form-data';
                $hidden['MAX_FILE_SIZE'] = Misc::getMaxUploadSize();
            }

            $pre = Html::formStart(array(Html::P_HIDDEN=>$hidden) + $params);
            $post = Html::formStop();
        }
        else
        {
            $pre = '';
            $post = '';
        }

        return $pre . implode('', $sets) . $post;
    }

    /** html presentation of a field
     * @param array $params {FIELD_NAME:'birthday'
     *                      , FIELD_LABEL:'Contact birthday'
     *                      , FIELD_COMMENT:...
     *                      , FIELD_VALUE:'1972-09-22'
     *                      , FIELD_REPOSITORY:repository field attributes
     *                      , FIELD_FMT:'%s', FIELD_CONTENT:...
     *                      }
     * @return string       field label and value
     */
    public static function htmlFieldView($params)
    {
        $field = isset($params[self::FIELD_NAME]) ? $params[self::FIELD_NAME] : null;
        $label = isset($params[self::FIELD_LABEL]) ? $params[self::FIELD_LABEL] : Repo::getLabel($field);
        $label_str = ! empty($label)
            ? "<label>$label</label>"
            : ''
            ;
        if ($comment = Params::extract($params, self::FIELD_COMMENT))
            $comment = sprintf(Ui::FORM_COMMENT_BLOCK_FMT, $comment);
        $fmt = Params::extract($params, self::FIELD_FMT);
        $content = (isset($params[self::FIELD_CONTENT])
            ? $params[self::FIELD_CONTENT]
            : Repo::asHtml($field
                , isset($params[self::FIELD_VALUE]) ? $params[self::FIELD_VALUE] : null
                , isset($params[self::FIELD_REPOSITORY]) ? ($params[self::FIELD_REPOSITORY] + Repo::get($field)) : Repo::get($field)
                )
            ) . $comment
            ;

        return $fmt ? sprintf($fmt, "$label_str$content") : "$label_str$content";
    }

    /** html presentation of a field in input form
     * @param array $params {FIELD_NAME:'birthday'
     *                      , FIELD_LABEL:'Contact birthday'
     *                      , FIELD_COMMENT:...
     *                      , FIELD_VALUE:'1972-09-22'
     *                      , FIELD_REQUIRED:bool
     *                      , FIELD_REPOSITORY:repository field attributes
     *                      , FIELD_INPUT:input tag attributes
     *                      , FIELD_FMT:'%s'
     *                      }
     * @return string       field label and input element
     */
    public static function htmlFieldEdit($params)
    {
        $field = isset($params[self::FIELD_NAME]) ? $params[self::FIELD_NAME] : ('_'.self::$counter);
        if (! isset($params[self::FIELD_INPUT]))
            $params[self::FIELD_INPUT] = array();
        if (isset($params[self::FIELD_INPUT]['id']))
            $label_attr = array('for'=>$params[self::FIELD_INPUT]['id']);
        else
        {
            ++self::$counter;
            $label_attr = array('for'=>$field.'_id'.self::$counter);
            if (isset($params[self::FIELD_INPUT]))
                $params[self::FIELD_INPUT]['id'] = "{$field}_id".self::$counter;
            else
                $params[self::FIELD_INPUT] = array('id'=>"{$field}_id".self::$counter);
        }
        $params[self::FIELD_INPUT] = Ui::width2attr('100%', $params[self::FIELD_INPUT]);
        if (! empty($params[self::FIELD_REQUIRED]))
            Params::add($params[self::FIELD_INPUT], 'required', 'required');
        $label = isset($params[self::FIELD_LABEL]) ? $params[self::FIELD_LABEL] : Repo::getLabel($field);
        $label_str = ! empty($label)
            ? ("<label".Html::attr($label_attr).">$label</label>")
            : ''
            ;
        if ($comment = Params::extract($params, self::FIELD_COMMENT))
            $comment = sprintf(Ui::FORM_COMMENT_BLOCK_FMT, $comment);
        $fmt = Params::extract($params, self::FIELD_FMT);
        $content = Repo::asHtmlInput($field
                , isset($params[self::FIELD_VALUE]) ? $params[self::FIELD_VALUE] : null
                , $params[self::FIELD_INPUT]
                , isset($params[self::FIELD_REPOSITORY]) ? $params[self::FIELD_REPOSITORY] + Repo::get($field) : Repo::get($field)
                )
            . $comment
            ;

        return $fmt ? sprintf($fmt, "$label_str$content") : "$label_str$content";
    }
}
