<?php

namespace Dotwheel\Ui;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass HtmlPage
 */
class HtmlPageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        HtmlPage::add([
            HtmlPage::META => ' name="viewport" content="viewport content"',
            HtmlPage::TITLE => '<page> "title"',
            HtmlPage::META_DESCRIPTION => '<page> "description"',
            HtmlPage::LINK => ' href="favicon.ico" rel="shortcut icon" type="image/x-icon"',
            HtmlPage::BASE => ' target="_blank"',
            HtmlPage::STYLE_SRC => '"style".css',
            HtmlPage::STYLE => '.important{color:red;}',

            HtmlPage::HTML_FOOTER => [__CLASS__ => 'html footer'],
            HtmlPage::SCRIPT_SRC_INIT => [__CLASS__ => '"initial".js'],
            HtmlPage::SCRIPT_SRC => [__CLASS__ => '"another".js'],
            HtmlPage::SCRIPT => [__CLASS__ => 'var a=true;'],
            HtmlPage::DOM_READY => [__CLASS__ => 'a++;'],
            HtmlPage::SCRIPT_LAST => [__CLASS__ => 'lastCommand();'],
            HtmlPage::HTML_FOOTER_LAST => [__CLASS__ => 'html last footer'],
        ]);
    }

    /**
     * @covers ::add
     */
    public function testAdd()
    {
        $this->assertContains('<meta name="viewport" content="viewport content">', HtmlPage::$bin_head);
        $this->assertContains('<title>&lt;page&gt; &quot;title&quot;</title>', HtmlPage::$bin_head);
        $this->assertContains('<meta name="description" content="&lt;page&gt; &quot;description&quot;">', HtmlPage::$bin_head);
        $this->assertContains('<link href="favicon.ico" rel="shortcut icon" type="image/x-icon">', HtmlPage::$bin_head);
        $this->assertContains('<base target="_blank">', HtmlPage::$bin_head);
        $this->assertContains('<link rel="stylesheet" href="&quot;style&quot;.css">', HtmlPage::$bin_head_style_src);
        $this->assertContains('.important{color:red;}', HtmlPage::$bin_head_style);

        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_html_footer);
        $this->assertEquals('html footer', HtmlPage::$bin_html_footer[__CLASS__]);
        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_script_src_init);
        $this->assertEquals('<script src="&quot;initial&quot;.js"></script>', HtmlPage::$bin_script_src_init[__CLASS__]);
        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_script_src);
        $this->assertEquals('<script src="&quot;another&quot;.js"></script>', HtmlPage::$bin_script_src[__CLASS__]);
        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_script);
        $this->assertEquals('var a=true;', HtmlPage::$bin_script[__CLASS__]);
        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_dom_ready);
        $this->assertEquals('a++;', HtmlPage::$bin_dom_ready[__CLASS__]);
        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_script_last);
        $this->assertEquals('lastCommand();', HtmlPage::$bin_script_last[__CLASS__]);
        $this->assertArrayHasKey(__CLASS__, HtmlPage::$bin_html_footer_last);
        $this->assertEquals('html last footer', HtmlPage::$bin_html_footer_last[__CLASS__]);
    }

    /**
     * @covers ::getHead
     */
    public function testGetHead()
    {
        $res = HtmlPage::getHead([
            HtmlPage::META => ' charset="utf-8"',
            HtmlPage::TITLE => '<page> "overwrite" title',
            HtmlPage::META_DESCRIPTION => null,
        ]);

        $this->assertStringContainsString('<meta name="viewport" content="viewport content">', $res, 'initial meta in place');
        $this->assertStringContainsString('<meta charset="utf-8">', $res, 'another meta added');
        $this->assertLessThan(
            strpos($res, '<meta charset="utf-8">'),
            strpos($res, '<meta name="viewport"'),
            'original header before subsequent header'
        );
        $this->assertStringContainsString('<title>&lt;page&gt; &quot;overwrite&quot; title</title>', $res, 'second title overwrites');
        $this->assertLessThan(
            strpos($res, '<title>&lt;page&gt;'),
            strpos($res, '<meta name="viewport"'),
            'viewport meta before title'
        );
        $this->assertStringNotContainsString('<meta name="description"', $res, 'second description overwrites or deletes');
        $this->assertStringContainsString('<link href="favicon.ico" rel="shortcut icon" type="image/x-icon">', $res);
        $this->assertLessThan(
            strpos($res, '<link href="favicon.ico"'),
            strpos($res, '<title>&lt;page&gt;'),
            'title before favicon'
        );
        $this->assertStringContainsString('<base target="_blank">', $res);
        $this->assertLessThan(
            strpos($res, '<base target="_blank">'),
            strpos($res, '<link href="favicon.ico"'),
            'favicon before base'
        );
        $this->assertLessThan(
            strpos($res, '<meta charset="utf-8">'),
            strpos($res, '<base target="_blank">'),
            'base before charset'
        );
        $this->assertStringContainsString('<link rel="stylesheet" href="&quot;style&quot;.css">', $res);
        $this->assertLessThan(
            strpos($res, '<link rel="stylesheet"'),
            strpos($res, '<meta charset="utf-8">'),
            'charset before stylesheet'
        );
        $this->assertStringContainsString('<style>.important{color:red;}</style>', $res);
        $this->assertLessThan(
            strpos($res, '.important{color:red;}'),
            strpos($res, '<link rel="stylesheet"'),
            'stylesheet before styles'
        );
    }

    /**
     * @covers ::getTail
     */
    public function testGetTail()
    {
        $res = HtmlPage::getTail([
            HtmlPage::DOM_READY => 'anotherCode();',
            HtmlPage::HTML_FOOTER => [
                'html another footer',
                __CLASS__ => 'html duplicate footer',
            ],
        ]);

        // html footer
        $this->assertStringContainsString('html footer', $res, 'original footer present');
        $this->assertStringContainsString('html another footer', $res, 'another footer appended');
        $this->assertLessThan(
            strpos($res, 'html another footer'),
            strpos($res, 'html footer'),
            'original footer before subsequent footer'
        );
        $this->assertStringNotContainsString('html duplicate footer', $res, 'other duplicates do not overwrite');
        $this->assertStringContainsString('<script src="&quot;initial&quot;.js"></script>', $res, '"initial".js present');
        $this->assertLessThan(
            strpos($res, '&quot;initial&quot;.js'),
            strpos($res, 'html another footer'),
            'html footer before initial js file'
        );
        $this->assertStringContainsString('<script src="&quot;another&quot;.js"></script>', $res, '"another".js present');
        $this->assertLessThan(
            strpos($res, '&quot;another&quot;.js'),
            strpos($res, '&quot;initial&quot;.js'),
            'initial js file before subsequent js file'
        );

        // js block
        $this->assertEquals(1, preg_match('/<script>(.+)<\/script>/s', $res, $m), 'js block created');
        $this->assertLessThan(
            strpos($res, '<script>'),
            strpos($res, '&quot;another&quot;.js'),
            'js files before script block'
        );
        $js = $m[1];
        $this->assertStringContainsString('var a=true;', $js, 'js code present');
        // DOM ready block
        $this->assertEquals(
            1,
            preg_match('/document.addEventListener\("DOMContentLoaded",function\(event\)\{(.+)}\);/s', $js, $n),
            'DOM ready block created'
        );
        $this->assertLessThan(
            strpos($res, 'document.addEventListener("DOMContentLoaded"'),
            strpos($res, 'var a=true;'),
            'js code before DOM ready block'
        );
        $dr = $n[1];
        $this->assertStringContainsString('a++;', $dr, 'initial DOM ready code in place');
        $this->assertStringContainsString('anotherCode();', $dr, 'additional DOM ready code in place');
        $this->assertLessThan(
            strpos($dr, 'anotherCode();'),
            strpos($dr, 'a++;'),
            'initial code before subsequent code in DOM ready block'
        );
        // js block continued
        $this->assertStringContainsString('lastCommand();', $js, 'last js code present');
        $this->assertLessThan(
            strpos($res, 'lastCommand();'),
            strpos($res, 'document.addEventListener("DOMContentLoaded"'),
            'DOM ready block before last js code'
        );

        // last html footer
        $this->assertStringContainsString('html last footer', $res, 'last footer appended');
        $this->assertLessThan(
            strrpos($res, 'html last footer'),
            strpos($res, '</script>'),
            'last script block before html last footer'
        );
    }
}
