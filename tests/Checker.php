<?php

namespace Dotwheel\Tests;

use PHPUnit_Framework_TestCase;

trait Checker
{
    public function assertByType($expected, $result)
    {
        if ($expected === null) {
            PHPUnit_Framework_TestCase::assertNull($result);
        } elseif ($expected === true) {
            PHPUnit_Framework_TestCase::assertTrue($result);
        } elseif ($expected === false) {
            PHPUnit_Framework_TestCase::assertFalse($result);
        } elseif ($expected === '' or ! is_string($expected)) {
            PHPUnit_Framework_TestCase::assertEquals($expected, $result);
        } else {
            if ($expected[0] === '*' and substr($expected, -1) === '*' and strlen($expected) > 2) {
                PHPUnit_Framework_TestCase::assertContains(substr($expected, 1, -1), $result);
            } elseif ($expected[0] === '/' and substr($expected, -1) === '/' and strlen($expected) > 2) {
                PHPUnit_Framework_TestCase::assertRegExp($expected, $result);
            } else {
                PHPUnit_Framework_TestCase::assertEquals($expected, $result);
            }
        }
    }

    public function assertChecker($expected, $result)
    {
        if (is_array($expected)) {
            foreach ($expected as $k => $part) {
                self::assertByType($part, $result);
            }
        } else {
            self::assertByType($expected, $result);
        }
    }
}
