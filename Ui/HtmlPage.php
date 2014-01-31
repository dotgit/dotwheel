<?php

/**
 * handles html page setup and layout allowing the output of blocking page
 * rendering elements at the bottom of the html page
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Ui;

class HtmlPage
{
    /** page headings */
    const META              = 1;
    const TITLE             = 2;
    const META_DESCRIPTION  = 3;
    const LINK              = 4;
    const BASE              = 5;
    const STYLE_SRC         = 6;
    const STYLE             = 7;
    /** page footers */
    const HTML_FOOTER       = 10;
    const SCRIPT_SRC_INIT   = 11;
    const SCRIPT_SRC        = 12;
    const SCRIPT            = 13;
    const DOM_READY         = 14;
    const SCRIPT_LAST       = 15;
    const HTML_FOOTER_LAST  = 16;

    /** page headers */
    public static $bin_meta = array();
    public static $bin_style_src = array();
    public static $bin_style = array();

    /** page footers */
    public static $bin_html_footer = array();
    public static $bin_script_src_init = array();
    public static $bin_script_src = array();
    public static $bin_script = array();
    public static $bin_script_last = array();
    public static $bin_dom_ready = array();
    public static $bin_html_footer_last = array();



    /** parses page meta data and stores to the appropriate bin
     * @param array $baskets    {TITLE:'', SCRIPT:{'initial':'var MSG={};'},...}
     */
    public static function add(array $baskets)
    {
        foreach ($baskets as $basket=>$items)
            switch ($basket)
            {
            case self::HTML_FOOTER:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_html_footer[] = \trim($v);
                        elseif (! isset(self::$bin_html_footer[$k]))
                            self::$bin_html_footer[$k] = \trim($v);
                    }
                else
                    self::$bin_html_footer[] = \trim($items);
                break;
            case self::HTML_FOOTER_LAST:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_html_footer_last[] = \trim($v);
                        elseif (! isset(self::$bin_html_footer_last[$k]))
                            self::$bin_html_footer_last[$k] = \trim($v);
                    }
                else
                    self::$bin_html_footer_last[] = \trim($items);
                break;
            case self::DOM_READY:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_dom_ready[] = \trim($v);
                        elseif (! isset(self::$bin_dom_ready[$k]))
                            self::$bin_dom_ready[$k] = \trim($v);
                    }
                else
                    self::$bin_dom_ready[] = \trim($items);
                break;
            case self::SCRIPT:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_script[] = \trim($v);
                        elseif (! isset(self::$bin_script[$k]))
                            self::$bin_script[$k] = \trim($v);
                    }
                else
                    self::$bin_script[] = \trim($items);
                break;
            case self::SCRIPT_SRC_INIT:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_script_src_init[] = "<script type=\"text/javascript\" src=\"$v\"></script>";
                        elseif (! isset(self::$bin_script_src_init[$k]))
                            self::$bin_script_src_init[$k] = "<script type=\"text/javascript\" src=\"$v\"></script>";
                    }
                else
                    self::$bin_script_src_init[] = "<script type=\"text/javascript\" src=\"$items\"></script>";
                break;
            case self::SCRIPT_SRC:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_script_src[] = "<script type=\"text/javascript\" src=\"$v\"></script>";
                        elseif (! isset(self::$bin_script_src[$k]))
                            self::$bin_script_src[$k] = "<script type=\"text/javascript\" src=\"$v\"></script>";
                    }
                else
                    self::$bin_script_src[] = "<script type=\"text/javascript\" src=\"$items\"></script>";
                break;
            case self::SCRIPT_LAST:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_script_last[] = \trim($v);
                        elseif (! isset(self::$bin_script_last[$k]))
                            self::$bin_script_last[$k] = \trim($v);
                    }
                else
                    self::$bin_script_last[] = \trim($items);
                break;
            case self::STYLE_SRC:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_style_src[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$v\">";
                        elseif (! isset(self::$bin_style_src[$k]))
                            self::$bin_style_src[$k] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$v\">";
                    }
                else
                    self::$bin_style_src[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$items\">";
                break;
            case self::STYLE:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_style[] = \trim($v);
                        elseif (! isset(self::$bin_style[$k]))
                            self::$bin_style[$k] = \trim($v);
                    }
                else
                    self::$bin_style[] = \trim($items);
                break;
            case self::TITLE:
                self::$bin_meta[self::TITLE] = "<title>$items</title>";
                break;
            case self::META_DESCRIPTION:
                self::$bin_meta[self::META_DESCRIPTION] = '<meta name="description" content="'.Html::encode($items).'">';
                break;
            case self::META:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_meta[] = "<meta$v>";
                        elseif (! isset(self::$bin_meta[$k]))
                            self::$bin_meta[$k] = "<meta$v>";
                    }
                else
                    self::$bin_meta[] = "<meta$items>";
                break;
            case self::LINK:
                if (\is_array($items))
                    foreach ($items as $k=>$v)
                    {
                        if (is_int($k))
                            self::$bin_meta[] = "<link$v>";
                        elseif (! isset(self::$bin_meta[$k]))
                            self::$bin_meta[$k] = "<link$v>";
                    }
                else
                    self::$bin_meta[] = "<link$items>";
                break;
            case self::BASE:
                self::$bin_meta[self::BASE] = "<base$items>";
                break;
            }
    }

    /** start the page
     * @param array $params additional params to pass into self::add()
     * @return string       html head contents (styles, title, etc.)
     */
    public static function getHead(array $params=null)
    {
        if ($params)
            self::add($params);

        $ret = '';
        if (self::$bin_meta)
            $ret .= \implode("\n", self::$bin_meta)."\n";
        if (self::$bin_style_src)
            $ret .= \implode("\n", self::$bin_style_src)."\n";
        if (self::$bin_style)
            $ret .= "<style type=\"text/css\">\n".\implode("\n", self::$bin_style)."\n</style>\n";

        return $ret;
    }

    /** close the page
     * @param array $params additional params to pass into self::add()
     * @return string       html page trailing contents (scripts)
     */
    public static function getTail(array $params=null)
    {
        if ($params)
            self::add($params);

        $ret = '';
        if (self::$bin_html_footer)
            $ret .= \implode("\n", self::$bin_html_footer)."\n";
        if (self::$bin_script_src_init)
            $ret .= \implode("\n", self::$bin_script_src_init)."\n";
        if (self::$bin_script_src)
            $ret .= \implode("\n", self::$bin_script_src)."\n";
        if (self::$bin_script or self::$bin_dom_ready or self::$bin_script_last)
        {
            $ret .= "<script type=\"text/javascript\">\n";
            if (self::$bin_script)
                $ret .= \implode("\n", self::$bin_script)."\n";
            if (self::$bin_dom_ready)
                $ret .= "jQuery(document).ready(function(){\n".\implode("\n", self::$bin_dom_ready)."\n});\n";
            if (self::$bin_script_last)
                $ret .= \implode("\n", self::$bin_script_last)."\n";
            $ret .= "</script>\n";
        }
        if (self::$bin_html_footer_last)
            $ret .= \implode("\n", self::$bin_html_footer_last)."\n";

        return $ret;
    }
}
