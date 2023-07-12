<?php

namespace Dotwheel\Nls;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Text
 */
class TextTest extends TestCase
{
    const DOMAIN = 'tests';

    /**
     * Reset Text class
     */
    public static function setUpBeforeClass(): void
    {
        Text::$pluralForms = 0;
        Text::$domainTranslations = [];
        Text::$domain = 'messages';
    }

    /**
     * @covers ::binddomain
     * @dataProvider binddomainProvider
     */
    public function testBinddomain($lang, $expected_plurals)
    {
        Text::binddomain(self::DOMAIN, __DIR__ . '/locale', $lang);
        $this->assertEquals($expected_plurals, Text::$pluralForms);
        $this->assertNotEmpty(Text::$domainTranslations);
        $this->assertEquals(self::DOMAIN, Text::$domain);
    }

    public function binddomainProvider(): array
    {
        return [
            'english locale' => ['en', '($n != 1)'],
            'french locale' => ['fr', '($n > 1)'],
        ];
    }

    /**
     * @covers ::domain
     */
    public function testDomain()
    {
        Text::binddomain(self::DOMAIN, __DIR__ . '/locale', 'fr');
        $domain = Text::$domain;
        Text::domain('unknown');
        $this->assertEquals('unknown', Text::$domain, 'current domain is reset');
        $this->assertEquals('Next', Text::_('Next'), 'translation from non-existent current domain');
        Text::domain($domain);
        $this->assertEquals('Suivant', Text::_('Next'), 'translation from existant current domain');
    }

    /**
     * @covers ::_
     */
    public function test_()
    {
        $this->assertEquals('Suivant', Text::_('Next'), 'existing translation');
        $unknown = 'non-existent ' . rand(100, 999);
        $this->assertEquals($unknown, Text::_($unknown), 'non-existent translation');
    }

    /**
     * @covers ::dget
     */
    public function testDget()
    {
        Text::domain('unknown');
        $this->assertEquals('Next', Text::_('Next'), 'translation from non-existent current domain');
        $this->assertEquals('Suivant', Text::dget(self::DOMAIN, 'Next'), 'translation from ' . self::DOMAIN);
        Text::domain(self::DOMAIN);
    }

    /**
     * @covers ::pget
     */
    public function testPget()
    {
        $this->assertEquals('Verte', Text::pget('grass', 'Green'), 'existing translation using context');
        $unknown = 'non-existent ' . rand(100, 999);
        $this->assertEquals($unknown, Text::pget('grass', $unknown), 'non-existent translation using context');
    }

    /**
     * @covers ::dpget
     */
    public function testDpget()
    {
        $this->assertEquals('Green', Text::dpget('unknown', 'grass', 'Green'), 'translation from non-existent domain');
        $this->assertEquals('Verte', Text::dpget(self::DOMAIN, 'grass', 'Green'), 'translation from ' . self::DOMAIN);
    }

    /**
     * @covers ::nget
     * @dataProvider ngetProvider
     */
    public function testNget($n, $expected)
    {
        $this->assertEquals($expected, sprintf(Text::nget('%u line', '%u lines', $n), $n));
    }

    /**
     * @covers ::dnget
     * @dataProvider ngetProvider
     */
    public function testDnget($n, $expected)
    {
        Text::domain('unknown');
        $this->assertEquals($expected, sprintf(Text::dnget(self::DOMAIN, '%u line', '%u lines', $n), $n));
        Text::domain(self::DOMAIN);
    }

    public function ngetProvider(): array
    {
        return [
            'zero lines' => [0, '0 ligne'],
            'one ligne' => [1, '1 ligne'],
            'two lines' => [2, '2 lignes'],
            'five lines' => [5, '5 lignes'],
        ];
    }

    /**
     * @covers ::npget
     * @dataProvider pgetProvider
     */
    public function testPnget($n, $expected)
    {
        $this->assertEquals($expected, sprintf(Text::npget('line', '%u sent', '%u sent', $n), $n));
    }

    /**
     * @covers ::dnpget
     * @dataProvider pgetProvider
     */
    public function testDpnget($n, $expected)
    {
        Text::domain('unknown');
        $this->assertEquals($expected, sprintf(Text::dnpget(self::DOMAIN, 'line', '%u sent', '%u sent', $n), $n));
        Text::domain(self::DOMAIN);
    }

    public function pgetProvider(): array
    {
        return [
            'zero lines' => [0, '0 envoyée'],
            'one ligne' => [1, '1 envoyée'],
            'two lines' => [2, '2 envoyées'],
            'five lines' => [5, '5 envoyées'],
        ];
    }
}
