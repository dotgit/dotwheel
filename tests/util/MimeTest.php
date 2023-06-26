<?php

namespace Dotwheel\Util;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Mime
 */
class MimeTest extends TestCase
{
    /**
     * @covers ::compileParts
     * @dataProvider compilePartsProvider
     */
    public function testCompileParts($expectedRegex, $parts, $headers)
    {
        $this->assertMatchesRegularExpression($expectedRegex, Mime::compileParts($parts, $headers));
        $this->assertContains('MIME-Version: 1.0', $headers);
    }

    public function compilePartsProvider(): array
    {
        return [
            'no parts' => ["/^--([^\r]+)\r\n\r\n--\\1--$/", [], []],
            'one part' => ["/^--([^\r]+)\r\nFirst part\r\n--\\1--$/", ['First part'], []],
            'two parts' => [
                "/^--([^\r]+)\r\nFirst part\r\n--\\1\r\nSecond\r\npart\r\n--\\1--$/",
                ['First part', "Second\r\npart"],
                []
            ],
        ];
    }

    /**
     * @covers ::displayName
     * @dataProvider displayNameProvider
     */
    public function testDisplayName($expected, $name)
    {
        $this->assertEquals($expected, Mime::displayName($name));
    }

    public function displayNameProvider(): array
    {
        return [
            'simple name' => ['Resnick', 'Resnick'],
            'double name' => ['Mary Smith', 'Mary Smith'],
            'name with punctuation' => ['"Joe Q. Public"', 'Joe Q. Public'],
            'name with punctuation2' => ['"Giant; \\"Big\\" \\\\Box"', 'Giant; "Big" \\Box'],
            'unicode name' => ['"Jérôme"', 'Jérôme'],
        ];
    }

    /**
     * @covers ::partBase64
     * @dataProvider partsProvider
     */
    public function testPartBase64($content, $type)
    {
        $result = Mime::partBase64($content, $type);

        $this->assertStringContainsString("Content-Type: $type", $result);
        $this->assertStringContainsString("Content-Transfer-Encoding: base64", $result);
        $this->assertStringContainsString("\r\n\r\n" . chunk_split(base64_encode($content)), $result);
    }

    /**
     * @covers ::partQuoted
     * @dataProvider partsProvider
     */
    public function testPartQuoted($content, $type)
    {
        $result = Mime::partQuoted($content, $type);

        $this->assertStringContainsString("Content-Type: $type", $result);
        $this->assertStringContainsString("Content-Transfer-Encoding: quoted-printable", $result);
        $this->assertStringContainsString("\r\n\r\n" . quoted_printable_encode($content), $result);
    }

    public function partsProvider(): array
    {
        return [
            'empty line' => ['', 'text/plain'],
            'single line' => ['Text', 'text/plain'],
            'long line' => ['A very long line of a somewhat senceless words just to fill some space. And this text may be even longer, and longer, and longer, and longer, and longer, and longer, and longer, and longer, and longer.', 'text/plain'],
        ];
    }
}
