<?php

/**
 * less frequently used db functions
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\db;

require_once (__DIR__.'/Db.class.php');

use dotwheel\db\Db;

class Db2
{
	/** constructs and executes a DML command to insert a row in the specified table. <i>wrap_fields</i>
	 * parameter specifies the type of escaping for each field(for example, WRAP_ALPHA means escape
	 * the value and wrap it in apostrophes)
	 *
	 * @param array $params	{input:{fld1:value1,...}
	 * , table:'table_name'
	 * , wrap_fields:{fld1:WRAP_ALPHA|WRAP_NUM|null,...}
	 * , duplicates:true(whether to include the <i>'on duplicate key update'</i> part)
	 * @return int|bool		number of affected records or false on error
	 */
	public static function insert($params)
	{
		$ins = array();
		$dupl = array();
		foreach ($params['input'] as $name=>$value)
		{
			switch ($params['wrap_fields'][$name])
			{
				case Db::WRAP_ALPHA: $ins[$name] = Db::wrap($value); break;
				case Db::WRAP_NUM: $ins[$name] = Db::escape($value); break;
				default: $ins[$name] = $value;
			}
			$dupl[$name] = "$name = values($name)";
		}
		$on_dupl = isset($params['duplicates'])
			? (' on duplicate key update '.implode(',', array_intersect_key($dupl, $params['duplicates'])))
			: ''
			;

		return Db::dml(sprintf("insert into %s(%s) values(%s)%s"
			, $params['table']
			, implode(',', array_keys($ins))
			, implode(',', array_values($ins))
			, $on_dupl
			));
	}

	/** constructs and executes a DML command to update a row in the specified table. <i>wrap_fields</i>
	 * parameter specifies the type of escaping for each field(for example, WRAP_ALPHA means escape
	 * the value and wrap it in apostrophes). to locate a row you may indicate a <i>where</i> parameter or
	 * set the id_field and id_value parameters.
	 *
	 * @param array $params {input:{fld1:value1,...}
	 * , table:'table_name'
	 * , wrap_fields:{fld1:WRAP_ALPHA|WRAP_NUM|null,...}
	 * , where:'a is not null'
	 * , id_field: (primary key field name, not checked if <i>where</i> is set)
	 * , id_value: (primary key value, not checked if <i>where</i> is set)
	 * @return int|bool		number of affected records or false on error
	 */
	public static function update($params)
	{
		$upd = array();
		foreach ($params['input'] as $name=>$value)
		{
			switch ($params['wrap_fields'][$name])
			{
				case Db::WRAP_ALPHA: $upd[] = "$name = ".Db::wrap($value); break;
				case Db::WRAP_NUM: $upd[] = "$name = ".Db::escape($value); break;
				default: $upd[] = "$name = $value";
			}
		}

		return $upd
			? Db::dml(sprintf('update %s set %s where %s'
				, $params['table']
				, implode(', ', $upd)
				, isset($params['where'])
					? $params['where']
					: "{$params['id_field']} = {$params['id_value']}"
				))
			: 0
			;
	}

	/** dml operation to exchange the position of two lines
	 * @param array $params		{table:'application_experiences'
	 * 	, main_id_field:'ane_an_id'
	 * 	, id_field:'ane_id'
	 * 	, pos_field:'ane_pos'
	 * 	, main_id_value:$an_id
	 * 	, id_value:$ane_id
	 * 	, op:'u'|'d'
	 * 	}
	 * @return int|bool		number of affected records or false on error
	 */
	public static function changePos($params)
	{
		// get ids of all the items(a small number for a given application)
		foreach ($all = Db::fetchArray("select {$params['id_field']}, {$params['pos_field']}"
			. " from {$params['table']}"
			. " where {$params['main_id_field']} = ".(int)$params['main_id_value']
			. " order by {$params['pos_field']}"
			) as $i=>$row)
		{
			if ($row[$params['id_field']] == $params['id_value'])
				$current = $i;
		}

		// exit if not found
		if (! isset($current))
			return true;

		// handle operations
		switch ($params['op'])
		{
		case 'u':
			if (! $current)
				return true;
			$another = $all[$current-1];
			break;
		default:
			if ($current + 1 == count($all))
				return true;
			$another = $all[$current+1];
		}

		// dml to change *_pos values
		return Db::dml(sprintf('update %s'
			. ' set %s = if (%s = %u, %u, %u)'
			. ' where %s = %u'
				. ' and %s in(%u, %u)'
			, $params['table']
			, $params['pos_field'], $params['id_field']
			, $params['id_value'], $another[$params['pos_field']], $all[$current][$params['pos_field']]
			, $params['main_id_field'], $params['main_id_value']
			, $params['id_field'], $another[$params['id_field']], $all[$current][$params['id_field']]
			));
	}

	/** tries to lock a <i>$token</i>
	 * @param string $token the name of the token(must be properly escaped)
	 * @param int $ttl max nbr of seconds to spend trying
	 * @return bool whether the lock is obtained
	 */
	public static function lockGet($token, $ttl=10)
	{
		$locked = Db::fetchRow("select get_lock('$token', $ttl) locked");

		return (bool)$locked['locked'];
	}

	/** sees whether the token is already locked
	 * @param string $token the name of the token(must be properly escaped)
	 * @return bool whether the token is locked by someone
	 */
	public static function lockIsUsed($token)
	{
		$is_locked = Db::fetchRow("select is_used_lock('$token') is_locked");

		return (bool)$is_locked['is_locked'];
	}

	/** releases a locked token
	 * @param string $token the name of the token(must be properly escaped)
	 */
	public static function lockRelease($token)
	{
		return Db::dml("do release_lock('$token')");
	}
}
