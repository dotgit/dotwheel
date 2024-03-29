<?php

namespace Dotwheel\Ui;

use Dotwheel\Nls\Nls;
use Dotwheel\Nls\TextTest;
use Dotwheel\Tests\Checker;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Html
 */
class HtmlTest extends TestCase
{
    use Checker;

    /**
     * @covers ::attr
     */
    public function testAttr()
    {
        $this->assertEquals(
            ' my="one &amp; &quot;only&quot;"',
            Html::attr(['my' => 'one & "only"']),
            'contains passed encoded attributes'
        );
        $this->assertEquals(
            ' two="2"',
            Html::attr(['one' => null, 'two' => '2']),
            'exclude attribute if null attribute value'
        );
    }

    /**
     * @covers ::urlArgs
     */
    public function testUrlArgs()
    {
        $this->assertEquals(
            'my=one',
            Html::urlArgs('', ['my' => 'one']),
            'base encoding'
        );
        $this->assertEquals(
            '?my=one+%3D+two',
            Html::urlArgs('?', ['my' => 'one = two']),
            'contains equal sign and spaces'
        );
        $this->assertEquals(
            '&my%5Bone%5D%5Btwo%5D=2',
            Html::urlArgs('&', ['my' => ['one' => ['two' => 2]]]),
            'contains recursive arrays'
        );
    }

    /**
     * @covers ::tableStart
     */
    public function testTableStart()
    {
        $this->assertEquals(
            '<table>',
            Html::tableStart(),
            'no arguments'
        );
        $this->assertEquals(
            '<table id="table1" class="split"><caption class="t-capt">< unencoded ></caption>',
            Html::tableStart([
                Html::P_CAPTION => '< unencoded >',
                Html::P_CAPTION_ATTR => ['class' => 't-capt'],
                'id' => 'table1',
                'class' => 'split',
            ]),
            'table and caption with arguments'
        );
        $res = Html::tableStart([
            Html::P_COLGROUP => [['width' => '80%'], ['width' => '20%', 'align' => 'right']],
        ]);
        $this->assertStringContainsString('<table id="', $res, 'table with colgroup');
        $this->assertEquals(1, preg_match('/ id="([^"]+)"/', $res, $m), 'table id was computed');
        $id = $m[1];
        foreach (array_filter(
            HtmlPage::$bin_head_style,
            function ($st) use ($id) {return strpos($st, $id) !== false;}
        ) as $style) {
            $this->assertStringContainsString('width:', $style, 'width property is set');
        }
    }

    /**
     * @covers ::tableStop
     */
    public function testTableStop()
    {
        $this->assertEquals('</table>', Html::tableStop());
    }

    /**
     * @covers ::thead
     */
    public function testThead()
    {
        $this->assertEquals('<thead>', Html::thead(), 'empty table header');

        $resPr = Html::thead([
            Html::P_PREFIX => '<prefix row>',
        ]);
        $this->assertStringContainsString('<tr><td><prefix row></td></tr>', $resPr, 'contains prefix row');

        $resTr = Html::thead([
            Html::P_PREFIX => '<prefix row>',
            Html::P_VALUES => ['c1' => 'Description', 'c2' => 'Price', 'c3' => 'Total'],
            Html::P_TD_ATTR => ['c2' => ['class' => 'price-header']],
            Html::P_TAG => 'td',
            'id' => 'header',
        ]);
        $this->assertStringContainsString('<thead>', $resTr, 'contains THEAD');
        $this->assertStringContainsString('<tr><td colspan="3"><prefix row></td></tr>', $resTr, 'contains prefix row');
        $this->assertStringContainsString('<tr id="header">', $resTr, 'TR has a correct id attribute');
        $this->assertStringContainsString('<td>Description</td>', $resTr, 'first column correct');
        $this->assertStringContainsString('<td class="price-header">Price</td>', $resTr, 'second column with correct attributes');
        $this->assertStringContainsString('<td>Total</td>', $resTr, 'third column correct');
    }

    /**
     * @covers ::colgroup
     */
    public function testColgroup()
    {
        $res = Html::colgroup([
            ['width' => '80%'],
            ['width' => '20%', 'align' => 'right']
        ]);
        $this->assertEquals(2, preg_match_all('/<col ([^>]+)>/', $res, $m), 'contains THEAD');
        $this->assertStringContainsString('width="80%"', $m[1][0], 'first column width');
        $this->assertStringContainsString('width="20%"', $m[1][1], 'second column width');
        $this->assertStringContainsString('align="right"', $m[1][1], 'second column align');
    }

    /**
     * @covers ::tr
     */
    public function testTr()
    {
        $res = Html::tr([
            Html::P_VALUES => ['c1' => 'Description', 'c2' => 'Price', 'c3' => 'Total'],
            Html::P_TD_ATTR => ['c2' => ['class' => 'price-header']],
            Html::P_TAG => 'td',
            'id' => 'header',
        ]);
        $this->assertStringContainsString('<td>Description</td>', $res, 'first column correct');
        $this->assertStringContainsString('<td class="price-header">Price</td>', $res, 'second column with correct attributes');
        $this->assertStringContainsString('<td>Total</td>', $res, 'third column correct');
        $this->assertStringContainsString('<tr id="header">', $res, 'TR has a correct id attribute');
    }

    /**
     * @covers ::input
     */
    public function testInput()
    {
        $res = Html::input([
            'id' => 'field',
        ]);
        $this->assertStringContainsString('<input ', $res, 'input tag returned');
        $this->assertStringContainsString(' id="field"', $res, 'id attr set');
        $this->assertStringContainsString(' name="field"', $res, 'name set from id');
    }

    /**
     * @covers ::inputText
     */
    public function testInputText()
    {
        $res = Html::inputText([
            'id' => 'field',
        ]);
        $this->assertStringContainsString('<input ', $res, 'input tag returned');
        $this->assertStringContainsString(' type="text"', $res, 'type attribute set to text');
    }

    /**
     * @covers ::inputTextarea
     */
    public function testInputTextarea()
    {
        $res = Html::inputTextarea([
            'id' => 'field',
        ]);
        $this->assertStringContainsString('<textarea ', $res, 'textarea tag returned');
        $this->assertStringContainsString(' id="field"', $res, 'id attribute set');
        $this->assertStringContainsString(' name="field"', $res, 'name attribute set from id');
        $this->assertStringContainsString(' rows="5"', $res, '5 rows by default');

        $res2 = Html::inputTextarea([
            'rows' => 7,
            'cols' => 20,
            'value' => "some\n<text>",
        ]);
        $this->assertStringContainsString(' rows="7"', $res2, 'rows attribute set');
        $this->assertStringContainsString(' cols="20"', $res2, 'cols attribute set');
        $this->assertStringContainsString(">some\n<text><", $res2, 'value attribute transformed to textarea content, no escaping');
    }

    /**
     * @covers ::inputInt
     */
    public function testInputInt()
    {
        $res = Html::inputInt([
            'id' => 'field',
        ]);
        $this->assertStringContainsString('<input ', $res, 'input tag returned');
        $this->assertStringContainsString(' type="number"', $res, 'type attribute set to text');

        $res2 = Html::inputInt([
            'id' => 'field',
            'type' => 'text',
            'maxlength' => 15,
        ]);
        $this->assertStringContainsString(' type="text"', $res2, 'type attribute set to number');
        $this->assertStringContainsString(' maxlength="15"', $res2, 'maxlength attribute set');
    }

    /**
     * @covers ::inputCents
     */
    public function testInputCents()
    {
        Nls::init(TextTest::DOMAIN, __DIR__ . '/../nls/locale', 'fr');
        $res = Html::inputCents([
            'id' => 'field',
            'value' => '12345',
        ]);
        $this->assertStringContainsString('<input ', $res, 'input tag returned');
        $this->assertStringContainsString(' type="number"', $res, 'type attribute set to number');
        $this->assertStringContainsString(' value="123,45"', $res, 'decimal value set correctly');

        $res2 = Html::inputCents([
            'id' => 'field',
            'type' => 'text',
            'maxlength' => 15,
        ]);
        $this->assertStringContainsString(' type="text"', $res2, 'type attribute set to text');
        $this->assertStringContainsString(' maxlength="15"', $res2, 'maxlength attribute set');
    }

    /**
     * @covers ::inputDate
     */
    public function testInputDate()
    {
        $res = Html::inputDate([
            'id' => 'field',
            'value' => '2016-12-31 23:45:01',
            Html::P_DATETIME => true,
        ]);
        $this->assertStringContainsString('<input ', $res, 'input tag returned');
        $this->assertStringContainsString(' type="datetime"', $res, 'type attribute set to datetime');
        $this->assertStringContainsString(' value="2016-12-31T23:45:01"', $res, 'date value set to RFC date');

        $res2 = Html::inputDate([
            'id' => 'field',
            'type' => 'text',
            'value' => '2016-12-31',
        ]);
        $this->assertStringContainsString(' type="text"', $res2, 'type attribute set to text');
        $this->assertStringContainsString(' value="31/12/16"', $res2, 'date value set to NLS date');
    }

    /**
     * @covers ::inputSelect
     */
    public function testInputSelect()
    {
        $res = Html::inputSelect([
            'id' => 'field',
            'value' => 'two',
            Html::P_BLANK => 'Select line',
            Html::P_ITEMS => [
                'one' => 'First <line>',
                'two' => 'Second line',
            ],
        ]);
        $this->assertStringContainsString('<select ', $res, 'select tag returned');
        $this->assertStringContainsString(' id="field"', $res, 'id attribute set');
        $this->assertStringContainsString(' name="field"', $res, 'name attribute set from id');
        $this->assertStringContainsString('<option value="">Select line</option>', $res, 'blank line with empty value');
        $this->assertStringContainsString('<option value="one">First <line></option>', $res, 'first line, unescaped');
        $this->assertStringContainsString('<option value="two" selected>Second line</option>', $res, 'second line selected');
    }

    /**
     * @covers ::inputSet
     */
    public function testInputSet()
    {
        $res = Html::inputSet([
            'name' => 'field_name',
            'value' => 'two',
            Html::P_ITEMS => [
                'one' => 'First line',
                'two' => 'Second <line>',
            ],
            Html::P_DELIM => '*',
        ]);
        $this->assertStringContainsString('</label>*<label', $res, 'has delimiter');
        $this->assertEquals(1, preg_match('/<label><input([^>]+)> Second <line><\/label>/', $res, $m), 'has second value, unescaped');
        $this->assertStringContainsString(' type="checkbox"', $m[1], 'second value type is checkbox');
        $this->assertStringContainsString(' name="field_name[two]"', $m[1], 'second value name is correct');
        $this->assertStringContainsString(' value="two"', $m[1], 'value attribute from second value is "two"');
        $this->assertStringContainsString(' checked="on"', $m[1], 'second value is checked');
    }

    /**
     * @covers ::inputRadio
     */
    public function testInputRadio()
    {
        $res = Html::inputRadio([
            'name' => 'field_name',
            'value' => 'two',
            Html::P_DELIM => '*',
            Html::P_WRAP_FMT => 'PREFIX%sSUFFIX',
            Html::P_HEADER_ATTR => [
                'class' => 'header',
            ],
            Html::P_ITEMS => [
                'one' => 'First line',
                'two' => 'Second <line>',
            ],
        ]);
        $this->assertStringStartsWith('PREFIX', $res, 'has correct prefix');
        $this->assertStringEndsWith('SUFFIX', $res, 'has correct suffix');
        $this->assertStringContainsString('SUFFIX*PREFIX', $res, 'has delimiter');
        $this->assertEquals(1, preg_match('/<label class="header"><input([^>]+)>Second <line><\/label>/', $res, $m), 'has second value, unescaped');
        $this->assertStringContainsString(' type="radio"', $m[1], 'second value type is radio');
        $this->assertStringContainsString(' name="field_name"', $m[1], 'second value name is correct');
        $this->assertStringContainsString(' value="two"', $m[1], 'value attribute from second value is "two"');
        $this->assertStringContainsString(' checked="on"', $m[1], 'second value is checked');
    }

    /**
     * @covers ::inputCheckbox
     */
    public function testInputCheckbox()
    {
        $res = Html::inputCheckbox([
            'name' => 'field_name',
            'value' => 'two',
            Html::P_DELIM => '*',
            Html::P_WRAP_FMT => 'PREFIX%sSUFFIX',
            Html::P_HEADER => '<description>',
            Html::P_HEADER_ATTR => [
                'class' => 'header',
            ],
        ]);
        $this->assertStringStartsWith('PREFIX', $res, 'has correct prefix');
        $this->assertStringEndsWith('SUFFIX', $res, 'has correct suffix');
        $this->assertStringContainsString('*<description>', $res, 'has delimiter, unescaped description');
        $this->assertEquals(1, preg_match('/<label class="header"><input([^>]+)>/', $res, $m), 'has input control, correct label attributes');
        $this->assertStringContainsString(' type="checkbox"', $m[1], 'control type is checkbox');
        $this->assertStringContainsString(' name="field_name"', $m[1], 'control name is correct');
        $this->assertStringContainsString(' value="two"', $m[1], 'control value attribute is "two"');
    }

    /**
     * @covers ::encode
     */
    public function testEncode()
    {
        $this->assertEquals("&lt;p&gt;\n&amp;\"e\"\n&amp;n's", Html::encode("<p>\n&\"e\"\n&n's"), 'encode &, < and >');
        $this->assertEquals('', Html::encode(null), 'encode &, < and >');
    }

    /**
     * @covers ::encodeAttr
     */
    public function testEncodeAttr()
    {
        $this->assertEquals("&lt;p&gt;\n&amp;&quot;e&quot;\n&amp;n's", Html::encodeAttr("<p>\n&\"e\"\n&n's"), 'encode &, ", < and >');
    }

    /**
     * @covers ::encodeNl
     */
    public function testEncodeNl()
    {
        $this->assertEquals("&lt;p&gt;<br />\n&amp;\"e\"<br />\n&amp;n's", Html::encodeNl("<p>\n&\"e\"\n&n's"), 'encode &, < and >, translate \n to <br />');
        $this->assertEquals(
            "&bull;&nbsp;line (&nbsp;1&nbsp;), ‘&nbsp;2&nbsp;’<br />\n&bull;&nbsp;line “&nbsp;3&nbsp;”, «&nbsp;4&nbsp;», 5&nbsp;: 6&nbsp;; 7&nbsp;! 8&nbsp;?",
            Html::encodeNl("* line ( 1 ), ‘ 2 ’\n* line “ 3 ”, « 4 », 5 : 6 ; 7 ! 8 ?", true),
            'encode &, < and >, translate \n to <br />, basic list formatting, opening and closing symbols with spaces'
        );
    }

    /**
     * @covers ::asEmail
     */
    public function testAsEmail()
    {
        $this->assertEquals(
            '<a href="mailto:this.is.email@domain.com">this.is.email@domain.com</a>',
            Html::asEmail('this.is.email@domain.com'),
            'mailto: protocol'
        );
        $this->assertEquals(
            '<a href="mailto:this.is.email@domain.com">this.is.em...</a>',
            Html::asEmail('this.is.email@domain.com', 10),
            'mailto: protocol, display first 10 email chars'
        );
    }

    /**
     * @covers ::asUrl
     */
    public function testAsUrl()
    {
        $this->assertEquals(
            '<a href="http://domain.com/address.php" target="_blank">domain.com/address.php</a>',
            Html::asUrl('domain.com/address.php'),
            'http: protocol'
        );
        $this->assertEquals(
            '<a href="https://domain.com/address.php" target="_blank">https://do...</a>',
            Html::asUrl('https://domain.com/address.php', 10),
            'https: protocol, display first 10 email chars'
        );
    }

    /**
     * @covers ::asTel
     */
    public function testAsTel()
    {
        $this->assertEquals(
            '01&nbsp;23&nbsp;45&nbsp;67&nbsp;89&nbsp;B&amp;B',
            Html::asTel("01 23\t45\n67\r\n89 B&B"),
            'replace whitespaces with &nbsp;'
        );
    }

    /**
     * @covers ::asInt
     */
    public function testAsInt()
    {
        $this->assertEquals(
            '123&nbsp;457',
            Html::asInt(123456.78),
            'use NLS thousands separator, rounded'
        );
        $this->assertNull(
            Html::asInt('text'),
            'non-numeric value'
        );
    }

    /**
     * @covers ::asCents
     */
    public function testAsCents()
    {
        $this->assertEquals(
            '1&nbsp;234,56',
            Html::asCents(123456),
            'use NLS thousands separator, display 2 decimals'
        );
        $this->assertEquals(
            '1&nbsp;235',
            Html::asCents(123456, false),
            'use NLS thousands separator, no decimals, rounded'
        );
        $this->assertEquals(
            '1&nbsp;234,5',
            Html::asCents(123450, null),
            'use NLS thousands separator, up to 2 decimals'
        );
        $this->assertNull(
            Html::asCents('text'),
            'non-numeric value'
        );
    }

    /**
     * @covers ::asDateRfc
     */
    public function testAsDateRfc()
    {
        $this->assertEquals('2016-12-31', Html::asDateRfc('2016-12-31'), 'MySQL to RFC date');
        $this->assertEquals('2016-12-31T01:23:45', Html::asDateRfc('2016-12-31 01:23:45', true), 'MySQL to RFC datetime');
        $this->assertEquals('', Html::asDateRfc('not-a-date'), 'not a MySQL date');
    }

    /**
     * @covers ::asDateNls
     */
    public function testAsDateNls()
    {
        $this->assertEquals('31/12/16', Html::asDateNls('2016-12-31'), 'MySQL to NLS date');
        $this->assertEquals('31/12/16 01:23', Html::asDateNls('2016-12-31 01:23:45', true), 'MySQL to NLS datetime');
        $this->assertEquals('', Html::asDateNls('not-a-date'), 'not a MySQL date');
    }

    /**
     * @covers ::asMonth
     */
    public function testAsMonth()
    {
        $this->assertEquals('Décembre 2016', Html::asMonth('2016-12-31'), 'MySQL to year-with-month form');
        $this->assertEquals("déc. '16", Html::asMonth('2016-12-31', Nls::P_MONTHS_SHORT), 'MySQL to year-with-short-month form');
        $this->assertEquals('', Html::asMonth('not-a-date'), 'not a MySQL date');
    }

    /**
     * @covers ::asAbbr
     */
    public function testAsAbbr()
    {
        $this->assertEquals(
            '<abbr title="HyperText &lt;Markup&gt; &quot;Language&quot;">[HTML]</abbr>',
            Html::asAbbr('[HTML]', 'HyperText <Markup> "Language"'),
            'short term unescaped, developed attribute escaped'
        );
    }
}
