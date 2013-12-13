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
    /** tests the string has a valid luhn checksum. used for SIRET and CC checks.
     * @param string $num_str   string representing the tested number
     * @return bool             whether the string passes
     * @see http://fr.wikipedia.org/wiki/Luhn
     * @see http://fr.wikipedia.org/wiki/Num%C3%A9ro_Interne_de_Classement
     * @see http://rosettacode.org/wiki/Luhn_test_of_credit_card_numbers#PHP
     */
    public static function luhn($num_str)
    {
        $str = '';
        foreach (\array_reverse(\str_split($num_str)) as $i=>$c)
            $str .= ($i % 2 ? $c * 2 : $c );

        return \array_sum(str_split($str)) % 10 == 0;
    }

    /** tests the string has a valid mod97 checksum. used for IBAN and VAT checks.
     * @param string $num_str   string representing the tested number
     * @return bool             whether the string passes
     * @see http://fr.wikipedia.org/wiki/Luhn
     * @see http://fr.wikipedia.org/wiki/Num%C3%A9ro_Interne_de_Classement
     */
    public static function mod97($num_str)
    {
        $ai = 1;
        $sum = 0;
        $len = \strlen($num_str);
        for ($i = 0; $i < $len; ++$i)
        {
            $sum += ($num_str[$len-$i-1] * $ai);
            $ai = ($ai * 10) % 97;
        }

        return $sum % 97;
    }
}
