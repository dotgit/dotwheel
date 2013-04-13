<?php

/**
 * miscellanous functions without special attribution to other classes
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

require_once (__DIR__.'/Nls.class.php');

class Misc
{
	/** decodes previously encoded blob value
	 * @param type $blob	encoded blob value
	 * @return string		decoded blob value
	 */
	public static function blobDecode($blob)
	{
		switch (substr($blob, 0, 3))
		{
			case 'sr:': return unserialize(substr($blob, 3));
			case 'gz:': return unserialize(gzinflate(substr($blob, 3)));
			default: return substr($blob, 1);
		}
	}

	/** serialises and compresses the passed value
	 * @param type $blob	value to encode
	 * @return string		encoded blob value
	 */
	public static function blobEncode($blob)
	{
		if (!is_scalar($blob))
		{
			$blob = serialize($blob);
			$blob = strlen($blob) > 252
				? ('gz:'.gzdeflate($blob, 1))
				: "sr:$blob"
				;
		}
		else
			$blob = ":$blob";

		return strlen($blob) <= 65535 ? $blob : null;
	}

	/** converts the proposed size to from K, M or G form to bytes
	 * @param string $size_str	string with size representation(128M, 2G etc.)
	 * @return int				size in bytes
	 */
	public static function convertSize($size_str)
	{
		switch (substr($size_str, -1))
		{
			case 'M': case 'm': return (int)$size_str * 1048576;
			case 'K': case 'k': return (int)$size_str * 1024;
			case 'G': case 'g': return (int)$size_str * 1073741824;
			default: return $size_str;
		}
	}

	/** assembles standard address representation from different parts. resulting
	 * address is in the following form:
	 * [street]
	 * [postal] [city] [country]
	 * @param type $street	address street
	 * @param type $postal	address postal code
	 * @param type $city	address city
	 * @param type $country	address country
	 * @return string		standard address representation
	 */
	public static function formatAddress($street, $postal, $city, $country)
	{
		return self::joinWs(array("\n", $street, array(' ', $postal, $city, $country)));
	}

	/** html-formatted string with some bb-style formatting convertion
	 * @param string $text	bb-style formatted string(recognizes *bold*, /italic/,
	 *						---header lines---, lines started with dash are bulleted)
	 * @return string
	 * @see Snippets::preview_txt()
	 */
	public static function formatPreview($text)
	{
		return preg_replace(array('#&#', '#<#', '#>#'
				, '#/([^/\r\n]*)/#m', '#\*([^*\\r\\n]*)\*#m'
				, '#^#m', '#$#m'
				, '#^<p>---(.*)---</p>$#m'
				, '#^<p>-(.*)</p>$#m'
				)
			, array('&amp;', '&lt;', '&gt;'
				, '<i>$1</i>', '<b>$1</b>'
				, '<p>', '</p>'
				, '<h5>$1</h5>'
				, '<li>$1</li>'
				)
			, $text
			);
	}

	/** return the formatted tel number or the original string
	 * @param string $tel
	 * @return string
	 */
	public static function formatTel($tel)
	{
		$t = str_replace(array(' ', '.', '-', '(0)'), '', $tel);
		if (preg_match('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m))
			return "$m[1] $m[2] $m[3] $m[4] $m[5]";
		elseif (preg_match('/^\+?\(?(\d{2})\)?(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m))
			return "+$m[1] $m[2] $m[3] $m[4] $m[5] $m[6]";
		else
			return $tel;
	}

	public static function getMaxUploadSize()
	{
		return min(self::convertSize(ini_get('upload_max_filesize')), self::convertSize(ini_get('post_max_size')));
	}

	public static function joinWs($params=array())
	{
		$elements = array();
		$splitter = '';

		foreach ($params as $k=>$v)
		{
			if ($k)
			{
				if (isset($v))
				{
					if (is_scalar($v))
						$elements[] = $v;
					elseif (($v = self::joinWs($v)) !== null)
						$elements[] = $v;
				}
			}
			else
				$splitter = $v;
		}
		if (is_scalar($splitter))
			return $elements ? implode($splitter, $elements) : null;
		elseif ($elements)
			return $splitter[0].implode($splitter[1], $elements).$splitter[2];
		else
			return null;
	}

	/** inject a new attributes into the list of attributes or add new value to the existing attribute
	 * @param array &$params	array of attributes
	 * @param string $value		attribute value
	 * @param string $name		attribute name
	 * @param string $sep		separator of attribute values
	 */
	public static function paramAdd(&$params, $value, $name='class', $sep=' ')
	{
		if (! is_array($params))
			$params = array($name=>$value);
		elseif (empty($params[$name]))
			$params[$name] = $value;
		elseif (strpos("$sep{$params[$name]}$sep", "$sep$value$sep") === false)
			$params[$name] .= "$sep$value";
	}

	/** returns the value of the specified attribute and unsets it in the attributes array
	 * @param array $params		array of attributes
	 * @param string $name		attribute name
	 * @param mixed $default	default value to return if $params[$name] is not set
	 * @return mixed
	 */
	public static function paramExtract(&$params, $name, $default=null)
	{
		if (! is_array($params) or ! array_key_exists($name, $params))
			return $default;

		$ret = $params[$name];
		unset($params[$name]);

		return $ret;
	}

	/** escapes a string to be used in sprintf by doubling the % characters
	 * @param string $str	string to escape
	 * @return string
	 */
	public static function sprintfEscape($str)
	{
		return str_replace('%', '%%', $str);
	}

	/** trims string to the specified length by word boundary adding suffix
	 * @param string $str		string to trim
	 * @param int $len			maximal trimmed string lenth
	 * @param string $suffix	suffix to add
	 * @return string			trimmed string
	 */
	public static function trimWord($str, $len=100, $suffix='...')
	{
		return mb_substr($str
			, 0
			, $len - mb_strlen(mb_strrchr(mb_substr($str, 0, $len, Nls::$charset), ' ', false, Nls::$charset), Nls::$charset)
			, Nls::$charset
			).$suffix
			;
	}

	/** checks whether the value represents a valid email in simplified form
	 * @param string $email		email address to validate
	 * @return string|bool		returns validated email or false
	 */
	public static function validateEmail($email)
	{
		$atom = '[^()<>@,;:\\\\".\\[\\] \\x00-\\x1f\\x80-\\xff]';
		return preg_match("/^$atom+(?:\.$atom+)*@$atom+(?:\.$atom+)*\$/", $email)
			? mb_strtolower($val, Nls::$charset)
			: false
			;
	}

	/** checks whether the value represents a valid url
	 * @param string $url	url to validate
	 * @return string|bool	returns validated url or false
	 */
	public static function validateUrl($url)
	{
		$safe = '-$.+';
		$extra = '!*\'(),';
		$more = ';:@&=';
		$escape = '%[\da-fA-F][\da-fA-F]';
		$unreserved = "[$safe$extra\w]";
		$uchar = "(?:[$safe$extra\w]|$escape)";
		$hsegment = "(?:[$safe$extra$more\w]|$escape)";
		$schema_re = '\w+:\/\/';
		$user_re = '\w+(?::\w+)@';
		$host_re = '[\w-]+(?:\.[\w-]+)+';
		$port_re = ':\d+';
		$search = "$hsegment*";
		$path_re = "\/(?:$search(?:\/$search)*)(?:\?$search)?";

		if (preg_match("/^($schema_re)?(?:$user_re)?$host_re(?:$port_re)?(?:$path_re)?$/", $url, $matches))
		{
			$url = (isset($matches[1]) and strtolower($matches[1]) == 'http://')
				? substr($url, strlen($matches[1]))
				: $url
				;
			if (substr($url, -1) == '/')
				$url = substr($url, 0, -1);
		}
		else
			return false;
	}
}
