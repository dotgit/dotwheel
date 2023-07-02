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
    public const META = 1;
    public const TITLE = 2;
    public const META_DESCRIPTION = 3;
    public const LINK = 4;
    public const BASE = 5;
    public const STYLE_SRC = 6;
    public const STYLE = 7;

    /** page footers */
    public const HTML_FOOTER = 10;
    public const SCRIPT_SRC_INIT = 11;
    public const SCRIPT_SRC = 12;
    public const SCRIPT = 13;
    public const DOM_READY = 14;
    public const SCRIPT_LAST = 15;
    public const HTML_FOOTER_LAST = 16;

    /** page headers */
    public static $bin_head = [];
    public static $bin_head_style_src = [];
    public static $bin_head_style = [];

    /** page footers */
    public static $bin_html_footer = [];
    public static $bin_script_src_init = [];
    public static $bin_script_src = [];
    public static $bin_script = [];
    public static $bin_dom_ready = [];
    public static $bin_script_last = [];
    public static $bin_html_footer_last = [];


    /** parse page meta data and store to the appropriate bin
     *
     * @param array $baskets {TITLE:'', SCRIPT:{'initial':'var MSG={};'},...}
     */
    public static function add(array $baskets)
    {
        foreach ($baskets as $basket => $items) {
            switch ($basket) {
                case self::HTML_FOOTER:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_html_footer[] = trim($v);
                            } elseif (!isset(self::$bin_html_footer[$k])) {
                                self::$bin_html_footer[$k] = trim($v);
                            }
                        }
                    } else {
                        self::$bin_html_footer[] = trim($items);
                    }
                    break;
                case self::HTML_FOOTER_LAST:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_html_footer_last[] = trim($v);
                            } elseif (!isset(self::$bin_html_footer_last[$k])) {
                                self::$bin_html_footer_last[$k] = trim($v);
                            }
                        }
                    } else {
                        self::$bin_html_footer_last[] = trim($items);
                    }
                    break;
                case self::DOM_READY:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_dom_ready[] = trim($v);
                            } elseif (!isset(self::$bin_dom_ready[$k])) {
                                self::$bin_dom_ready[$k] = trim($v);
                            }
                        }
                    } else {
                        self::$bin_dom_ready[] = trim($items);
                    }
                    break;
                case self::SCRIPT:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_script[] = trim($v);
                            } elseif (!isset(self::$bin_script[$k])) {
                                self::$bin_script[$k] = trim($v);
                            }
                        }
                    } else {
                        self::$bin_script[] = trim($items);
                    }
                    break;
                case self::SCRIPT_SRC_INIT:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            $attr = Html::encodeAttr($v);
                            if (is_int($k)) {
                                self::$bin_script_src_init[] = "<script src=\"$attr\"></script>";
                            } elseif (!isset(self::$bin_script_src_init[$k])) {
                                self::$bin_script_src_init[$k] = "<script src=\"$attr\"></script>";
                            }
                        }
                    } else {
                        $attr = Html::encodeAttr($items);
                        self::$bin_script_src_init[] = "<script src=\"$attr\"></script>";
                    }
                    break;
                case self::SCRIPT_SRC:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            $attr = Html::encodeAttr($v);
                            if (is_int($k)) {
                                self::$bin_script_src[] = "<script src=\"$attr\"></script>";
                            } elseif (!isset(self::$bin_script_src[$k])) {
                                self::$bin_script_src[$k] = "<script src=\"$attr\"></script>";
                            }
                        }
                    } else {
                        $attr = Html::encodeAttr($items);
                        self::$bin_script_src[] = "<script src=\"$attr\"></script>";
                    }
                    break;
                case self::SCRIPT_LAST:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_script_last[] = trim($v);
                            } elseif (!isset(self::$bin_script_last[$k])) {
                                self::$bin_script_last[$k] = trim($v);
                            }
                        }
                    } else {
                        self::$bin_script_last[] = trim($items);
                    }
                    break;
                case self::STYLE_SRC:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            $attr = Html::encodeAttr($v);
                            if (is_int($k)) {
                                self::$bin_head_style_src[] = "<link rel=\"stylesheet\" href=\"$attr\">";
                            } elseif (!isset(self::$bin_head_style_src[$k])) {
                                self::$bin_head_style_src[$k] = "<link rel=\"stylesheet\" href=\"$attr\">";
                            }
                        }
                    } else {
                        $attr = Html::encodeAttr($items);
                        self::$bin_head_style_src[] = "<link rel=\"stylesheet\" href=\"$attr\">";
                    }
                    break;
                case self::STYLE:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_head_style[] = trim($v);
                            } elseif (!isset(self::$bin_head_style[$k])) {
                                self::$bin_head_style[$k] = trim($v);
                            }
                        }
                    } else {
                        self::$bin_head_style[] = trim($items);
                    }
                    break;
                case self::TITLE:
                    if (isset($items)) {
                        self::$bin_head[__METHOD__ . '-' . self::TITLE] = '<title>' .
                            Html::encodeAttr($items) .
                            '</title>';
                    } else {
                        unset(self::$bin_head[__METHOD__ . '-' . self::TITLE]);
                    }
                    break;
                case self::META_DESCRIPTION:
                    if (isset($items)) {
                        self::$bin_head[__METHOD__ . '-' . self::META_DESCRIPTION] = '<meta name="description" ' .
                            'content="' .
                            Html::encodeAttr($items) .
                            '">';
                    } else {
                        unset(self::$bin_head[__METHOD__ . '-' . self::META_DESCRIPTION]);
                    }
                    break;
                case self::META:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_head[] = "<meta$v>";
                            } elseif (!isset(self::$bin_head[$k])) {
                                self::$bin_head[$k] = "<meta$v>";
                            }
                        }
                    } else {
                        self::$bin_head[] = "<meta$items>";
                    }
                    break;
                case self::LINK:
                    if (is_array($items)) {
                        foreach ($items as $k => $v) {
                            if (is_int($k)) {
                                self::$bin_head[] = "<link$v>";
                            } elseif (!isset(self::$bin_head[$k])) {
                                self::$bin_head[$k] = "<link$v>";
                            }
                        }
                    } else {
                        self::$bin_head[] = "<link$items>";
                    }
                    break;
                case self::BASE:
                    if (isset($items)) {
                        self::$bin_head[__METHOD__ . '-' . self::BASE] = "<base$items>";
                    } else {
                        unset(self::$bin_head[__METHOD__ . '-' . self::BASE]);
                    }
                    break;
            }
        }
    }

    /** start the page
     *
     * @param ?array $params additional params to pass into self::add()
     * @return string       html head contents (styles, title, etc.)
     */
    public static function getHead(?array $params = null): string
    {
        if ($params) {
            self::add($params);
        }

        $parts = [];
        if (self::$bin_head) {
            $parts[] = implode("\n", self::$bin_head);
        }
        if (self::$bin_head_style_src) {
            $parts[] = implode("\n", self::$bin_head_style_src);
        }
        if (self::$bin_head_style) {
            $parts[] = '<style>' . implode("\n", self::$bin_head_style) . '</style>';
        }

        return implode("\n", $parts);
    }

    /** close the page
     *
     * @param ?array $params additional params to pass into self::add()
     * @return string       html page trailing contents (scripts)
     */
    public static function getTail(?array $params = null): ?string
    {
        if ($params) {
            self::add($params);
        }

        $parts = [];
        if (self::$bin_html_footer) {
            $parts[] = implode("\n", self::$bin_html_footer);
        }
        if (self::$bin_script_src_init) {
            $parts[] = implode("\n", self::$bin_script_src_init);
        }
        if (self::$bin_script_src) {
            $parts[] = implode("\n", self::$bin_script_src);
        }
        if (self::$bin_script or self::$bin_dom_ready or self::$bin_script_last) {
            $parts[] = '<script>';
            if (self::$bin_script) {
                $parts[] = implode("\n", self::$bin_script);
            }
            if (self::$bin_dom_ready) {
                $parts[] = "document.addEventListener(\"DOMContentLoaded\",function(event){\n" .
                    implode("\n", self::$bin_dom_ready) .
                    "\n});";
            }
            if (self::$bin_script_last) {
                $parts[] = implode("\n", self::$bin_script_last);
            }
            $parts[] = '</script>';
        }
        if (self::$bin_html_footer_last) {
            $parts[] = implode("\n", self::$bin_html_footer_last);
        }

        return $parts
            ? "\n" . implode("\n", $parts)
            : null;
    }
}
