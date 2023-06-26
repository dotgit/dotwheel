<?php

namespace Dotwheel\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Misc
 */
class AclTest extends TestCase
{
    /**
     * @covers ::containsRequired
     * @dataProvider containsRequiredProvider
     */
    public function testContainsRequired($expected, $present, $required)
    {
        $this->assertEquals($expected, Acl::containsRequired($present, $required));
    }

    public function containsRequiredProvider(): array
    {
        return [
            '1st level, int' => [true, [0x010000, 0x010100, 0x010101], 0x010000],
            '2nd level, int' => [true, [0x010000, 0x010100, 0x010101], 0x010100],
            '3rd level, int' => [true, [0x010000, 0x010100, 0x010101], 0x010101],
            'absent, int' => [false, [0x010000, 0x010100, 0x010101], 0x020101],
            'present, array' => [true, [0x010000, 0x010100, 0x010101], [0x010101, 0x020101]],
            'absent, array' => [false, [0x010000, 0x010100, 0x010101], [0x000000, 0x000000]],
        ];
    }
}
