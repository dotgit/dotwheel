<?php

namespace Dotwheel\Db;

use DbBeforeClass;

/**
 * @coversDefaultClass Db2
 * @requires extension mysqli
 */
class Db2Test extends DbBeforeClass
{
    /**
     * @covers ::insert
     */
    public function testInsert()
    {
        $this->assertEquals(
            1,
            Db2::insert([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_SECTION => Db2::FMT_NUM,
                    self::C_ID => Db2::FMT_NUM,
                    self::C_NAME => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 1,
                    self::C_NAME => 'FOURTH 1',
                ],
            ]),
            "normal insert of (4,1,'FOURTH 1')"
        );

        $this->assertFalse(
            Db2::insert([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_SECTION => Db2::FMT_NUM,
                    self::C_ID => Db2::FMT_NUM,
                    self::C_NAME => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 1,
                    self::C_NAME => 'fourth one',
                ],
            ]),
            "error on inserting of another (4,1,'fourth one')"
        );

        $this->assertEquals(
            0,
            Db2::insert([
                Db2::P_IGNORE => true,
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_SECTION => Db2::FMT_NUM,
                    self::C_ID => Db2::FMT_NUM,
                    self::C_NAME => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 1,
                    self::C_NAME => 'fourth one',
                ],
            ]),
            "insert ignore of (4,1,'fourth one')"
        );

        $this->assertEquals(
            2,
            Db2::insert([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_SECTION => Db2::FMT_NUM,
                    self::C_ID => Db2::FMT_NUM,
                    self::C_NAME => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 1,
                    self::C_NAME => 'fourth one',
                ],
                Db2::P_DUPLICATES => array(
                    self::C_NAME => Db::wrapChar('fourth one'),
                ),
            ]),
            "insert ... on duplicate key update of (4,1,'fourth one')->(4,2,'fourth two')"
        );

        $this->assertEquals(
            1,
            Db2::insert([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_SECTION => Db2::FMT_NUM,
                    self::C_ID => Db2::FMT_NUM,
                    self::C_NAME => Db2::FMT_ALPHA,
                    'extra_field' => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 2,
                    self::C_NAME => 'fourth two',
                ],
            ]),
            "extra field ignored inserting (4,2,'fourth two')"
        );

        $this->assertEquals(
            1,
            Db2::insert([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_SECTION => Db2::FMT_NUM,
                    self::C_ID => Db2::FMT_NUM,
                    self::C_NAME => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 3,
                    self::C_NAME => 'fourth three',
                    'extra_field' => 'ignored value',
                ],
            ]),
            "extra values ignored inserting (4,3,'fourth three')"
        );

        $this->assertEquals(
            0,
            Db2::insert([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    'x' => Db2::FMT_NUM,
                    'y' => Db2::FMT_NUM,
                    'z' => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_SECTION => 4,
                    self::C_ID => 3,
                    self::C_NAME => 'fourth three',
                ],
            ]),
            "error inserting without corresponding P_FIELDS"
        );
    }

    /**
     * @covers ::update
     * @uses Db::fetchRow
     */
    public function testUpdate()
    {
        $this->assertEquals(
            1,
            Db2::update([
                Db2::P_TABLE => self::TABLE,
                Db2::P_FIELDS => [
                    self::C_NAME => Db2::FMT_ALPHA,
                ],
                Db2::P_VALUES => [
                    self::C_NAME => 'FOURTH 1',
                ],
                Db2::P_WHERE => sprintf(
                    '%s = %u and %s = %u',
                    self::C_SECTION, 4,
                    self::C_ID, 1
                ),
            ]),
            "normal update of (4,1)->'FOURTH 1'"
        );
        $row = Db::fetchRow(
            sprintf(
                'select * from %s where %s = %u and %s = %u',
                self::TABLE, self::C_SECTION, 4, self::C_ID, 1
            )
        );
        $this->assertArrayHasKey(self::C_NAME, $row, 'row (4,1) is returned');
        $this->assertEquals('FOURTH 1', $row[self::C_NAME], 'name in row (4,1) is FOURTH 1');
    }

    /**
     * @covers ::blobEncode
     * @covers ::blobDecode
     */
    public function testBlobEnDecode()
    {
        // small structure
        $original = ['a' => 11, 'b' => 22, 'c' => 33];
        $blob = Db2::blobEncode($original);
        $this->assertEquals(' j:{"a":11,"b":22,"c":33}', $blob, 'small structure encoded as json');
        $restored = Db2::blobDecode($blob);
        $this->assertEquals($original, $restored, 'restored small structure same as original');

        // large structure
        $original = array_fill(1, 100, 'some value');
        $blob = Db2::blobEncode($original);
        $length = strlen($blob);
        $this->assertGreaterThan(100, $length, 'large structure encoded as binary string');
        $restored = Db2::blobDecode($blob);
        $this->assertEquals($original, $restored, 'restored large structure same as original');

        // too large structure
        $blob = Db2::blobEncode($original, $length - 1);
        $this->assertNull($blob, 'too large structure returns null');
    }

    /**
     * @covers ::lockGet
     * @covers ::lockIsUsed
     * @covers ::lockRelease
     */
    public function testLockGet()
    {
        $token = 'token_' . rand(100, 999);
        $this->assertTrue(Db2::lockGet($token), 'lock is acquired');
        $this->assertTrue(Db2::lockIsUsed($token), 'token is locked');
        $this->assertFalse(Db2::lockIsUsed("another_$token"), 'token is not locked');
        $this->assertTrue(Db2::lockRelease($token), 'token is released');
        $this->assertFalse(Db2::lockIsUsed($token), 'token is not locked');
    }

    /**
     * @covers ::nextSequence
     */
    public function testNextSequence()
    {
        $this->assertEquals(1, Db2::nextSequence([], 255), 'first element');
        $this->assertEquals(2, Db2::nextSequence([1], 255), 'second element');
        $this->assertEquals(4, Db2::nextSequence([1, 3], 255), 'next element');
        $this->assertEquals(4, Db2::nextSequence([1, 2, 3, 100, 255], 255), 'fill gaps from beginning');
    }
}
