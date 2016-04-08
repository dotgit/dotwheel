<?php

use Dotwheel\Db\Db;

/**
 * Implements setUpBeforeClass method for descendants
 * Connects to the temp database, creates temporary table, inserts some lines
 * @requires PHP 5.4
 * @requires extension mysqli
 */
class DbBeforeClass extends PHPUnit_Framework_TestCase
{
    const HOST  = null;
    const USER  = null;
    const PASS  = null;
    const DB    = 'test';

    const TABLE     = 'test';
    const IDX_ID    = 'idx_id';
    const C_SECTION = 'tt_section';
    const C_ID      = 'tt_id';
    const C_NAME    = 'tt_name';

    /**
     * @coversNothing
     * @uses ::connect
     * @uses ::dml
     */
    public static function setUpBeforeClass()
    {
        // connect
        self::assertNotEmpty(Db::connect(self::HOST, self::USER, self::PASS, self::DB));

        // create temporary table
        Db::dml(sprintf(
<<<EOsql
create temporary table if not exists %s (
  %s integer unsigned not null,
  %s integer unsigned not null,
  %s varchar(255) not null,
  primary key(%s, %s),
  unique key %s (%s, %s)
)
EOsql
            ,
            self::TABLE,
            self::C_SECTION,
            self::C_ID,
            self::C_NAME,
            self::C_SECTION, self::C_ID,
            self::IDX_ID, self::C_ID, self::C_NAME
        ));

        // insert initial lines
        Db::dml(sprintf(
<<<EOsql
insert into %s (%s, %s, %s)
values
(1, 1, 'first one'),
(1, 2, 'second one'),
(1, 3, 'third one'),
(2, 1, 'first two'),
(2, 2, 'second two'),
(2, 3, 'third two'),
(3, 1, 'first three'),
(3, 2, 'second three'),
(3, 3, 'third three')
EOsql
            ,
            self::TABLE,
            self::C_SECTION,
            self::C_ID,
            self::C_NAME
        ));
    }
}
