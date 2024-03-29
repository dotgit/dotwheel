<?php

namespace Dotwheel\Db;

use DbBeforeClass;

/**
 * @coversDefaultClass Db
 * @requires extension mysqli
 */
class DbTest extends DbBeforeClass
{
    /**
     * @covers ::connect
     */
    public function testConnect()
    {
        // connection test is done in DbPrecondition::setUpBeforeClass()
    }

    /**
     * @covers ::fetchRow
     */
    public function testFetchRow()
    {
        $row = Db::fetchRow(
            sprintf(
                "select %s from %s where %s = 1 and %s = 3",
                self::C_NAME, self::TABLE, self::C_SECTION, self::C_ID
            )
        );
        $this->assertArrayHasKey(self::C_NAME, $row);
        $this->assertEquals('third one', $row[self::C_NAME]);
    }

    /**
     * @covers ::fetchRowDEBUG
     */
    public function testFetchRowDEBUG()
    {
    }

    /**
     * @covers ::fetchList
     */
    public function testFetchList()
    {
        $list = Db::fetchList(
            sprintf(
                "select %s, %s from %s where %s = 2 order by 1",
                self::C_ID, self::C_NAME, self::TABLE, self::C_SECTION
            )
        );
        $this->assertNotEmpty($list);
        $this->assertCount(3, $list);
        $this->assertEquals('third two', $list[3]);
    }

    /**
     * @covers ::fetchListDEBUG
     */
    public function testFetchListDEBUG()
    {
    }

    /**
     * @covers ::fetchHash
     */
    public function testFetchHash()
    {
        $hash = Db::fetchHash(
            sprintf(
                "select * from %s where %s = 3 order by 1",
                self::TABLE, self::C_SECTION
            ),
            self::C_ID
        );
        $this->assertNotEmpty($hash);
        $this->assertCount(3, $hash);
        $this->assertEquals('third three', $hash[3][self::C_NAME]);
    }

    /**
     * @covers ::fetchHashDEBUG
     */
    public function testFetchHashDEBUG()
    {
    }

    /**
     * @covers ::fetchArray
     */
    public function testFetchArray()
    {
        $array = Db::fetchArray(
            sprintf(
                "select * from %s where %s = 1 order by 1",
                self::TABLE, self::C_SECTION
            )
        );
        $this->assertNotEmpty($array);
        $this->assertCount(3, $array);
        $this->assertEquals('first one', $array[0][self::C_NAME]);
    }

    /**
     * @covers ::fetchArrayDEBUG
     */
    public function testFetchArrayDEBUG()
    {
    }

    /**
     * @covers ::fetchCsv
     */
    public function testFetchCsv()
    {
        $csv = Db::fetchCsv(
            sprintf(
                "select %s from %s where %s = 2 order by %s",
                self::C_NAME, self::TABLE, self::C_SECTION, self::C_ID
            )
        );
        $this->assertNotEmpty($csv);
        $this->assertEquals('first two,second two,third two', $csv);
    }

    /**
     * @covers ::fetchCsvDEBUG
     */
    public function testFetchCsvDEBUG()
    {
    }

    /**
     * @covers ::handlerReadPrimary
     */
    public function testHandlerReadPrimary()
    {
        $row = Db::handlerReadPrimary(self::TABLE, [1, 3]);
        $this->assertArrayHasKey(self::C_NAME, $row);
        $this->assertEquals('third one', $row[self::C_NAME]);
    }

    /**
     * @covers ::handlerReadIndex
     */
    public function testHandlerReadIndex()
    {
        $row = Db::handlerReadIndex(self::TABLE, '`PRIMARY`', [1, 2]);
        $this->assertArrayHasKey(self::C_NAME, $row);
        $this->assertEquals('second one', $row[self::C_NAME]);
    }

    /**
     * @covers ::handlerReadPrimaryMulti
     */
    public function testHandlerReadPrimaryMulti()
    {
        $rows = Db::handlerReadPrimaryMulti(self::TABLE, [[1, 1], [2, 2], [3, 3]]);
        $this->assertCount(3, $rows);
        $this->assertEquals('third three', $rows['3,3'][self::C_NAME]);
    }

    /**
     * @covers ::handlerReadIndexMulti
     */
    public function testHandlerReadIndexMulti()
    {
        $rows = Db::handlerReadIndexMulti(self::TABLE, self::IDX_ID, [[1, "'first two'"], [3, "'third one'"]]);
        $this->assertCount(2, $rows);
        $this->assertEquals(2, $rows["1,'first two'"][self::C_SECTION]);
    }

    /**
     * @covers ::dml
     * @covers ::fetchCsv
     */
    public function testDml()
    {
        $dml = Db::dml(
            sprintf(
                <<<EOsql
                insert into %s (%s, %s, %s)
                values
                (4, 1, 'first four'),
                (4, 2, 'second four'),
                (4, 3, 'third four')
                EOsql,
                self::TABLE, self::C_SECTION, self::C_ID, self::C_NAME
            )
        );
        $this->assertGreaterThan(2, $dml, 'more than two rows inserted');

        $csv = Db::fetchCsv(
            sprintf(
                "select %s from %s where %s = 4 order by %s",
                self::C_NAME, self::TABLE, self::C_SECTION, self::C_ID
            )
        );
        $this->assertNotEmpty($csv);
        $this->assertEquals('first four,second four,third four', $csv);
    }

    /**
     * @covers ::dmlDEBUG
     */
    public function testDmlDEBUG()
    {
    }

    /**
     * @covers ::dmlBind
     * @covers ::fetchCsv
     */
    public function testDmlBind()
    {
        $dml = Db::dmlBind(
            sprintf(
                <<<EOsql
                insert into %s (%s, %s, %s)
                values
                (?, ?, ?),
                (?, ?, ?),
                (?, ?, ?)
                EOsql,
                self::TABLE, self::C_SECTION, self::C_ID, self::C_NAME
            ),
            'iisiisiis',
            [
                5, 1, 'first five',
                5, 2, 'second five',
                5, 3, 'third five',
            ]
        );
        $this->assertGreaterThan(2, $dml, 'more than two rows inserted');

        $csv = Db::fetchCsv(
            sprintf(
                "select %s from %s where %s = 5 order by %s",
                self::C_NAME, self::TABLE, self::C_SECTION, self::C_ID
            )
        );
        $this->assertNotEmpty($csv);
        $this->assertEquals('first five,second five,third five', $csv);
    }

    /**
     * @covers ::dmlBindDEBUG
     */
    public function testDmlBindDEBUG()
    {
    }

    /**
     * @covers ::insertId
     * @covers ::dml
     */
    public function testInsertId()
    {
        $dml1 = Db::dml(
            sprintf(
                <<<EOsql
                create temporary table if not exists t2 (
                    %s integer unsigned not null auto_increment primary key,
                    %s varchar(255) not null
                )
                select %s, %s from %s where %s = 5 order by 1
                EOsql,
                self::C_ID,
                self::C_NAME,
                self::C_ID, self::C_NAME, self::TABLE, self::C_SECTION
            )
        );
        $this->assertNotEmpty($dml1);

        $dml2 = Db::dml(sprintf("insert into t2 (%s) values ('fourth five')", self::C_NAME));
        $this->assertNotEmpty($dml2);

        $id = Db::insertId();
        $this->assertEquals(4, $id);
    }

    /**
     * @covers ::escapeInt
     */
    public function testEscapeInt()
    {
        $this->assertEquals('null', Db::escapeInt(null));
        $this->assertEquals(0, Db::escapeInt(0));
        $this->assertEquals(4, Db::escapeInt(4));
        $this->assertEquals(0, Db::escapeInt('not-integer'));
    }

    /**
     * @covers ::escapeIntCsv
     */
    public function testEscapeIntCsv()
    {
        $this->assertEquals('null', Db::escapeIntCsv(null));
        $this->assertEquals(0, Db::escapeIntCsv(0));
        $this->assertEquals('1,2,3', Db::escapeIntCsv([1, 2, 3]));
        $this->assertEquals('1,3', Db::escapeIntCsv([1, null, 3, 'xxx']));
        $this->assertEquals('null', Db::escapeIntCsv(['xxx']));
        $this->assertEquals(0, Db::escapeIntCsv('xxx'));
    }

    /**
     * @covers ::wrapChar
     */
    public function testWrapChar()
    {
        $this->assertEquals('null', Db::wrapChar(null));
        $this->assertEquals("'value'", Db::wrapChar('value'));
        $this->assertEquals("'o\\'value'", Db::wrapChar("o'value"));
        $this->assertEquals("'o\\nvalue'", Db::wrapChar("o\nvalue"));
    }

    /**
     * @covers ::wrapCharCsv
     */
    public function testWrapCharCsv()
    {
        $this->assertEquals('null', Db::wrapCharCsv(null));
        $this->assertEquals("'value'", Db::wrapCharCsv('value'));
        $this->assertEquals(
            "'value','o\\'value','o\\nvalue'",
            Db::wrapCharCsv(['value', "o'value", "o\nvalue"])
        );
        $this->assertEquals(
            'null',
            Db::wrapCharCsv([])
        );
    }
}
