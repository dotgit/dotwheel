<?php

namespace Dotwheel\Tests;

use PHPUnit\Framework\TestCase;

trait Checker
{
    public function assertByType($expected, $result)
    {
        if ($expected === null) {
            TestCase::assertNull($result);
        } elseif ($expected === true) {
            TestCase::assertTrue($result);
        } elseif ($expected === false) {
            TestCase::assertFalse($result);
        } elseif ($expected === '' or !is_string($expected)) {
            TestCase::assertEquals($expected, $result);
        } else {
            if ($expected[0] === '*' and substr($expected, -1) === '*' and strlen($expected) > 2) {
                TestCase::assertStringContainsString(substr($expected, 1, -1), $result);
            } elseif ($expected[0] === '/' and substr($expected, -1) === '/' and strlen($expected) > 2) {
                TestCase::assertMatchesRegularExpression($expected, $result);
            } else {
                TestCase::assertEquals($expected, $result);
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
