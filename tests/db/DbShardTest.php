<?php

namespace Dotwheel\Db;

use DbBeforeClass;

/**
 * @coversDefaultClass DbShard
 * @requires extension mysqli
 */
class DbShardTest extends DbBeforeClass
{
    public static string $shard_users = 'shard_users_';
    public static string $shard_contracts = 'shard_contracts_';
    public static string $shard_storage = 'shard_storage_';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$shard_users .= rand(100, 999);
        self::$shard_contracts .= rand(100, 999);
        self::$shard_storage .= rand(100, 999);
    }

    /**
     * @covers ::init
     */
    public function testInit()
    {
        $shards = [
            self::$shard_users => [
                DbShard::MODE_WRITE => [[
                    DbShard::CNX_HOST => self::HOST,
                    DbShard::CNX_USERNAME => self::USER,
                    DbShard::CNX_PASSWORD => self::PASS,
                    DbShard::CNX_DATABASE => DbBeforeClass::DB,
                    DbShard::CNX_CHARSET => 'utf8',
                ]],
                DbShard::MODE_READ => [[
                    DbShard::CNX_HOST => self::HOST,
                    DbShard::CNX_USERNAME => self::USER,
                    DbShard::CNX_PASSWORD => self::PASS,
                    DbShard::CNX_DATABASE => DbBeforeClass::DB,
                    DbShard::CNX_CHARSET => 'utf8',
                ]],
            ],
            self::$shard_contracts => [
                DbShard::MODE_WRITE => [[
                    DbShard::CNX_HOST => self::HOST,
                    DbShard::CNX_USERNAME => self::USER,
                    DbShard::CNX_PASSWORD => self::PASS,
                    DbShard::CNX_DATABASE => DbBeforeClass::DB,
                    DbShard::CNX_CHARSET => 'utf8',
                ]],
                DbShard::MODE_READ => [[
                    DbShard::CNX_HOST => self::HOST,
                    DbShard::CNX_USERNAME => self::USER,
                    DbShard::CNX_PASSWORD => self::PASS,
                    DbShard::CNX_DATABASE => DbBeforeClass::DB,
                    DbShard::CNX_CHARSET => 'utf8',
                ]],
            ],
            self::$shard_storage => [
                DbShard::MODE_WRITE => [[
                    DbShard::CNX_HOST => self::HOST,
                    DbShard::CNX_USERNAME => self::USER,
                    DbShard::CNX_PASSWORD => self::PASS,
                    DbShard::CNX_DATABASE => DbBeforeClass::DB,
                    DbShard::CNX_CHARSET => 'utf8',
                ]],
                DbShard::MODE_READ => [[
                    DbShard::CNX_HOST => self::HOST,
                    DbShard::CNX_USERNAME => self::USER,
                    DbShard::CNX_PASSWORD => self::PASS,
                    DbShard::CNX_DATABASE => DbBeforeClass::DB,
                    DbShard::CNX_CHARSET => 'utf8',
                ]],
            ],
        ];
        DbShard::init($shards);
        $this->assertArrayHasKey(self::$shard_storage, DbShard::$shards, 'shards list initialized');
        DbShard::$current_shard = $shards[self::$shard_users][DbShard::MODE_READ][0];
    }

    /**
     * @covers ::open
     * @uses Db::fetchList
     */
    public function testOpen()
    {
        $this->assertFalse(DbShard::open('inexistant-shard'), 'error on inexistant shard');

        $this->assertNotEmpty(DbShard::open(self::$shard_users, DbShard::MODE_READ), 'check read-only shard');
        $this->assertCount(
            3,
            Db::fetchList(
                sprintf(
                    'select %s, %s from %s where %s = %u order by 2',
                    self::C_ID, self::C_NAME, self::TABLE, self::C_SECTION, 1
                )
            )
        );

        $this->assertNotEmpty(DbShard::open(self::$shard_users, DbShard::MODE_WRITE), 'check read-write shard');
        $this->assertCount(
            3,
            Db::fetchList(
                sprintf(
                    'select %s, %s from %s where %s = %u order by 2',
                    self::C_ID, self::C_NAME, self::TABLE, self::C_SECTION, 1
                )
            )
        );
    }

    /**
     * @covers ::selectHost
     */
    public function testSelectHost()
    {
        $hosts = [
            '127.0.0.1',
            'localhost',
            '192.168.0.1',
            '172.16.0.1',
            '10.0.0.1',
        ];
        $this->assertContains(DbShard::selectHost($hosts), $hosts, 'one of the hosts selected');
    }
}
