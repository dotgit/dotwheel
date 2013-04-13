<?php

/**
 * some useful algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

class Algo
{
	/** tests the string has a valid luhn checksum. used for SIRET checks.
	 * @param string $num_str	string representing the tested number
	 * @return bool				whether the string passes
	 * @see http://fr.wikipedia.org/wiki/Luhn
	 * @see http://fr.wikipedia.org/wiki/Num%C3%A9ro_Interne_de_Classement
	 */
	public static function luhn($num_str)
	{
		$sum = 0;
		for ($i = 0, $l = strlen($num_str); $i < $l; ++$i)
		{
			$k = ($i & 1)
				? (int)$num_str[$l-$i-1] << 1
				: (int)$num_str[$l-$i-1]
				;
			if ($k > 9)
				$k -= 9;
			$sum += $k;
		}

		return $sum % 10 == 0;
	}

	/** tests the string has a valid mod97 checksum. used for IBAN checks.
	 * @param string $num_str	string representing the tested number
	 * @return bool				whether the string passes
	 * @see http://fr.wikipedia.org/wiki/Luhn
	 * @see http://fr.wikipedia.org/wiki/Num%C3%A9ro_Interne_de_Classement
	 */
	public static function mod97($num_str)
	{
		$ai = 1;
		$sum = 0;
		$len = strlen($num_str);
		for ($i = 0; $i < $len; ++$i)
		{
			$sum += ($num_str[$len-$i-1] * $ai);
			$ai = ($ai * 10) % 97;
		}

		return $sum % 97;
	}
}
