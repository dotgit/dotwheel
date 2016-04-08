<?php

/**
 * sharding implementation
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Db;

use Dotwheel\Util\Params;

class DbShard extends Db
{
    /** connection params */
    const CNX_HOST      = 1;
    const CNX_USERNAME  = 2;
    const CNX_PASSWORD  = 3;
    const CNX_DATABASE  = 4;
    const CNX_CHARSET   = 5;

    /** shard modes */
    const MODE_READ     = 1;
    const MODE_WRITE    = 2;

    /** internal connection enum */
    const ENUM_HOST = 1;
    const ENUM_CNX  = 2;

    /** @var array list of all available application shards by shard name */
    public static $shards = array();

    /** @var array list of current db connections by shard name / access mode */
    public static $connections = array();

    /** @var string current host */
    public static $current_host = array();

    /** initializes application shards
     *
     * @param array $shards list of available shards in format
     *  {'shard1':{
     *      MODE_READ:[
     *          {
     *              CNX_HOST:'localhost',
     *              CNX_USERNAME:'root',
     *              CNX_PASSWORD:null,
     *              CNX_DATABASE:null,
     *              CNX_CHARSET:'UTF8',
     *          },
     *          {<server 2>},
     *          {<server 3>},
     *      ],
     *      MODE_WRITE:[{<server 1>},...]
     *  },
     *  'shard2':{
     *      MODE_READ:[{<server 1>},...],
     *      MODE_WRITE:[{<server 1>},...],
     *  }, ...}
     */
    public static function init($shards)
    {
        self::$shards = $shards;
    }

    /** switches to specified shard, connects if selected host parameters differ
     * from currently used
     *
     * @param string $shard_name    shard name
     * @param integer $access_mode  MODE_READ | MODE_WRITE | null
     * @return mixed connection ressource or <i>false</i> on error
     */
    public static function open($shard_name, $access_mode = null)
    {
        // select access mode
        if ($access_mode != self::MODE_WRITE && $access_mode != self::MODE_READ) {
            $access_mode = isset(self::$connections[$shard_name][self::MODE_WRITE])
                ? self::MODE_WRITE
                : self::MODE_READ;
        }

        // check shard configured
        if (empty(self::$shards[$shard_name][$access_mode])) {
            return false;
        }

        // open new connection if needed and store in connections array
        if (empty(self::$connections[$shard_name][$access_mode])) {
            $shard = self::selectHost(self::$shards[$shard_name][$access_mode]);
            $host = Params::extract($shard, self::CNX_HOST);
            self::$connections[$shard_name][$access_mode] = array(self::ENUM_HOST=>$host);
            self::$connections[$shard_name][$access_mode][self::ENUM_CNX] = ($host == self::$current_host)
                ? parent::$conn
                : self::connect(
                    $host,
                    Params::extract($shard, self::CNX_USERNAME),
                    Params::extract($shard, self::CNX_PASSWORD),
                    Params::extract($shard, self::CNX_DATABASE),
                    Params::extract($shard, self::CNX_CHARSET, 'UTF8')
                );
        }

        // remember current connection host
        self::$current_host = self::$connections[$shard_name][$access_mode][self::ENUM_HOST];

        // set Db::$conn
        parent::$conn = self::$connections[$shard_name][$access_mode][self::ENUM_CNX];

        return parent::$conn;
    }

    /** given the list of available host structures selects one to connect to
     *
     * @param array $hosts  array of available hosts
     * @return array        selected host structure
     */
    public static function selectHost($hosts)
    {
        return $hosts[\array_rand($hosts)];
    }
}
