<?php

namespace Dotwheel\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Commerce
 */
class CommerceTest extends TestCase
{
    /**
     * @covers ::ibanGenerate
     * @dataProvider ibanGenerateProvider
     */
    public function testIbanGenerate($expected, $country, $account)
    {
        $this->assertEquals($expected, Commerce::ibanGenerate($country, $account));
    }

    public function ibanGenerateProvider(): array
    {
        return [
            'BE62 5100 0754 7061' => ['BE62 5100 0754 7061', 'be', '510-0075470-61'],
            'DE44 5001 0517 5407 3249 31' => ['DE44 5001 0517 5407 3249 31', 'De', '500105175407324931'],
            'GR16 0110 1250 0000 0001 2300 695' => ['GR16 0110 1250 0000 0001 2300 695', 'GR', '0110 1250 0000 0001 2300 695'],
            'GB29 NWBK 6016 1331 9268 19' => ['GB29 NWBK 6016 1331 9268 19', 'GB', 'NWBK 6016 1331 9268 19'],
            'GB82 WEST 1234 5698 7654 32' => ['GB82 WEST 1234 5698 7654 32', 'GB', 'WEST 1234 5698 7654 32'],
            'SA03 8000 0000 6080 1016 7519' => ['SA03 8000 0000 6080 1016 7519', 'SA', '8000 0000 6080 1016 7519'],
            'CH93 0076 2011 6238 5295 7' => ['CH93 0076 2011 6238 5295 7', 'CH', '0076 2011 6238 5295 7'],
            'TR33 0006 1005 1978 6457 8413 26' => ['TR33 0006 1005 1978 6457 8413 26', 'TR', '0006 1005 1978 6457 8413 26'],
        ];
    }

    /**
     * @covers ::ibanValidate
     * @dataProvider ibanValidateProvider
     */
    public function testIbanValidate($expected, $iban)
    {
        $this->assertEquals($expected, Commerce::ibanValidate($iban));
    }

    public function ibanValidateProvider(): array
    {
        return [
            'BE62 5100 0754 7061' => [true, 'BE62 5100 0754 7061'],
            'DE44 5001 0517 5407 3249 31' => [true, 'DE44 5001 0517 5407 3249 31'],
            'GR16 0110 1250 0000 0001 2300 695' => [true, 'GR16 0110 1250 0000 0001 2300 695'],
            'GB29 NWBK 6016 1331 9268 19' => [true, 'GB29 NWBK 6016 1331 9268 19'],
            'GB82 WEST 1234 5698 7654 32' => [true, 'GB82 WEST 1234 5698 7654 32'],
            'SA03 8000 0000 6080 1016 7519' => [true, 'SA03 8000 0000 6080 1016 7519'],
            'CH93 0076 2011 6238 5295 7' => [true, 'CH93 0076 2011 6238 5295 7'],
            'TR33 0006 1005 1978 6457 8413 26' => [true, 'TR33 0006 1005 1978 6457 8413 26'],
        ];
    }
}
