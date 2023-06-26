<?php

namespace Dotwheel\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Misc
 */
class MiscTest extends TestCase
{
    /**
     * Generated from @assert ('2013-01-01', 1) == '2013-02-01'.
     * Generated from @assert ('2013-01-31', 1) == '2013-02-28'.
     * Generated from @assert ('2013-02-28', 1) == '2013-03-28'.
     * Generated from @assert ('2013-02-28', 1, '2013-01-31') == '2013-03-31'.
     * Generated from @assert ('2013-02-28', 1, '2013-01-29') == '2013-03-29'.
     * Generated from @assert ('2013-02-28', 2, '2013-01-29') == '2013-04-29'.
     *
     * @covers ::addMonths
     */
    public function testAddMonths()
    {
        $this->assertEquals('2013-02-01', Misc::addMonths('2013-01-01', 1));
        $this->assertEquals('2013-02-28', Misc::addMonths('2013-01-31', 1));
        $this->assertEquals('2013-03-28', Misc::addMonths('2013-02-28', 1));
        $this->assertEquals('2013-03-31', Misc::addMonths('2013-02-28', 1, '2013-01-31'));
        $this->assertEquals('2013-03-29', Misc::addMonths('2013-02-28', 1, '2013-01-29'));
        $this->assertEquals('2013-04-29', Misc::addMonths('2013-02-28', 2, '2013-01-29'));
    }

    /**
     * Generated from @assert ('15') == 15.
     * Generated from @assert ('1K') == 1024.
     * Generated from @assert ('2k') == 2048.
     * Generated from @assert ('1M') == 1048576.
     * Generated from @assert ('1g') == 1073741824.
     *
     * @covers ::convertSize
     */
    public function testConvertSize()
    {
        $this->assertEquals(15, Misc::convertSize('15'));
        $this->assertEquals(1024, Misc::convertSize('1K'));
        $this->assertEquals(2048, Misc::convertSize('2k'));
        $this->assertEquals(1048576, Misc::convertSize('1M'));
        $this->assertEquals(1073741824, Misc::convertSize('1g'));
    }

    /**
     * Generated from @assert ('2013-01-01', '2013-01-01') == 1.
     * Generated from @assert ('2013-01-01', '2013-01-31') == 31.
     * Generated from @assert ('2013-01-01', '2013-12-31') == 365.
     *
     * @covers ::daysInPeriod
     */
    public function testDaysInPeriod()
    {
        $this->assertEquals(1, Misc::daysInPeriod('2013-01-01', '2013-01-01'));
        $this->assertEquals(31, Misc::daysInPeriod('2013-01-01', '2013-01-31'));
        $this->assertEquals(365, Misc::daysInPeriod('2013-01-01', '2013-12-31'));
    }

    /**
     * @covers ::rgbaToInt
     * @dataProvider rgbaToIntProvider
     */
    public function testRgbaToInt($expected, $color)
    {
        $this->assertEquals($expected, Misc::rgbaToInt($color));
    }

    public function rgbaToIntProvider(): array
    {
        return [
            [0xff000000, '#000000'],
            [0xff0000ff, '#0000ff'],
            [0xff00ff00, '#00ff00'],
            [0xffff0000, '#ff0000'],
            [0xffffffff, '#ffffff'],
            [0xff0000ff, '#00f'],
            [0xff00ff00, '#0f0'],
            [0xffff0000, '#f00'],
            [0xffffeeff, '#fef'],
            [0xffffffff, '#fff'],
            [0x7f000000, '#0000007f'],
            [0x7fffffff, '#ffffff7f'],
            [0xaaffeeff, '#fefa'],
            [0xaaffeedd, '#feda'],
        ];
    }

    /**
     * @covers ::intToRgba
     * @dataProvider intToRgbaProvider
     */
    public function testIntToRgba($expected, $rgba)
    {
        $this->assertEquals($expected, Misc::intToRgba($rgba));
    }

    public function intToRgbaProvider(): array
    {
        return [
            ['#000000', 0xff000000],
            ['#0000ff', 0xff0000ff],
            ['#00ff00', 0xff00ff00],
            ['#ff0000', 0xffff0000],
            ['#ffffff', 0xffffffff],
            ['#0000ff', 0xff0000ff],
            ['#00ff00', 0xff00ff00],
            ['#ff0000', 0xffff0000],
            ['#ffeeff', 0xffffeeff],
            ['#ffffff', 0xffffffff],
            ['rgba(0,0,0,0.5)', 0x7f000000],
            ['rgba(255,255,255,0.5)', 0x7fffffff],
            ['rgba(255,238,255,0.67)', 0xaaffeeff],
            ['rgba(255,238,221,0.67)', 0xaaffeedd],
        ];
    }

    /**
     * Generated from @assert ('Street', 'Postal', 'City', 'Country') == "Street\nPostal City Country".
     * Generated from @assert ('Street', 'Postal', 'City', null) == "Street\nPostal City".
     * Generated from @assert ('Street', null, 'City', null) == "Street\nCity".
     * Generated from @assert ('Street', null, null, null) == "Street".
     * Generated from @assert (null, null, 'City', null) == "City".
     *
     * @covers ::formatAddress
     */
    public function testFormatAddress()
    {
        $this->assertEquals("Street\nPostal City Country", Misc::formatAddress('Street', 'Postal', 'City', 'Country'));
        $this->assertEquals("Street\nPostal City", Misc::formatAddress('Street', 'Postal', 'City', null));
        $this->assertEquals("Street\nCity", Misc::formatAddress('Street', null, 'City', null));
        $this->assertEquals("Street", Misc::formatAddress('Street', null, null, null));
        $this->assertEquals("City", Misc::formatAddress(null, null, 'City', null));
    }

    /**
     * Generated from @assert ("line") == "<p>line</p>".
     * Generated from @assert ("line *bold* line") == "<p>line <b>bold</b> line</p>".
     * Generated from @assert ("line *bold* /italic/ line") == "<p>line <b>bold</b> <i>italic</i> line</p>".
     * Generated from @assert ("---heading line---\nline") == "<h5>heading line</h5>\n<p>line</p>".
     * Generated from @assert ("-line 1\n-line 2") == "<li>line 1</li>\n<li>line 2</li>".
     *
     * @covers ::formatPreview
     */
    public function testFormatPreview()
    {
        $this->assertEquals("<p>line</p>", Misc::formatPreview("line"));
        $this->assertEquals("<p>line <b>bold</b> line</p>", Misc::formatPreview("line *bold* line"));
        $this->assertEquals("<p>line <b>bold</b> <i>italic</i> line</p>", Misc::formatPreview("line *bold* /italic/ line"));
        $this->assertEquals("<h5>heading line</h5>\n<p>line</p>", Misc::formatPreview("---heading line---\nline"));
        $this->assertEquals("<li>line 1</li>\n<li>line 2</li>", Misc::formatPreview("-line 1\n-line 2"));
    }

    /**
     * Generated from @assert ("01.23.45.67.89") == "01 23 45 67 89".
     * Generated from @assert ("+33-1-23-45-67-89") == "+33 1 23 45 67 89".
     * Generated from @assert ("T.+33-1-23-45-67-89") == "T.+33-1-23-45-67-89".
     *
     * @covers ::formatTel
     */
    public function testFormatTel()
    {
        $this->assertEquals("01 23 45 67 89", Misc::formatTel("01.23.45.67.89"));
        $this->assertEquals("+33 1 23 45 67 89", Misc::formatTel("+33-1-23-45-67-89"));
        $this->assertEquals("T.+33-1-23-45-67-89", Misc::formatTel("T.+33-1-23-45-67-89"));
    }

    /**
     * Generated from @assert (20) == '20'.
     * Generated from @assert (999) == '999'.
     * Generated from @assert (1000) == '1K'.
     * Generated from @assert (2000) == '2K'.
     * Generated from @assert (2100) == '2.1K'.
     * Generated from @assert (2150) == '2.15K'.
     * Generated from @assert (2157) == '2.16K'.
     * Generated from @assert (21573) == '21.6K'.
     * Generated from @assert (-21573) == '-21.6K'.
     * Generated from @assert (2000, 'K') == '2'.
     * Generated from @assert (2157, 'K') == '2.16'.
     *
     * @covers ::humanFloat
     */
    public function testHumanFloat()
    {
        $this->assertEquals('20', Misc::humanFloat(20));
        $this->assertEquals('999', Misc::humanFloat(999));
        $this->assertEquals('1K', Misc::humanFloat(1000));
        $this->assertEquals('2K', Misc::humanFloat(2000));
        $this->assertEquals('2.1K', Misc::humanFloat(2100));
        $this->assertEquals('2.15K', Misc::humanFloat(2150));
        $this->assertEquals('2.16K', Misc::humanFloat(2157));
        $this->assertEquals('21.6K', Misc::humanFloat(21573));
        $this->assertEquals('-21.6K', Misc::humanFloat(-21573));
        $this->assertEquals('2', Misc::humanFloat(2000, 'K'));
        $this->assertEquals('2.16', Misc::humanFloat(2157, 'K'));
    }

    /**
     * @covers ::getMaxUploadSize
     */
    public function testGetMaxUploadSize()
    {
        $this->assertEquals(
            min(
                Misc::convertSize(ini_get('upload_max_filesize')),
                Misc::convertSize(ini_get('post_max_size'))
            ),
            Misc::getMaxUploadSize()
        );
    }

    /**
     * Generated from @assert (20) == '20'.
     * Generated from @assert (999) == '999'.
     * Generated from @assert (1000) == '1000'.
     * Generated from @assert (1024) == '1K'.
     * Generated from @assert (1025) == '2K'.
     * Generated from @assert (2048) == '2K'.
     * Generated from @assert (1048575) == '1024K'.
     * Generated from @assert (1048576) == '1M'.
     * Generated from @assert (1048577) == '2M'.
     *
     * @covers ::humanBytes
     */
    public function testHumanBytes()
    {
        $this->assertEquals('20', Misc::humanBytes(20));
        $this->assertEquals('999', Misc::humanBytes(999));
        $this->assertEquals('1000', Misc::humanBytes(1000));
        $this->assertEquals('1K', Misc::humanBytes(1024));
        $this->assertEquals('2K', Misc::humanBytes(1025));
        $this->assertEquals('2K', Misc::humanBytes(2048));
        $this->assertEquals('1024K', Misc::humanBytes(1048575));
        $this->assertEquals('1M', Misc::humanBytes(1048576));
        $this->assertEquals('2M', Misc::humanBytes(1048577));
    }

    /**
     * Generated from @assert (array()) == null.
     * Generated from @assert (array(' ', 'First', 'Second')) == 'First Second'.
     * Generated from @assert (array("\n", 'Street', array(' ', 'Postal', 'City'))) == "Street\nPostal City".
     * Generated from @assert (array("\n", 'Street', array(' ', 'Postal', null, 'Country'))) == "Street\nPostal Country".
     * Generated from @assert (array(array('[', ', ', ']'), 'First', 'Second')) == "[First, Second]".
     * Generated from @assert (array("\n", null, null)) == null.
     *
     * @covers ::joinWs
     */
    public function testJoinWs()
    {
        $this->assertEquals(null, Misc::joinWs([]));
        $this->assertEquals('First Second', Misc::joinWs([' ', 'First', 'Second']));
        $this->assertEquals("Street\nPostal City", Misc::joinWs(["\n", 'Street', [' ', 'Postal', 'City']]));
        $this->assertEquals("Street\nPostal Country", Misc::joinWs(["\n", 'Street', [' ', 'Postal', null, 'Country']]));
        $this->assertEquals("[First, Second]", Misc::joinWs([['[', ', ', ']'], 'First', 'Second']));
        $this->assertEquals(null, Misc::joinWs(["\n", null, null]));
    }

    /**
     * Generated from @assert (array(1, 2, 3)) == array(1, 2, 3).
     * Generated from @assert (array('a'=>1, 'b'=>null, 'c'=>3, 'd'=>null, 'e'=>5)) == array('a'=>1, 'c'=>3, 'e'=>5, 'N'=>array('b', 'd')).
     *
     * @covers ::nullCompact
     */
    public function testNullCompact()
    {
        $this->assertEquals([1, 2, 3], Misc::nullCompact([1, 2, 3]));
        $this->assertEquals(['a' => 1, 'c' => 3, 'e' => 5, 'N' => ['b', 'd']], Misc::nullCompact(['a' => 1, 'b' => null, 'c' => 3, 'd' => null, 'e' => 5]));
    }

    /**
     * Generated from @assert (array(1, 2, 3)) == array(1, 2, 3).
     * Generated from @assert (array('a'=>1, 'c'=>3, 'e'=>5, 'N'=>array('b', 'd'))) == array('a'=>1, 'c'=>3, 'e'=>5, 'b'=>null, 'd'=>null).
     *
     * @covers ::nullRestore
     */
    public function testNullRestore()
    {
        $this->assertEquals([1, 2, 3], Misc::nullRestore([1, 2, 3]));
        $this->assertEquals(['a' => 1, 'c' => 3, 'e' => 5, 'b' => null, 'd' => null], Misc::nullRestore(['a' => 1, 'c' => 3, 'e' => 5, 'N' => ['b', 'd']]));
    }

    /**
     * @runInSeparateProcess
     * @covers ::sessionSetTtl
     */
    public function testSessionSetTtl()
    {
        $sid = (session_status() != PHP_SESSION_NONE)
            ? session_id()
            : null;
        $params0 = session_get_cookie_params();
        $ttl = mt_rand(1, 0xffffffff);
        Misc::sessionSetTtl($ttl);
        $params1 = session_get_cookie_params();
        $this->assertEquals($ttl, $params1['lifetime']);
        $this->assertNotEquals($params0['lifetime'], $params1['lifetime']);
        $this->assertNotEquals($sid, session_id());
    }

    /**
     * Generated from @assert ('simple') == 'simple'.
     * Generated from @assert ('simple string') == 'simple-string'.
     * Generated from @assert ('Very Common Name, Inc...') == 'very-common-name-inc'.
     *
     * @covers ::simplifyLine
     */
    public function testSimplifyLine()
    {
        $this->assertEquals('simple', Misc::simplifyLine('simple'));
        $this->assertEquals('simple-string', Misc::simplifyLine('simple string'));
        $this->assertEquals('very-common-name-inc', Misc::simplifyLine('Very Common Name, Inc...'));
    }

    /**
     * Generated from @assert ('simple') == 'simple'.
     * Generated from @assert ('line with % sign') == 'line with %% sign'.
     *
     * @covers ::sprintfEscape
     */
    public function testSprintfEscape()
    {
        $this->assertEquals('simple', Misc::sprintfEscape('simple'));
        $this->assertEquals('line with %% sign', Misc::sprintfEscape('line with % sign'));
    }

    /**
     * Generated from @assert ('select * from %tbl$s where %id$s = %value$u', ['tbl'=>'users', 'id'=>'user_id', 'value'=>15]) == 'select * from users where user_id = 15'.
     * Generated from @assert ('select * from %1$s where %2$s = %3$u', ['users', 'user_id', 15]) == 'select * from users where user_id = 15'.
     * Generated from @assert ('select * from %s where %s = %u', ['users', 'user_id', 15]) == 'select * from users where user_id = 15'.
     * Generated from @assert ('aaa=%aaa$s, aa=%aa$s, a=%a$s', ['a'=>'A', 'aa'=>'AA', 'aaa'=>'AAA']) == 'aaa=aaa, aa=aa, a=a'.
     *
     * @covers ::vsprintfArgs
     */
    public function testVsprintfArgs()
    {
        $this->assertEquals(
            'select * from users where user_id = 15',
            Misc::vsprintfArgs('select * from %tbl$s where %id$s = %value$u', ['tbl' => 'users', 'id' => 'user_id', 'value' => 15])
        );
        $this->assertEquals(
            'select * from users where user_id = 15',
            Misc::vsprintfArgs('select * from %1$s where %2$s = %3$u', ['users', 'user_id', 15])
        );
        $this->assertEquals(
            'select * from users where user_id = 15',
            Misc::vsprintfArgs('select * from %s where %s = %u', ['users', 'user_id', 15])
        );
        $this->assertEquals(
            'aaa=AAA, aa=AA, a=A',
            Misc::vsprintfArgs('aaa=%aaa$s, aa=%aa$s, a=%a$s', ['a' => 'A', 'aa' => 'AA', 'aaa' => 'AAA'])
        );
    }

    /**
     * Generated from @assert ('line') == 'line'.
     * Generated from @assert ('longer line', 8) == 'longe...'.
     * Generated from @assert ('длинная строка', 10, '.') == 'длинная с.'.
     *
     * @covers ::trim
     */
    public function testTrim()
    {
        $this->assertEquals('line', Misc::trim('line'));
        $this->assertEquals('longe...', Misc::trim('longer line', 8));
        $this->assertEquals('длинная с.', Misc::trim('длинная строка', 10, '.'));
    }

    /**
     * Generated from @assert ('much longer line', 15) == 'much longer...'.
     * Generated from @assert ('гораздо более длинная строка', 23) == 'гораздо более...'.
     *
     * @covers ::trimWord
     */
    public function testTrimWord()
    {
        $this->assertEquals('much longer...', Misc::trimWord('much longer line', 15));
        $this->assertEquals('гораздо более...', Misc::trimWord('гораздо более длинная строка', 23));
    }

    /**
     * Generated from @assert (0x29) === null.
     * Generated from @assert (0xE2) == 3.
     * Generated from @assert (0x89) == 0.
     * Generated from @assert (0xA2) == 0.
     * Generated from @assert (0xCE) == 2.
     * Generated from @assert (0xF0) == 4.
     * Generated from @assert (0xC0) === false.
     * Generated from @assert (0xC1) === false.
     * Generated from @assert (0xF5) === false.
     * Generated from @assert (0xFF) === false.
     *
     * @covers ::utf8Leading
     */
    public function testUtf8Leading()
    {
        $this->assertNull(Misc::utf8Leading(0x29));
        $this->assertEquals(3, Misc::utf8Leading(0xE2));
        $this->assertEquals(0, Misc::utf8Leading(0x89));
        $this->assertEquals(0, Misc::utf8Leading(0xA2));
        $this->assertEquals(2, Misc::utf8Leading(0xCE));
        $this->assertEquals(4, Misc::utf8Leading(0xF0));
        $this->assertFalse(Misc::utf8Leading(0xC0));
        $this->assertFalse(Misc::utf8Leading(0xC1));
        $this->assertFalse(Misc::utf8Leading(0xF5));
        $this->assertFalse(Misc::utf8Leading(0xFF));
    }

    /**
     * Generated from @assert (0xA2) === true.
     * Generated from @assert (0xE2) === false.
     * Generated from @assert (0x29) === false.
     *
     * @covers ::utf8Trailing
     */
    public function testUtf8Trailing()
    {
        $this->assertTrue(Misc::utf8Trailing(0xA2));
        $this->assertFalse(Misc::utf8Trailing(0xE2));
        $this->assertFalse(Misc::utf8Trailing(0x29));
    }
}
