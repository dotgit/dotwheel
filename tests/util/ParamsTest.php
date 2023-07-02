<?php

namespace Dotwheel\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Params
 */
class ParamsTest extends TestCase
{
    /**
     * @covers ::add
     * @dataProvider addProvider
     */
    public function testAdd($expected, $params, $value, $name, $sep)
    {
        Params::add($params, $value, $name, $sep);
        $this->assertEquals($expected, $params);
    }

    public function addProvider(): array
    {
        return [
            [['class' => 'active'], '', 'active', 'class', ' '],
            [['class' => 'active'], [], 'active', 'class', ' '],
            [['class' => null], '', null, 'class', ' '],
            [['id' => 'usr', 'class' => 'active'], ['class' => 'active'], 'usr', 'id', ' '],
            [['id' => 'usr', 'class' => 'active'], ['id' => 'usr', 'class' => 'active'], 'active', 'class', ' '],
            [['id' => 'usr', 'class' => 'active second'], ['id' => 'usr', 'class' => 'active'], 'second', 'class', ' '],
            [['style' => 'height:30px;width:100%;'], ['style' => 'height:30px;'], 'width:100%;', 'style', ''],
        ];
    }

    /**
     * @covers ::extract
     * @dataProvider extractProvider
     */
    public function testExtract($expected, $params, $name, $default)
    {
        $result = Params::extract($params, $name, $default);
        $this->assertEquals($expected, $result);
        $this->assertArrayNotHasKey($name, $params);
    }

    public function extractProvider(): array
    {
        return [
            ['active', ['class' => 'active'], 'class', null],
            [null, ['class' => 'active'], 'id', null],
            ['default', ['class' => 'active'], 'id', 'default'],
        ];
    }
}
