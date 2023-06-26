<?php

namespace Dotwheel\Db;

use DbBeforeClass;
use Dotwheel\Nls\Nls;
use Dotwheel\Nls\TextTest;
use Dotwheel\Tests\Checker;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Repo
 */
class RepoTest extends TestCase
{
    use Checker;

    const PKG_NAME = __CLASS__;

    /**
     * @coversNothing
     * @uses ::registerPackage
     */
    public static function setUpBeforeClass(): void
    {
        self::assertEquals(
            'fr',
            Nls::init(TextTest::DOMAIN, __DIR__ . '/../nls/locale', 'fr'),
            'initialize english env'
        );
        self::assertTrue(
            Repo::registerPackage(self::PKG_NAME, [
                DbBeforeClass::C_SECTION => [
                    Repo::P_CLASS => Repo::C_ID,
                    Repo::P_LABEL => 'Section id',
                ],
                DbBeforeClass::C_ID => [
                    Repo::P_CLASS => Repo::C_ID,
                    Repo::P_LABEL => 'Item id',
                ],
                DbBeforeClass::C_NAME => [
                    Repo::P_CLASS => Repo::C_TEXT,
                    Repo::P_WIDTH => 255,
                    Repo::P_LABEL => 'Name',
                    Repo::P_LABEL_LONG => 'Full name',
                ],
            ]),
            'register package'
        );
    }

    /**
     * @covers ::registerPackage
     * @covers ::getParam
     */
    public function testRegisterPackage()
    {
        $this->assertArrayHasKey(
            DbBeforeClass::C_SECTION,
            Repo::$store,
            'initial fields were registered'
        );

        $this->assertFalse(
            Repo::registerPackage(self::PKG_NAME, [
                'another_field' => [
                    Repo::P_CLASS => Repo::C_ID,
                    Repo::P_LABEL => 'Id'
                ],
            ]),
            'error since package already registered'
        );

        $this->assertTrue(
            Repo::registerPackage(self::PKG_NAME . '_' . rand(100, 999), [
                'section_alias' => [
                    Repo::P_ALIAS => DbBeforeClass::C_SECTION,
                ],
                'new_alias' => [
                    Repo::P_ALIAS => 'new_field',
                ],
            ]),
            'symlinks resolution'
        );
        $section_alias_class = Repo::getParam('section_alias', Repo::P_CLASS);
        $this->assertEquals(
            Repo::C_ID,
            $section_alias_class,
            'symlink resolved to existing target'
        );
        $new_alias_class = Repo::getParam('new_alias', Repo::P_CLASS);
        $this->assertEmpty(
            $new_alias_class,
            'forwarded symlink unresolved for now'
        );

        $this->assertTrue(
            Repo::registerPackage(
                self::PKG_NAME . '_2_' . rand(100, 999),
                [
                    'new_field' => [
                        Repo::P_CLASS => Repo::C_TEXT,
                        Repo::P_WIDTH => 255,
                        Repo::P_LABEL => 'New name'
                    ],
                ]
            ),
            'forwarded symlinks resolution'
        );
        $new_alias_class = Repo::getParam('new_alias', Repo::P_CLASS);
        $this->assertEquals(
            Repo::C_TEXT,
            $new_alias_class,
            'forwarded symlink resolved to target'
        );
    }

    /**
     * @covers ::getParam
     */
    public function testGetParam()
    {
        $this->assertEquals(
            Repo::C_DATE,
            Repo::getParam(DbBeforeClass::C_NAME, Repo::P_CLASS, [Repo::P_CLASS => Repo::C_DATE]),
            'passed repository entry overwrites registered one'
        );
        $this->assertNull(
            Repo::getParam(DbBeforeClass::C_NAME, Repo::P_ITEMS),
            'null for non-existent parameters'
        );
    }

    /**
     * @covers ::getLabel
     */
    public function testGetLabel()
    {
        $this->assertEquals(
            'Name',
            Repo::getLabel(DbBeforeClass::C_NAME),
            'registered name'
        );
        $this->assertEquals(
            'The name',
            Repo::getLabel(DbBeforeClass::C_NAME, null, [Repo::P_LABEL => 'The name']),
            'passed repository entry overwrites registered one'
        );
        $this->assertNull(
            Repo::getLabel('unknown_field'),
            'null for non-existent fields'
        );
        $this->assertEquals(
            'Full name',
            Repo::getLabel(DbBeforeClass::C_NAME, Repo::P_LABEL_LONG),
            'P_LABEL_LONG parameter'
        );
        $this->assertEquals(
            'Name',
            Repo::getLabel(DbBeforeClass::C_NAME, Repo::P_LABEL_SHORT),
            'missing P_LABEL_SHORT parameter falls back to P_LABEL'
        );
        $this->assertEquals(
            'Nm',
            Repo::getLabel(DbBeforeClass::C_NAME, Repo::P_LABEL_SHORT, [Repo::P_LABEL_SHORT => 'Nm']),
            'passed repository entry overwrites missing P_LABEL_SHORT parameter'
        );
    }

    /**
     * @covers ::getList
     */
    public function testGetList()
    {
        $repo = [
            Repo::P_CLASS => Repo::C_ENUM,
            Repo::P_LABEL => 'Numbers',
            Repo::P_ITEMS => [
                1 => 'first',
                2 => 'second',
                3 => 'third',
            ],
            Repo::P_ITEMS_SHORT => [
                1 => 'one',
                2 => 'two',
                3 => 'three',
            ],
        ];
        $this->assertEquals(
            'third',
            Repo::getList('unknown', null, $repo)[3],
            'P_ITEMS returned from passed repository entry'
        );
        $this->assertEquals(
            'two',
            Repo::getList('unknown', Repo::P_ITEMS_SHORT, $repo)[2],
            'P_ITEMS_SHORT returned from passed repository entry'
        );
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $fld_name = Repo::get(DbBeforeClass::C_NAME);
        $this->assertArrayHasKey(
            Repo::P_CLASS,
            $fld_name,
            'get registered entry'
        );
        $this->assertEquals(
            Repo::C_TEXT,
            $fld_name[Repo::P_CLASS],
            'get registered entry'
        );
        $fld_name2 = Repo::get(DbBeforeClass::C_NAME, [Repo::P_CLASS => Repo::C_ENUM]);
        $this->assertEquals(
            Repo::C_ENUM,
            $fld_name2[Repo::P_CLASS],
            'get passed entry'
        );
        $this->assertEquals(
            255,
            $fld_name2[Repo::P_WIDTH],
            'does not overwrite non-passed attributes'
        );
    }

    /**
     * @covers ::isArithmetical
     * @uses ::get
     */
    public function testIsArithmetical()
    {
        $this->assertFalse(
            Repo::isArithmetical(Repo::get(DbBeforeClass::C_SECTION)),
            'P_CLASS:C_ID arithmetical?'
        );
        $this->assertFalse(
            Repo::isArithmetical(Repo::get(DbBeforeClass::C_NAME)),
            'P_CLASS:C_TEXT arithmetical?'
        );
        $this->assertTrue(
            Repo::isArithmetical(Repo::get('price', [Repo::P_CLASS => Repo::C_CENTS])),
            'P_CLASS:C_CENTS arithmetical?'
        );
        $this->assertTrue(
            Repo::isArithmetical(Repo::get('qty', [Repo::P_CLASS => Repo::C_INT])),
            'P_CLASS:C_INT arithmetical?'
        );
    }

    /**
     * @covers ::isDate
     * @uses ::get
     */
    public function testIsDate()
    {
        $this->assertFalse(
            Repo::isDate(Repo::get(DbBeforeClass::C_NAME)),
            'P_CLASS:C_TEXT date?'
        );
        $this->assertTrue(
            Repo::isDate(Repo::get('queued', [Repo::P_CLASS => Repo::C_DATE])),
            'P_CLASS:C_DATE date?'
        );
    }

    /**
     * @covers ::isTextual
     * @uses ::get
     */
    public function testIsTextual()
    {
        $this->assertFalse(
            Repo::isTextual(Repo::get(DbBeforeClass::C_SECTION)),
            'P_CLASS:C_ID textual?'
        );
        $this->assertTrue(
            Repo::isTextual(Repo::get(DbBeforeClass::C_NAME)),
            'P_CLASS:C_TEXT textual?'
        );
    }

    /**
     * @covers ::validateInput
     * @dataProvider validateInputErrorProvider
     */
    public function testValidateInputError($expected, $field, $value, $repo)
    {
        $this->assertFalse(Repo::validateInput(
            [$field => $repo],
            [$field => $value]
        ));

        $this->assertChecker($expected, implode(' ', Repo::$input_errors));
    }

    public function validateInputErrorProvider(): array
    {
        return [
            'required field is empty' =>
                ['*Name*', DbBeforeClass::C_NAME, null, [Repo::P_REQUIRED => true]],
            'C_ID: value can only be positive' =>
                ['*Item id*', DbBeforeClass::C_ID, -1, [Repo::P_REQUIRED => true]],
            'C_TEXT: malformed email' =>
                [
                    '*Email*',
                    'user_email',
                    'not.an/email',
                    [Repo::P_LABEL => 'Email', Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_EMAIL],
                ],
            'C_TEXT: malformed url' =>
                [
                    '*User profile*',
                    'user_url',
                    'not.an/url',
                    [Repo::P_LABEL => 'User profile', Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_URL],
                ],
            'C_TEXT: the field is too long' =>
                [
                    '*User tel*',
                    'user_tel',
                    '12345678901234567890123',
                    [Repo::P_LABEL => 'User tel', Repo::P_CLASS => Repo::C_TEXT, Repo::P_WIDTH => 20],
                ],
            'P_DATE field must represent a date' =>
                [
                    '*Birthday*',
                    'user_birthday',
                    'not-a-date',
                    [Repo::P_LABEL => 'Birthday', Repo::P_CLASS => Repo::C_DATE],
                ],
            'P_CENTS field must be numeric' =>
                [
                    '*Price*',
                    'price',
                    '123x45',
                    [Repo::P_LABEL => 'Price', Repo::P_CLASS => Repo::C_CENTS],
                ],
            'C_BOOL field must be boolean' =>
                [
                    '*Is due*',
                    'is_due',
                    'unspecified',
                    [Repo::P_LABEL => 'Is due', Repo::P_CLASS => Repo::C_BOOL],
                ],
            'P_VALIDATE_REGEXP field must match regexp' =>
                [
                    '*Postal*',
                    'postal',
                    '12-345',
                    [Repo::P_LABEL => 'Postal', Repo::P_CLASS => Repo::C_TEXT, Repo::P_VALIDATE_REGEXP => '/^\d{5}$/'],
                ],
            'P_VALIDATE_CALLBACK field must pass callback validation' =>
                [
                    '*Color*',
                    'color',
                    'strong',
                    [Repo::P_LABEL => 'Color', Repo::P_VALIDATE_CALLBACK =>
                        function ($val, $label) {return $val == 'red' ? true : "value in '$label' is not a color";}
                    ],
                ],
            'C_ENUM item not an option' =>
                [
                    '*Operation*',
                    'op',
                    'insert',
                    [
                        Repo::P_LABEL => 'Operation',
                        Repo::P_REQUIRED => true,
                        Repo::P_CLASS => Repo::C_ENUM,
                        Repo::P_ITEMS => [
                            'user-ins' => 'insert user',
                            'user-upd' => 'update user',
                            'user-del' => 'delete user',
                        ],
                    ],
                ],
            'P_SET field must match P_ITEMS key(s)' =>
                [
                    '*Flags*',
                    'flags',
                    3,
                    [
                        Repo::P_LABEL => 'Flags',
                        Repo::P_REQUIRED => true,
                        Repo::P_CLASS => Repo::C_SET,
                        Repo::P_ITEMS => [1 => 'one', 2 => 'two'],
                    ],
                ],
            'C_TEXT field must be scalar' =>
                [
                    '*Code*',
                    'code',
                    [1 => 2],
                    [Repo::P_LABEL => 'Code', Repo::P_CLASS => Repo::C_TEXT],
                ],
        ];
    }

    /**
     * @covers ::validateInput
     * @dataProvider validateInputSuccessProvider
     */
    public function testValidateInputSuccess($expected, $field, $value, $repo)
    {
        $this->assertTrue(Repo::validateInput([
            $field => $repo,
        ], [
            $field => $value,
        ]));

        $this->assertChecker($expected, Repo::$validated[$field]);
    }

    public function validateInputSuccessProvider(): array
    {
        return [
            'C_ENUM: item key is present in list' => [
                'user-ins',
                'op',
                'user-ins',
                [
                    Repo::P_REQUIRED => true,
                    Repo::P_CLASS => Repo::C_ENUM,
                    Repo::P_ITEMS => [
                        'user-ins' => 'insert user',
                        'user-upd' => 'update user',
                        'user-del' => 'delete user',
                    ],
                ],
            ],
            'empty string in optional C_TEXT field formatted as null' => [
                null,
                'user_initials',
                '',
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_WIDTH => 10],
            ],
            'C_TEXT field formatted Uppercased First Chars' => [
                'Full Name',
                'user_fullname',
                'full name',
                [Repo::P_FLAGS => Repo::F_UCFIRST, Repo::P_WIDTH => 200],
            ],
            'C_TEXT field validated as email' => [
                'email+filter@domain.tld',
                'user_email',
                'email+filter@domain.tld',
                [Repo::P_FLAGS => Repo::F_EMAIL, Repo::P_WIDTH => 255],
            ],
            'C_TEXT field validated as URL' => [
                'https://www.linkedin.com/me?maybe#not',
                'user_profile',
                'https://www.linkedin.com/me?maybe#not',
                [Repo::P_FLAGS => Repo::F_URL, Repo::P_WIDTH => 255],
            ],
            'C_TEXT field formatted as telephone' => [
                '01 23 45 67 89',
                'user_tel',
                '  0  12345678  9 ',
                [Repo::P_FLAGS => Repo::F_TEL, Repo::P_WIDTH => 20],
            ],
            'F_TEXTAREA field kept inner whitespace formatting, formatted to F_UPPERCASE' => [
                "SOME TEXT ON\r\n   - MULTIPLE LINES, PADDED WITH WHITESPACES",
                'user_address',
                " some text on\r\n   - multiple lines, padded with whitespaces \t\r\n",
                [Repo::P_FLAGS => Repo::F_TEXTAREA | Repo::F_UPPERCASE, Repo::P_WIDTH => 2000],
            ],
            'C_DATE field formatted' =>
                ['2016-01-01', 'user_birthday', '1/1/16', [Repo::P_CLASS => Repo::C_DATE]],
            'C_CENTS field formatted' =>
                [12345, 'price', '123,45', [Repo::P_CLASS => Repo::C_CENTS]],
            'C_BOOL field is valid' =>
                [0, 'is_due', 'no', [Repo::P_CLASS => Repo::C_BOOL]],
            'P_VALIDATE_REGEXP field is valid' =>
                ['75001', 'postal', '75001', [Repo::P_VALIDATE_REGEXP => '/^\d{5}$/']],
            'P_VALIDATE_CALLBACK field is valid' => [
                'red',
                'color',
                'red',
                [Repo::P_VALIDATE_CALLBACK =>
                    function ($val, $label) {
                        return $val == 'red' ? true : "value in '$label' is not a color";
                    }
                ],
            ],
            'C_SET field formatted from string' => [
                'one,three',
                'flags_string',
                'one,three',
                [
                    Repo::P_CLASS => Repo::C_SET,
                    Repo::P_ITEMS => [
                        'one' => 'insert user',
                        'two' => 'update user',
                        'three' => 'delete user',
                    ],
                ],
            ],
            'C_SET field formatted from array' => [
                'two,three',
                'flags_array',
                ['two', 'three'],
                [
                    Repo::P_CLASS => Repo::C_SET,
                    Repo::P_ITEMS => [
                        'one' => 'insert user',
                        'two' => 'update user',
                        'three' => 'delete user',
                    ],
                ],
            ],
        ];
    }

    /**
     * @covers ::validateInput
     * @covers ::asHtmlStatic
     */
    public function testValidateInputFile()
    {
        $upload = [
            'name' => 'test.png',
            'type' => 'image/png',
            'size' => 32,
            'tmp_name' => '/tmp/uploaded-test.png',
            'error' => 0,
        ];
        $_FILES = [
            'upload' => $upload,
        ];

        $this->assertTrue(Repo::validateInput(['upload' => [Repo::P_CLASS => Repo::C_FILE]], []));

        $this->assertEquals($upload, Repo::$validated['upload'], 'C_FILE field corresponds to _FILES');
    }

    /**
     * @covers ::asHtmlStatic
     * @dataProvider asHtmlStaticProvider
     */
    public function testAsHtmlStatic($expected, $field, $value, $repo)
    {
        $this->assertEquals($expected, Repo::asHtmlStatic($field, $value, $repo));
    }

    public function asHtmlStaticProvider(): array
    {
        $text = "<Line1>\n<Line2>";
        $datetime = '2016-12-31 12:34:56';
        $file = [
            'name' => 'test&check.png',
            'type' => 'image/png',
            'size' => 32,
            'tmp_name' => '/tmp/uploaded-test.png',
            'error' => 0,
        ];

        return [
            'NULL values: empty string' =>
                ['', 'op', null, []],
            'P_CLASS not provided: return value as is' =>
                ['<Text>', 'op', '<Text>', []],

            'C_TEXT: html encode' =>
                ["&lt;Line1&gt;\n&lt;Line2&gt;", 'adress', $text, [Repo::P_CLASS => Repo::C_TEXT]],
            'C_TEXT + F_TEXTAREA: html encode + convert NL to BR' => [
                "&lt;Line1&gt;<br />\n&lt;Line2&gt;",
                'adress',
                $text,
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_TEXTAREA],
            ],

            'C_DATE: NLS converted' =>
                ['31/12/16', 'user_birthday', $datetime, [Repo::P_CLASS => Repo::C_DATE]],
            'C_DATE: NLS converted + time' =>
                ['31/12/16 12:34', 'user_birthday', $datetime, [Repo::P_CLASS => Repo::C_DATE, Repo::P_FLAGS => Repo::F_DATETIME]],

            'C_ENUM: empty string if no P_ITEMS' =>
                ['', 'op', 'ins', [Repo::P_CLASS => Repo::C_ENUM]],
            'C_ENUM: empty string if value not in P_ITEMS' =>
                ['', 'op', 'upd', [Repo::P_CLASS => Repo::C_ENUM, Repo::P_ITEMS => ['ins' => '<Insert>']]],
            'C_ENUM: html encode item value' =>
                ['&lt;Insert&gt;', 'op', 'ins', [Repo::P_CLASS => Repo::C_ENUM, Repo::P_ITEMS => ['ins' => '<Insert>']]],
            'C_ENUM + F_ASIS: item value as is' => [
                '<Insert>',
                'op',
                'ins',
                [
                    Repo::P_CLASS => Repo::C_ENUM,
                    Repo::P_FLAGS => Repo::F_ASIS,
                    Repo::P_ITEMS => ['ins' => '<Insert>'],
                ],
            ],
            'C_ENUM + F_ABBR: item value as abbreviation' => [
                '<abbr title="Insert item">&lt;I&gt;</abbr>',
                'op',
                'ins',
                [
                    Repo::P_CLASS => Repo::C_ENUM,
                    Repo::P_FLAGS => Repo::F_ABBR,
                    Repo::P_ITEMS_SHORT => ['ins' => '<I>'],
                    Repo::P_ITEMS => ['ins' => 'Insert'],
                    Repo::P_ITEMS_LONG => ['ins' => 'Insert item'],
                ],
            ],

            'C_SET: html encode values and delimiters' => [
                '&lt;I&gt;&nbsp;; &lt;D&gt;',
                'op',
                'i,d',
                [
                    Repo::P_CLASS => Repo::C_SET,
                    Repo::P_ITEMS => ['i' => '<I>', 'u' => '<U>', 'd' => '<D>'],
                ],
            ],
            'C_SET: item values and delimiters as is' => [
                '<I> ; <D>',
                'op',
                'i,d',
                [
                    Repo::P_CLASS => Repo::C_SET,
                    Repo::P_FLAGS => Repo::F_ASIS,
                    Repo::P_ITEMS => ['i' => '<I>', 'u' => '<U>', 'd' => '<D>'],
                ],
            ],

            'C_ID: integer' =>
                ['1&nbsp;000', 'id', '1000', [Repo::P_CLASS => Repo::C_ID]],
            'C_INT: integer' =>
                ['1 000', 'id', '1000', [Repo::P_CLASS => Repo::C_INT, Repo::P_FLAGS => Repo::F_ASIS]],
            'C_INT: non-integer as empty string' =>
                ['', 'id', 'abc', [Repo::P_CLASS => Repo::C_ID]],

            'C_CENTS: html integer with 2 decimals' =>
                ['1&nbsp;234,56', 'price', '123456', [Repo::P_CLASS => Repo::C_CENTS]],
            'C_CENTS: html integer with as much as 2 decimals' =>
                ['1&nbsp;234,5', 'price', '123450', [Repo::P_CLASS => Repo::C_CENTS, Repo::P_FLAGS => Repo::F_SHOW_COMPACT]],
            'C_CENTS: non-encoded integer without decimals' =>
                ['1 235', 'price', '123456', [Repo::P_CLASS => Repo::C_CENTS, Repo::P_FLAGS => Repo::F_HIDE_DECIMAL | Repo::F_ASIS]],

            'C_BOOL: true value' =>
                ['oui', 'is_due', true, [Repo::P_CLASS => Repo::C_BOOL]],
            'C_BOOL: false value with custom labels' =>
                ['&lt;off&gt;', 'is_due', false, [Repo::P_CLASS => Repo::C_BOOL, Repo::P_ITEMS => ['<off>', '<on>']]],

            'C_FILE: html filename' =>
                ['test&amp;check.png', 'upload', $file, [Repo::P_CLASS => Repo::C_FILE]],
            'C_FILE: non-encoded filename' =>
                ['test&check.png', 'upload', $file, [Repo::P_CLASS => Repo::C_FILE, Repo::P_FLAGS => Repo::F_ASIS]],

            'unknown P_CLASS: as is' =>
                ['U&I', 'unknown', 'U&I', [Repo::P_CLASS => 'unknown']],
        ];
    }

    /**
     * @covers ::asHtmlInput
     * @dataProvider asHtmlInputProvider
     */
    public function testAsHtmlInput($expected, $field, $value, $input, $repo)
    {
        $this->assertChecker($expected, Repo::asHtmlInput($field, $value, $input, $repo));
    }

    public function asHtmlInputProvider(): array
    {
        return [
            'class not provided: return as is' => [
                '<comment>',
                'comment',
                '<comment>',
                [],
                [],
            ],
            'C_TEXT input field' => [
                [
                    '*type="text"*',
                    '*maxlength="50"*',
                    '*name="firstname"*',
                    '*value="&lt;First-Name&gt;"*',
                ],
                'firstname',
                '<First-Name>',
                [],
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_WIDTH => 50],
            ],
            'C_TEXT + F_TEXTAREA input field' => [
                [
                    '*</textarea>*',
                    '*maxlength="250"*',
                    '*name="address"*',
                    "*1 Infinite Loop\nCupertino, CA*",
                ],
                'address',
                "1 Infinite Loop\nCupertino, CA",
                [],
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_TEXTAREA, Repo::P_WIDTH => 250],
            ],
            'C_TEXT + F_PASSWORD input field' => [
                [
                    '*type="password"*',
                    '*maxlength="50"*',
                    '*name="password"*',
                    '*value=""*',
                ],
                'password',
                '',
                [],
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_PASSWORD, Repo::P_WIDTH => 50],
            ],
            'C_TEXT + F_EMAIL input field' => [
                [
                    '*type="email"*',
                    '*maxlength="50"*',
                    '*name="email"*',
                    '*value="a@b.com"*',
                ],
                'email',
                'a@b.com',
                [],
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_EMAIL, Repo::P_WIDTH => 50],
            ],
            'C_TEXT + F_TEL input field + input tag attributes' => [
                [
                    '*id="152"*',
                    '*type="tel"*',
                    '*maxlength="50"*',
                    '*name="tel"*',
                    '*value="0123456789"*',
                ],
                'tel',
                '0123456789',
                ['id' => 152],
                [Repo::P_CLASS => Repo::C_TEXT, Repo::P_FLAGS => Repo::F_TEL, Repo::P_WIDTH => 50],
            ],
            'C_DATE input field (type text)' => [
                [
                    '*type="text"*',
                    '*name="user_birthday"*',
                    '*value="31/12/16"*',
                ],
                'user_birthday',
                '2016-12-31',
                ['type' => 'text'],
                [Repo::P_CLASS => Repo::C_DATE],
            ],
            'C_DATE input field (type date)' => [
                [
                    '*type="date"*',
                    '*name="user_birthday"*',
                    '*value="2016-12-31"*',
                ],
                'user_birthday',
                '2016-12-31',
                [],
                [Repo::P_CLASS => Repo::C_DATE],
            ],
            'C_ENUM as select dropdown with blank line' => [
                [
                    '*<select name="op">*',
                    '/<option[^>]*>---Select item---<\/option>/',
                    '*<option value="ins" selected="on">&lt;Insert&gt;</option>*',
                ],
                'op',
                'ins',
                [],
                [Repo::P_CLASS => Repo::C_ENUM, Repo::P_ITEM_BLANK => '---Select item---', Repo::P_ITEMS => ['ins' => '<Insert>', 'upd' => '<Update>']],
            ],
            'C_ENUM + F_ASIS as select dropdown' => [
                [
                    '*<select name="op">*',
                    '*<option value="ins" selected="on"><Insert></option>*',
                ],
                'op',
                'ins',
                [],
                [Repo::P_CLASS => Repo::C_ENUM, Repo::P_FLAGS => Repo::F_ASIS, Repo::P_ITEMS => ['ins' => '<Insert>', 'upd' => '<Update>']],
            ],
            'C_ENUM as radio buttons with delimiter' => [
                [
                    '*type="radio"*',
                    '*name="op"*',
                    '*value="ins" checked="on"*',
                    '*>#<*',
                ],
                'op',
                'ins',
                [],
                [Repo::P_CLASS => Repo::C_ENUM, Repo::P_ITEM_DELIM => '#', Repo::P_FLAGS => Repo::F_RADIO, Repo::P_ITEMS => ['ins' => '<Insert>', 'upd' => '<Update>']],
            ],
            'C_SET as a collection of 2 checkboxes' => [
                [
                    '*type="checkbox"*',
                    '/name="items\\[a]".+name="items\\[b]"/',
                    '*value="a"*',
                    '*&lt;A&gt;*',
                    '*>#<*',
                ],
                'items',
                '',
                [],
                [Repo::P_CLASS => Repo::C_SET, Repo::P_ITEM_DELIM => '#', Repo::P_ITEMS => ['a' => '<A>', 'b' => '<B>']],
            ],
            'C_SET + F_ASIS as a non-encoded checkbox' => [
                [
                    '*type="checkbox"*',
                    '*name="items[a]"*',
                    '*value="a"*',
                    '*checked="on"*',
                    '*<A>*',
                ],
                'items',
                'a',
                [],
                [Repo::P_CLASS => Repo::C_SET, Repo::P_FLAGS => Repo::F_ASIS, Repo::P_ITEMS => ['a' => '<A>']],
            ],
            'C_ID input field' => [
                [
                    '*type="number"*',
                    '*name="counter"*',
                    '*value="100"*',
                ],
                'counter',
                '100',
                [],
                [Repo::P_CLASS => Repo::C_ID],
            ],
            'C_INT input field' => [
                [
                    '*type="number"*',
                    '*name="counter"*',
                    '*value="100"*',
                ],
                'counter',
                '100',
                [],
                [Repo::P_CLASS => Repo::C_INT],
            ],
            'C_CENTS input field' => [
                [
                    '*type="number"*',
                    '*name="price"*',
                    '*value="123,45"*',
                ],
                'price',
                '12345',
                [],
                [Repo::P_CLASS => Repo::C_CENTS],
            ],
            'C_BOOL input checkbox (true)' => [
                [
                    '*type="checkbox"*',
                    '*name="is_due"*',
                    '*checked="on"*',
                ],
                'is_due',
                '1',
                [],
                [Repo::P_CLASS => Repo::C_BOOL],
            ],
            'C_BOOL input checkbox (false) + label' => [
                [
                    '*type="checkbox"*',
                    '*name="is_due"*',
                    '*Vrai*',
                ],
                'is_due',
                null,
                [],
                [Repo::P_CLASS => Repo::C_BOOL, Repo::P_ITEMS => ['Faux', 'Vrai']],
            ],
            'C_FILE input field' => [
                [
                    '*type="file"*',
                    '*name="upload"*',
                ],
                'upload',
                null,
                [],
                [Repo::P_CLASS => Repo::C_FILE],
            ],
            'unknown class: text field' => [
                [
                    '*type="text"*',
                    '*name="field"*',
                    '*value="undefined"*',
                ],
                'field',
                'undefined',
                [],
                [Repo::P_CLASS => 'default'],
            ],
        ];
    }

    /**
     * @covers ::enumToString
     */
    public function testEnumToString()
    {
        $this->assertEquals('&lt;Insert&gt;', Repo::enumToString('i', ['i' => '<Insert>']), 'item encoded');
        $this->assertEquals('<Insert>', Repo::enumToString('i', ['i' => '<Insert>'], false), 'item as is');
        $this->assertEquals('', Repo::enumToString('u', ['i' => '<Insert>'], false), 'missing item');
    }

    /**
     * @covers ::setToString
     */
    public function testSetToString()
    {
        $this->assertEquals(
            '&lt;I&gt;&nbsp;; &lt;D&gt;',
            Repo::setToString('i,d', ['i' => '<I>', 'u' => '<U>', 'd' => '<D>']),
            'items encoded'
        );
        $this->assertEquals(
            '<I> ; <D>',
            Repo::setToString('i,d', ['i' => '<I>', 'u' => '<U>', 'd' => '<D>'], false),
            'items as is'
        );
        $this->assertEquals(
            '<D>',
            Repo::setToString('a,b,c,d', ['i' => '<I>', 'u' => '<U>', 'd' => '<D>'], false),
            'missing items'
        );
    }

    /**
     * @covers ::validatePct100
     */
    public function testValidatePct100()
    {
        // correct values
        $this->assertTrue(Repo::validatePct100(0, 'Name'));
        $this->assertTrue(Repo::validatePct100(1, 'Name'));
        $this->assertTrue(Repo::validatePct100(99, 'Name'));
        $this->assertTrue(Repo::validatePct100(100, 'Name'));
        $this->assertTrue(Repo::validatePct100('0', 'Name'));
        $this->assertTrue(Repo::validatePct100('100', 'Name'));

        // incorrect values
        $this->assertStringContainsString('Name', Repo::validatePct100(-1, 'Name'), 'under 0');
        $this->assertStringContainsString('Name', Repo::validatePct100(101, 'Name'), 'over 100');
        $this->assertStringContainsString('Name', Repo::validatePct100('x', 'Name'), 'not numeric');
        $this->assertStringContainsString('Name', Repo::validatePct100('', 'Name'), 'empty');
    }

    /**
     * @covers ::asSql
     */
    public function testAsSql()
    {
        // P_CLASS:C_ID|C_INT
        $this->assertEquals(DbBeforeClass::C_SECTION . '=1', Repo::asSql(DbBeforeClass::C_SECTION, 1));
        $this->assertNull(Repo::asSql(DbBeforeClass::C_SECTION, null));
        $this->assertFalse(Repo::asSql(DbBeforeClass::C_SECTION, 'x'));
        $this->assertNull(Repo::asSql(DbBeforeClass::C_SECTION, ''));

        // P_CLASS:C_CENTS
        $this->assertEquals("price=123", Repo::asSql('price', 1.23, [Repo::P_CLASS => Repo::C_CENTS]));
        $this->assertNull(Repo::asSql('price', null, [Repo::P_CLASS => Repo::C_CENTS]));
        $this->assertFalse(Repo::asSql('price', 'x', [Repo::P_CLASS => Repo::C_CENTS]));
        $this->assertNull(Repo::asSql('price', '', [Repo::P_CLASS => Repo::C_CENTS]));

        // P_CLASS:C_BOOL
        $this->assertEquals('is_open', Repo::asSql('is_open', true, [Repo::P_CLASS => Repo::C_BOOL]));
        $this->assertEquals('not is_open', Repo::asSql('is_open', false, [Repo::P_CLASS => Repo::C_BOOL]));
        $this->assertNull(Repo::asSql('is_open', null, [Repo::P_CLASS => Repo::C_BOOL]));
        $this->assertEquals('is_open', Repo::asSql('is_open', 'x', [Repo::P_CLASS => Repo::C_BOOL]));
        $this->assertEquals('not is_open', Repo::asSql('is_open', '', [Repo::P_CLASS => Repo::C_BOOL]));

        if (!Db::getConnection()) {
            self::markTestSkipped('Must be connected to DB for sql escaping');
        }

        // P_CLASS:C_DATE
        $this->assertEquals("queued='2016-12-31'", Repo::asSql('queued', '31/12/2016', [Repo::P_CLASS => Repo::C_DATE]));
        $this->assertEquals("queued='2016-12-31 00:00:00'", Repo::asSql('queued', '31/12/2016', [Repo::P_CLASS => Repo::C_DATE, Repo::P_FLAGS => Repo::F_DATETIME]));
        $this->assertNull(Repo::asSql('queued', null, [Repo::P_CLASS => Repo::C_DATE]));
        $this->assertFalse(Repo::asSql('queued', 'x', [Repo::P_CLASS => Repo::C_DATE]));
        $this->assertNull(Repo::asSql('queued', '', [Repo::P_CLASS => Repo::C_DATE]));

        // P_CLASS:C_SET
        $this->assertEquals("find_in_set('wed',dow)", Repo::asSql('dow', 'wed', [Repo::P_CLASS => Repo::C_SET]));
        $this->assertNull(Repo::asSql('dow', null, [Repo::P_CLASS => Repo::C_SET]));
        $this->assertEquals("find_in_set('',dow)", Repo::asSql('dow', '', [Repo::P_CLASS => Repo::C_SET]));

        // P_CLASS:C_TEXT
        $this->assertEquals(DbBeforeClass::C_NAME . "='1'", Repo::asSql(DbBeforeClass::C_NAME, 1));
        $this->assertNull(Repo::asSql(DbBeforeClass::C_NAME, null));
        $this->assertEquals(DbBeforeClass::C_NAME . "='x'", Repo::asSql(DbBeforeClass::C_NAME, 'x'));
        $this->assertEquals(DbBeforeClass::C_NAME . "=''", Repo::asSql(DbBeforeClass::C_NAME, ''));
    }

    /**
     * @covers ::asSqlInt
     */
    public function testAsSqlInt()
    {
        $this->assertEquals('num=-1', Repo::asSqlInt('num', -1));
        $this->assertEquals('num=0', Repo::asSqlInt('num', 0));
        $this->assertEquals('num=1', Repo::asSqlInt('num', 1));
        $this->assertNull(Repo::asSqlInt('num', null));
        $this->assertFalse(Repo::asSqlInt('num', 'x'));
        $this->assertNull(Repo::asSqlInt('num', ''));
    }

    /**
     * @covers ::asSqlBool
     */
    public function testAsSqlBool()
    {
        $this->assertEquals('is_open', Repo::asSqlBool('is_open', true));
        $this->assertEquals('not is_open', Repo::asSqlBool('is_open', false));
        $this->assertNull(Repo::asSqlBool('is_open', null));
        $this->assertEquals('is_open', Repo::asSqlBool('is_open', 'x'));
        $this->assertEquals('not is_open', Repo::asSqlBool('is_open', ''));
    }

    /**
     * @covers ::asSqlText
     */
    public function testAsSqlText()
    {
        if (!Db::getConnection()) {
            self::markTestSkipped('Must be connected to DB for sql escaping');
        }

        $this->assertEquals("description='term'", Repo::asSqlText('description', 'term'));
        $this->assertEquals("description=''", Repo::asSqlText('description', ''));
        $this->assertNull(Repo::asSqlText('description', null));
    }

    /**
     * @covers ::asSqlSet
     */
    public function testAsSqlSet()
    {
        if (!Db::getConnection()) {
            self::markTestSkipped('Must be connected to DB for sql escaping');
        }

        $this->assertEquals("find_in_set('wed',dow)", Repo::asSqlSet('dow', 'wed'));
        $this->assertEquals("(find_in_set('sat',dow)or find_in_set('sun',dow))", Repo::asSqlSet('dow', ['sat', 'sun']));
        $this->assertEquals("(find_in_set('sat',dow)and find_in_set('sun',dow))", Repo::asSqlSet('dow', ['sat', 'sun'], true));
        $this->assertNull(Repo::asSqlSet('dow', null));
    }

    /**
     * @covers ::asSqlDate
     */
    public function testAsSqlDate()
    {
        if (!Db::getConnection()) {
            self::markTestSkipped('Must be connected to DB for sql escaping');
        }

        $repo = [
            Repo::P_CLASS => Repo::C_DATE,
            Repo::P_LABEL => 'Date queued',
        ];

        // date value
        $this->assertEquals("queued='2016-12-31'", Repo::asSqlDate('queued', '31/12/2016', $repo));
        $this->assertEquals("queued>'2016-12-31'", Repo::asSqlDate('queued', '>31/12/2016', $repo));
        $this->assertEquals("queued<'2016-12-31'", Repo::asSqlDate('queued', '< 31/12/2016', $repo));
        $this->assertEquals("queued between'2016-01-01'and'2016-12-31'", Repo::asSqlDate('queued', '1/1/2016 - 31/12/2016', $repo));

        // datetime value
        $this->assertEquals("queued='2016-12-31 00:00:00'", Repo::asSqlDate('queued', '31/12/2016', $repo + [Repo::P_FLAGS => Repo::F_DATETIME]));
        $this->assertEquals("queued>'2016-12-31 00:00:00'", Repo::asSqlDate('queued', '> 31/12/2016', $repo + [Repo::P_FLAGS => Repo::F_DATETIME]));
        $this->assertEquals("queued<'2016-12-31 00:00:00'", Repo::asSqlDate('queued', '<31/12/2016', $repo + [Repo::P_FLAGS => Repo::F_DATETIME]));
        $this->assertEquals("queued between'2016-01-01 00:00:00'and'2016-12-31 23:59:59'", Repo::asSqlDate('queued', '1/1/2016 - 31/12/2016', $repo + [Repo::P_FLAGS => Repo::F_DATETIME]));

        // empty value
        $this->assertNull(Repo::asSqlDate('queued', null, $repo));
        $this->assertNull(Repo::asSqlDate('queued', '0', $repo));
        $this->assertNull(Repo::asSqlDate('queued', '', $repo));

        // not a date value
        $this->assertFalse(Repo::asSqlDate('queued', 'not a date', $repo));
    }
}
