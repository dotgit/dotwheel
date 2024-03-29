<?php

namespace Dotwheel\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Algo
 */
class AlgoTest extends TestCase
{
    /**
     * @covers Algo::luhn
     * @dataProvider luhnProvider
     */
    public function testLuhn($expected, $num_str)
    {
        $this->assertEquals($expected, Algo::luhn($num_str));
    }

    public function luhnProvider(): array
    {
        return [
            '972487086' => [true, '972487086'],
            '732829320' => [true, '732829320'],
            '73282932000074' => [true, '73282932000074'],
            '73282932000075' => [false, '73282932000075'],
            '49927398716' => [true, '49927398716'],
            '49927398717' => [false, '49927398717'],
            '1234567812345670' => [true, '1234567812345670'],
            '1234567812345678' => [false, '1234567812345678'],
        ];
    }

    /**
     * @covers Algo::mod97
     * @dataProvider mod97Provider
     */
    public function testMod97($expected, $num_str)
    {
        $this->assertEquals($expected, Algo::mod97($num_str));
    }

    public function mod97Provider(): array
    {
        return [
            '0' => [0, '0'],
            '1' => [1, '1'],
            '2' => [2, '2'],
            '96' => [96, '96'],
            '97' => [0, '97'],
            '98' => [1, '98'],
            '100' => [3, '100'],
            '1110271220658244971655161187' => [1, '1110271220658244971655161187'],
            '068999999501111443' => [1, '068999999501111443'],
            '3214282912345698765432161182' => [1, '3214282912345698765432161182'],
        ];
    }

    /**
     * @covers Algo::uniqueCode
     */
    public function testUniqueCode()
    {
        $id1 = Algo::uniqueCode(16);
        $id2 = Algo::uniqueCode(16);
        $id3 = Algo::uniqueCode(16);
        $id4 = Algo::uniqueCode(16);

        $this->assertEquals(32, strlen($id1));
        $this->assertEquals(32, strlen($id2));
        $this->assertEquals(32, strlen($id3));
        $this->assertEquals(32, strlen($id4));

        $this->assertTrue($id1 < $id2, "$id1 < $id2");
        $this->assertTrue($id2 < $id3, "$id2 < $id3");
        $this->assertTrue($id3 < $id4, "$id3 < $id4");
    }

    /**
     * @covers Algo::uniqueXid
     */
    public function testXid()
    {
        $id1 = Algo::uniqueXid(0x42);
        $id2 = Algo::uniqueXid(0x42);
        $id3 = Algo::uniqueXid(0x42);
        $id4 = Algo::uniqueXid(0x42);

        $this->assertEquals(24, strlen($id1));
        $this->assertEquals(24, strlen($id2));
        $this->assertEquals(24, strlen($id3));
        $this->assertEquals(24, strlen($id4));

        $this->assertTrue($id1 < $id2, "$id1 < $id2");
        $this->assertTrue($id2 < $id3, "$id2 < $id3");
        $this->assertTrue($id3 < $id4, "$id3 < $id4");
    }
}
