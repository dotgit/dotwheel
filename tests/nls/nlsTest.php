<?php

namespace Dotwheel\Nls;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-04-05 at 19:26:18.
 */
class NlsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Dotwheel\Nls\Nls::getLang
     * @todo   Implement testGetLang().
     */
    public function testGetLang()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers Dotwheel\Nls\Nls::setLang
     * @todo   Implement testSetLang().
     */
    public function testSetLang()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers Dotwheel\Nls\Nls::guessLang
     */
    public function testGuessLang()
    {
        $this->assertEquals('en', Nls::guessLang(array('en', 'fr'), 'en'));
    }

    /**
     * @covers Dotwheel\Nls\Nls::init
     */
    public function testInit()
    {
        $this->assertEquals('en', Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'en'), 'initialize english env');
        $this->assertEquals('fr', Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'fr'), 'initialize french env');
    }

    /**
     * @covers Dotwheel\Nls\Nls::toDate
     */
    public function testToDate()
    {
        Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'en');
        $this->assertEquals('2016-04-05', Nls::toDate('4/5/16'));
        Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'fr');
        $this->assertEquals('2016-04-05', Nls::toDate('5/4/16'));
        $this->assertEquals('2016-12-31 23:59:59', Nls::toDate('31/12/2016 23:59:59', true));
    }

    /**
     * @covers Dotwheel\Nls\Nls::asFloat
     */
    public function testAsFloat()
    {
        Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'en');
        $this->assertEquals('1234.56', Nls::asFloat(1234.56));
        Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'fr');
        $this->assertEquals('2345,67', Nls::asFloat(2345.67));
    }

    /**
     * @covers Dotwheel\Nls\Nls::asNumber
     */
    public function testAsNumber()
    {
        Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'en');
        $this->assertEquals('1,234.56', Nls::asNumber(1234.56));
        Nls::init(TextTest::DOMAIN, __DIR__.'/locale', 'fr');
        $this->assertEquals('2 345,67', Nls::asNumber(2345.67));
        $this->assertEquals('2 345,6', Nls::asNumber(2345.5999));
        $this->assertEquals('2 345', Nls::asNumber(2345.0001));
    }
}
