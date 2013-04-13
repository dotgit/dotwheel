<?php

/**
 * defines DbEntity class which represents a basic db entity and serves as a base
 * for other application classes. implements operations on EAV tables.
 *
 * [type: framework]
 *
 * @author stas trefilov
 * @see http://en.wikipedia.org/wiki/Entity-attribute-value_model
 */

namespace dotwheel\db;

require_once (__DIR__.'/Db.class.php');
require_once (__DIR__.'/Db2.class.php');
require_once (__DIR__.'/../util/Cache.class.php');
require_once (__DIR__.'/../util/Nls.class.php');

use dotwheel\util\Cache;
use dotwheel\util\Nls;

class DbEntity
{
	const TEXT_TABLE = 'texts';
	const TEXT_ID_FIELD = 'tt_id';
	const TEXT_CONTENT_FIELD = 'tt_text';
	const TEXT_SMALL_LIMIT = 255;

	public static $errors = array();
	public static $last_count = 0;

	/** @var string entity table prefix */
	public static $prefix;
	/** @var string entity table name */
	static protected $table;
	/** @var string entity table primary key field name */
	static protected $id_field;
	/** @var string attributes table name */
	static protected $params_table;
	/** @var string entity table primary key field name in attributes table */
	static protected $params_id_field;
	/** @var string attribute name field name */
	static protected $params_name_field;
	/** @var string attribute value field name */
	static protected $params_value_field;
	/** @var string list of fields to select from entity table in <i>static::load_by_where()</i> */
	static protected $sql_fields = '*';
	/** @var string list of tables to join with an entity table in <i>static::load_by_where()</i> */
	static protected $sql_from;
	/** @var string list of fields to select from entity table in <i>static::get_by_id_short()</i> */
	static protected $sql_fields_short = 'null';
	/** @var string list of tables to join with an entity table in <i>static::get_by_id_short()</i> */
	static protected $sql_from_short;
	/** @var array hash of main attributes stored directly in the entity table with their wrap types */
	static protected $base_fields = array();
	/** @var array hash of attributes stored in the attributes table */
	static protected $params_fields = array();
	/** @var array hash of text attributes stored in the attributes table */
	static protected $text_fields = array();

	/** @var string sprintf-formatted SQL sentence to read the row from the entity table */
	static protected $sql_fmt_5 = 'select %s%s from %s%s where %s';



	// READ OPERATIONS

	/** runs a SQL query to retrieve the entity. if the corresponding row is found and
	 * <i>$add_fields</i> is empty then cache the found entity for future use
	 * @param string $where WHERE clause for the entity table
	 * @param string $add_fields CSV list of additional fields to read from the entity,
	 * must start with a comma
	 * @return array|bool returns loaded entity
	 */
	public static function loadByWhere($where, $add_fields=null)
	{
		$entity = isset(static::$params_table)
			? Db2::load_entity_attributes(array('sql'=>sprintf(static::$sql_fmt_5
					, static::$sql_fields, $add_fields, static::$table, static::$sql_from, $where
					)
				, 'id_field'=>static::$id_field
				, 'sql_params_fmt'=>'select '.static::$params_name_field.', rtrim('.static::$params_value_field.')'
					. ' from '.static::$params_table
					. ' where '.static::$params_id_field.' = %u'
				, 'params_fields'=>static::$params_fields
				, 'text_fields'=>static::$text_fields
				))
			: Db::fetchRow(sprintf(static::$sql_fmt_5
				, static::$sql_fields, $add_fields, static::$table, static::$sql_from, $where
				))
			;
		if ($entity and empty($add_fields))
			Cache::store(static::$prefix.':'.$entity[static::$id_field], $entity);

		return $entity;
	}

	/** if <i>$add_fields</i> is empty and row is cached, return from cache, otherwise load
	 * from db via <i>statis::load_by_where()</i>
	 * @param int $id primary key value of the entity
	 * @param string $add_fields CSV list of additional fields to read from the entity,
	 * must start with a comma
	 * @see self::load_by_where()
	 */
	public static function getById($id, $add_fields=null)
	{
		return ($add_fields or($entity = Cache::fetch(static::$prefix.':'.$id)) === null)
			? static::loadByWhere(static::$id_field.' = '.(int)$id, $add_fields)
			: $entity
			;
	}

	/** returns a cached entity or runs a simplified SQL statement(using only
	 * <i>static::$sql_fields_short</i> fields) and returns the found row without caching it
	 * @param int $id primary key value of the entity
	 * @return array returns found row or a complete entity if it was in the cache
	 */
	public static function getByIdShort($id)
	{
		if (($entity = Cache::fetch(static::$prefix.':'.$id)) !== null)
			return $entity;

		return Db::fetchRow(sprintf(static::$sql_fmt_5
			, static::$sql_fields_short, '', static::$table, static::$sql_from_short, static::$id_field.' = '.(int)$id
			));
	}

	/** @return array returns nullified <i>static::$base_fields</i> */
	public static function getBaseFields()
	{
		return array_fill_keys(array_keys(static::$base_fields), null);
	}

	/** @return array returns nullified <i>static::$params_fields</i> */
	public static function getParamsFields()
	{
		return array_fill_keys(array_keys(static::$params_fields), null);
	}

	/** remove specified object from cache thus forcing object reload on next request
	 * @param string|array $id entity id or a list of entity ids to invalidate
	 */
	public static function invalidate($id)
	{
		if (is_array($id))
			foreach ($id as $obj_id)
				Cache::delete(static::$prefix.':'.$obj_id);
		else
			Cache::delete(static::$prefix.':'.$id);

		return true;
	}

	/** creates a columns list as a CSV string for the SQL statement. normally used together with
	 * <i>static::sql_join_attributes()</i>
	 * @param array $data hash of attribute fields
	 * @return string returns a list of attribute fields for SQL select statement
	 * @example $data = array('mn_finder'=>null, 'mn_contact'=>null)<br>
	 * result: 'mn_finder.mnp_value mn_finder, mn_contact.mnp_value mn_contact'
	 * @see self::sql_join_attributes()
	 */
	public static function sqlFieldsAttributes($data=null)
	{
		$j = array();
		foreach (isset($data) ? array_intersect_key(static::$params_fields, $data) : static::$params_fields as $name=>$_)
			$j[] = "$name.".static::$params_value_field." $name";

		return implode(', ', $j);
	}

	/** creates a JOIN clause of a SQL sentence to allow the specified params fields
	 * in the list of columns(only joins those <i>$params['params_fields']</i> fields that appear
	 * in <i>$params['data']</i>). normally used together with <i>static::sql_fields_attributes()</i>
	 * @param array $params a hash of parameters:<br>
	 * <li>data: original entity values loaded from the database
	 * <li>params_table: parameters table name
	 * <li>params_fields: a hash indicating which fields are external attributes
	 * <li>main_id_field: the name of entity id field from the main table
	 * <li>id_field: the name of entity id field from the parameters table
	 * <li>name_field: the name of attribute field from the parameters table
	 * @return string a JOIN clause of a SQL sentence to load the specified attributes with the main table
	 */
	public static function sqlJoinAttributes($params)
	{
		$params += array('params_table'=>static::$params_table
			, 'params_fields'=>static::$params_fields
			, 'main_id_field'=>static::$id_field
			, 'id_field'=>static::$params_id_field
			, 'name_field'=>static::$params_name_field
			);
		$j = array();
		foreach (isset($params['data'])
			? array_intersect_key($params['params_fields'], $params['data'])
			: $params['params_fields']
			as $name=>$_
			)
			$j[] = " left join {$params['params_table']} $name"
				. " on {$params['main_id_field']} = $name.{$params['id_field']}"
					. " and $name.{$params['name_field']} = '$name'"
				;

		return implode('', $j);
	}

	/** loads an entity from a database by issuing one SQL statement to select a row in main table,
	 * the second to select attributes and the third(via <i>self::load_text_attributes()</i>) to load
	 * large text values
	 * @param array $params a hash of parameters:<br>
	 * <li>sql: select statement for a main table
	 * <li>sql_params_fmt: sprintf-formatted select statement for attributes table(with %u for entity id)
	 * <li>id_field: entity id field name
	 * <li>params_fields: a hash indicating which fields are external attributes
	 * <li>text_fields: a hash indicating which fields are text
	 */
	public static function loadEntityAttributes($params)
	{
		if ($res = Db::fetchRow($params['sql']))
		{
			$params_null = array_fill_keys(array_keys($params['params_fields']), null);
			if (isset($params['text_fields']))
			{
				if ($t = array_intersect_key($res, $params['text_fields']))
				{
					$t2 = array();
					foreach ($t as $t_name=>$txt)
						if ($txt[0] === ' ')
							$t2[$t_name] = (int)$txt;
					if ($t2)
					{
						$texts = Db::fetchList(sprintf('select %s, %s from %s where %s in(%s)'
							, self::TEXT_ID_FIELD
							, self::TEXT_CONTENT_FIELD
							, self::TEXT_TABLE
							, self::TEXT_ID_FIELD
							, implode(',', $t2)
							));
						foreach ($t2 as $t_name=>$t_id)
							$res[$t_name] = isset($texts[$t_id]) ? $texts[$t_id] : null;
					}
				}

				$res += $params_null;
			}
			else
				$res += Db::fetchList(sprintf($params['sql_params_fmt'], $res[$params['id_field']]))
					+ $params_null
					;
		}

		return $res;
	}



	// DML OPERATIONS

	/**	create new entity, do not store in cache, will be loaded when necessary
	 * @param array $input hash with entity values
	 * @return int|bool returns new entity id or <i>false</i> on error
	 */
	public static function insert($input)
	{
		// create main object
		if (Db2::insert(array('table'=>static::$table
			, 'wrap_fields'=>static::$base_fields
			, 'input'=>array_intersect_key($input, static::$base_fields)
			)))
		{
			$id = Db::insertId();

			// save object params
			if (isset(static::$params_table))
				self::replace(array('input'=>array_intersect_key($input, static::$params_fields)
					, 'original'=>static::getParamsFields()
					, 'table'=>static::$table
					, 'id_field'=>static::$id_field
					, 'id_value'=>$id
					, 'wrap_fields'=>array()
					, 'text_fields'=>static::$text_fields
					, 'params_table'=>static::$params_table
					, 'params_fields'=>static::$params_fields
					, 'params_id_field'=>static::$params_id_field
					, 'params_name_field'=>static::$params_name_field
					, 'params_value_field'=>static::$params_value_field
					));

			return $id;
		}
		else
		{
			static::$errors[] = dgettext(Nls::FW_DOMAIN, 'Error inserting into the database');
			return false;
		}
	}

	/** update entity, refresh cache
	 * @param array $input hash with new entity values
	 * @param array $original hash with current entity values
	 */
	public static function update($input, $original=array())
	{
		if (empty($original))
			$original = static::getById($input[static::$id_field]);

		if ((self::replace(array('input'=>array_intersect_key($input, static::$base_fields + static::$params_fields)
			, 'original'=>$original
			, 'table'=>static::$table
			, 'id_field'=>static::$id_field
			, 'id_value'=>$input[static::$id_field]
			, 'wrap_fields'=>static::$base_fields
			, 'text_fields'=>static::$text_fields
			, 'params_table'=>static::$params_table
			, 'params_fields'=>static::$params_fields
			, 'params_id_field'=>static::$params_id_field
			, 'params_name_field'=>static::$params_name_field
			, 'params_value_field'=>static::$params_value_field
			))) !== false)
		{
			// invalidate cache
			static::invalidate($input[static::$id_field]);

			return true;
		}
		else
		{
			static::$errors[] = dgettext(Nls::FW_DOMAIN, 'Error updating the database');
			return false;
		}
	}

	/**
	 * @param array $params a hash of parameters:<br>
	 * <li>name: field name
	 * <li>value: new value
	 * <li>original: old value
	 * <li>params_table: params table name
	 * <li>id_field: id field in params table
	 * <li>id_value: original object id
	 * <li>name_field: name field name in params table
	 * <li>value_field: value field name in params table
	 * @return array a hash of SQL statements<br>
	 * <li>ins: insert values
	 * <li>upd: set statement
	 * <li>del: delete condition
	*/
	public static function updateTextAttribute($params)
	{
		$ins = array();
		$upd = array();
		$del = array();
		$name = $params['name'];
		$value = $params['value'];
		$original = $params['original'];
		$len_o = mb_strlen($original, Nls::$charset);
		$len = mb_strlen($value, Nls::$charset);

		if ($len_o <= self::TEXT_SMALL_LIMIT and $len <= self::TEXT_SMALL_LIMIT)
		{
			// store inside the params table
			if ($value !== null)
				$ins[] = sprintf("(%u,'%s',%s)", $params['id_value'], $name, Db::wrap($value));
			else
				$del[] = "'$name'";
		}
		elseif ($len_o <= self::TEXT_SMALL_LIMIT)
		{
			// move from params to texts table
			Db::dml(sprintf('insert into %s(%s) values(%s)'
				, self::TEXT_TABLE
				, self::TEXT_CONTENT_FIELD
				, Db::wrap($value)
				));
			if ($id = Db::insertId())
				$ins[] = sprintf("(%u,'%s',' %u')", $params['id_value'], $name, $id);
		}
		elseif ($len <= self::TEXT_SMALL_LIMIT)
		{
			// move from texts to params table
			Db::dml(sprintf('delete %s'
				. " from %s join %s on %s = '%s' and %s = %s"
				. ' where %s = %u'
				, self::TEXT_TABLE
				, $params['params_table']
				, self::TEXT_TABLE
				, $params['name_field']
				, $name
				, $params['value_field']
				, self::TEXT_ID_FIELD
				, $params['id_field']
				, $params['id_value']
				));
			if ($value !== null)
				$ins[] = sprintf("(%u,'%s',%s)", $params['id_value'], $name, Db::wrap($value));
			else
				$del[] = "'$name'";
		}
		else
		{
			// update texts table
			Db::dml(sprintf("update %s join %s on %s = '%s' and %s = %s"
				. ' set %s = %s'
				. ' where %s = %u'
				, $params['params_table']
				, self::TEXT_TABLE
				, $params['name_field']
				, $name
				, $params['value_field']
				, self::TEXT_ID_FIELD
				, self::TEXT_CONTENT_FIELD
				, Db::wrap($value)
				, $params['id_field']
				, $params['id_value']
				));
		}

		return array('ins'=>$ins, 'upd'=>$upd, 'del'=>$del);
	}

	/** replaces an object in a database by inserting new lines in parameters table, deleting empty lines
	 * from parameters table and updating the line in main table(text fields are also handled via the
	 * <i>update_text_field()</i> call)
	 * @param array $params a hash of parameters:<br>
	 * <li>original: {fld1: value, ...}
	 * <li>input: {fld1: value, ...}
	 * <li>wrap_fields: {fld1: WRAP_ALPHA|WRAP_NUM|null, ...}
	 * <li>table: ...
	 * <li>id_field: ...
	 * <li>id_value: integer
	 * <li>where: ...
	 * <li>params_table: ...
	 * <li>params_fields: {fld1: true, ...}
	 * <li>params_id_field: ...
	 * <li>params_name_field: ...
	 * <li>params_value_field: ...
	 * <li>text_fields: {fld1: true, ...}
	 * @return int|bool number of successful DML operations to replace or <i>false</i> on error
	 * @see self::update_text_field()
	 */
	public static function replace($params)
	{
		$ins = array();
		$del = array();
		$upd = array();

		foreach ($params['input'] as $name=>$value)
		{
			if (array_key_exists($name, $params['original']) and strcmp($params['original'][$name], $value) != 0)
			{
				if (isset($params['params_fields'][$name]))
				{
					if (isset($params['text_fields'][$name]))
					{
						$_ = self::updateTextAttribute(array('name'=>$name
							, 'value'=>$value
							, 'original'=>$params['original'][$name]
							, 'params_table'=>$params['params_table']
							, 'id_field'=>$params['params_id_field']
							, 'id_value'=>$params['id_value']
							, 'name_field'=>$params['params_name_field']
							, 'value_field'=>$params['params_value_field']
							));
						if ($_['ins'])
							$ins = array_merge($ins, $_['ins']);
						if ($_['upd'])
							$upd = array_merge($upd, $_['upd']);
						if ($_['del'])
							$del = array_merge($del, $_['del']);
					}
					elseif ($value !== null)
						$ins[] = sprintf("(%u,'%s',%s)", $params['id_value'], $name, Db::wrap($value));
					else
						$del[] = "'$name'";
				}
				else
				{
					switch ($params['wrap_fields'][$name])
					{
						case Db::WRAP_ALPHA: $upd[] = "$name = ".Db::wrap($value); break;
						case Db::WRAP_NUM: $upd[] = "$name = ".Db::escape($value); break;
						default: $upd[] = "$name = $value";
					}
				}
			}
		}

		$res_d = $del
			? Db::dml(sprintf('delete from %s where %s = %u and %s in(%s)'
				, $params['params_table']
				, $params['params_id_field']
				, $params['id_value']
				, $params['params_name_field']
				, implode(',', $del)
				))
			: 0
			;
		$res_i = $ins
			? Db::dml(sprintf('insert into %s(%s, %s, %s)'
				. ' values %s'
				. ' on duplicate key update %s = values(%s)'
				, $params['params_table']
				, $params['params_id_field']
				, $params['params_name_field']
				, $params['params_value_field']
				, implode(',', $ins)
				, $params['params_value_field']
				, $params['params_value_field']
				))
			: 0
			;
		$res_u = $upd
			? Db::dml(sprintf('update %s set %s where %s'
				, $params['table']
				, implode(', ', $upd)
				, isset($params['where'])
					? $params['where']
					: "{$params['id_field']} = {$params['id_value']}"
				))
			: 0
			;
		if ($res_d === false or $res_i === false or $res_u === false)
			return false;
		else
			return $res_d + $res_i + $res_u;
	}
}
