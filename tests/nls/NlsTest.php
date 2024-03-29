<?php

namespace Dotwheel\Nls;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Nls
 */
class NlsTest extends TestCase
{
    /**
     * @covers ::getLang
     * @todo   Implement testGetLang().
     */
    public function testGetLang()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers ::setLang
     * @todo   Implement testSetLang().
     */
    public function testSetLang()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers ::guessLang
     */
    public function testGuessLang()
    {
        $this->assertEquals('en', Nls::guessLang(['en', 'fr'], 'en'));
    }

    /**
     * @covers ::init
     */
    public function testInit()
    {
        $this->assertEquals('en', Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'en'), 'initialize english env');
        $this->assertEquals('fr', Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'fr'), 'initialize french env');
    }

    /**
     * @covers ::toDate
     */
    public function testToDate()
    {
        Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'en');
        $this->assertEquals('2016-04-05', Nls::toDate('4/5/16'));
        Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'fr');
        $this->assertEquals('2016-04-05', Nls::toDate('5/4/16'));
        $this->assertEquals('2016-12-31 23:59:59', Nls::toDate('31/12/2016 23:59:59', true));
    }

    /**
     * @covers ::asFloat
     */
    public function testAsFloat()
    {
        Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'en');
        $this->assertEquals('1234.56', Nls::asFloat(1234.56));
        Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'fr');
        $this->assertEquals('2345,67', Nls::asFloat(2345.67));
    }

    /**
     * @covers ::asNumber
     */
    public function testAsNumber()
    {
        Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'en');
        $this->assertEquals('1,234.56', Nls::asNumber(1234.56));
        Nls::init(TextTest::DOMAIN, __DIR__ . '/locale', 'fr');
        $this->assertEquals('2 345,67', Nls::asNumber(2345.67));
        $this->assertEquals('2 345,6', Nls::asNumber(2345.5999));
        $this->assertEquals('2 345', Nls::asNumber(2345.0001));
    }
}
