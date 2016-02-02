<?php

/**
 * some useful algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

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

        return \array_sum(\str_split($str)) % 10 == 0;
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

    /** creates a unique random code. the first 5 bytes of code is time-based
     * value allowing to produce incremental values and ease insertion
     * into database as a unique index. the resulting format is the following
     * (spaces added for readability, $bytes = 16):<pre>
     * TtTtTtTt Ff OoOoOoOoOoOoOoOoOoOoOo</pre>
     * where<br>
     * Tt-part is 4-byte unix time value,<br>
     * Ff-part is 1-byte fraction of a second value,<br>
     * Oo-part is an openssl-generated random value.
     * @param int $bytes    length of resulting code in bytes (greater than 5)
     * @return string hexadecimal representation of a random code
     */
    public static function uniqueCode($bytes)
    {
        list ($frac, $tm) = \explode(' ', \microtime());

        $suffix_len = $bytes - 5;

        $random_str = \function_exists('random_bytes')
            ? \random_bytes($suffix_len)
            : (\function_exists('openssl_random_pseudo_bytes')
                ? \openssl_random_pseudo_bytes($suffix_len)
                : function() use ($suffix_len) {
                    for ($str='';$suffix_len;--$suffix_len)
                        $str .= \chr(\mt_rand(0, 255));
                    return $str;
                });

        return
            // 5-byte prefix
            \dechex((($tm & 0xffffffff) << 8) + (int)($frac*256)).
            \bin2hex($random_str);
    }
}
