<?php

/**
 * sharding implementation
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\db;

require_once (__DIR__.'/Db.class.php');
require_once (__DIR__.'/../util/Params.class.php');

use dotwheel\db\Db;
use dotwheel\util\Params;

class DbShard extends Db
{
    /** connection params */
    const CNX_HOST          = 1;
    const CNX_USERNAME      = 2;
    const CNX_PASSWORD      = 3;
    const CNX_DATABASE      = 4;
    const CNX_CHARSET       = 5;
    const CNX_HOST_READ     = 11;
    const CNX_HOST_WRITE    = 12;

    /** connect to read-only replica */
    const MODE_READ     = 1;
    /** connect to read/write replica */
    const MODE_WRITE    = 2;

    /** @var array list of application shards by shard name */
    public static $shards = array();
    /** @var string last connected host */
    public static $current_host;
    /** @var string current mode (MODE_READ | MODE_WRITE) */
    public static $current_mode;
    /** @var array list of current db connections by shard name / access mode */
    public static $connections = array();



    /** initialize application shards
     * @param array $shards list of available shards in format
     *  {'shard1':{MODE_READ:[{CNX_HOST:'localhost'
     *              , CNX_USERNAME:'root'
     *              , CNX_PASSWORD:null
     *              , CNX_DATABASE:null
     *              , CNX_CHARSET:'UTF8'
     *              }
     *          ,{<server 2>}
     *          ,{<server 3>}
     *          ]
     *      , MODE_WRITE:[{<server 1>},...]
     *      }
     *  , 'shard2':{MODE_READ:[{<server 1>},...]
     *      , MODE_WRITE:[{<server 1>},...]
     *      }
     *  , ...
     *  }
     */
    public static function init($shards)
    {
        self::$shards = $shards;
    }

    /** switch to specified shard, connect if selected host parameters differ from currently used
     * @param type $shard_name
     * @param type $access_mode
     */
    public static function useShard($shard_name, $access_mode=self::MODE_READ)
    {
        // select access mode
        if (isset($access_mode)
            and self::$current_mode != $access_mode
            and ($access_mode == self::MODE_READ || $access_mode == self::MODE_WRITE)
            )
            self::$current_mode = $access_mode;
        elseif (empty(self::$current_mode))
            self::$current_mode = self::MODE_READ;

        // connect to the specified shard or use an existing connection
        if (empty(self::$connections[$shard_name]) || empty(self::$connections[$shard_name][self::$current_mode]))
        {
            $host = self::selectHost(self::$shards[$shard_name][self::$current_mode]);
            if ($host == self::$current_host)
                self::$connections[$shard_name][self::$current_mode] = parent::$conn;
            else
            {
                self::$current_host = $host;
                self::$connections[$shard_name][self::$current_mode] = self::connect(Params::extract($host, self::CNX_HOST, 'localhost')
                    , Params::extract($host, self::CNX_USERNAME, 'root')
                    , Params::extract($host, self::CNX_PASSWORD, null)
                    , Params::extract($host, self::CNX_DATABASE, null)
                    , Params::extract($host, self::CNX_CHARSET, 'UTF8')
                    );
            }
        }
        else
            parent::$conn = self::$connections[$shard_name][self::$current_mode];

        return parent::$conn;
    }

    /** given the list of available hosts select one to connect to
     * @param array $hosts  array of available hosts
     * @return array        selected host
     */
    public static function selectHost($hosts)
    {
        return $hosts[array_rand($hosts)];
    }
}
