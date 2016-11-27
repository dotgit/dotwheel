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
     * @param string $num_str   string representing the tested number, like '79927398713'
     * @return bool             whether the string passes
     * @see https://en.wikipedia.org/wiki/Luhn_algorithm
     * @see https://en.wikipedia.org/wiki/Payment_card_number
     * @see https://fr.wikipedia.org/wiki/Syst%C3%A8me_d%27identification_du_r%C3%A9pertoire_des_entreprises
     * @see http://rosettacode.org/wiki/Luhn_test_of_credit_card_numbers#PHP
     * @see https://gist.github.com/troelskn/1287893
     */
    public static function luhn($num_str)
    {
        $impairs = array(0, 2, 4, 6, 8, 1, 3, 5, 7, 9);
        $sum = 0;
        for ($len = \strlen($num_str) - 1, $i = 0; $i <= $len; ++$i)
            $sum += ($i & 1)
                ? $impairs[$num_str[$len - $i]]
                : $num_str[$len - $i];

        return !($sum % 10);
    }

    /** returns the MOD-97 value for a numeric string. used for IBAN and VAT checks.
     * @param string $num_str   string representing the tested number, like '1110271220658244971655161187'
     * @return int
     * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number
     */
    public static function mod97($num_str)
    {
        if (\strlen($num_str) > 9) {
            $mod = \substr($num_str, 0, 2);
            foreach (\str_split(\substr($num_str, 2), 7) as $part) {
                $mod = "$mod$part" % 97;
            }
        } else {
            $mod = $num_str % 97;
        }

        return $mod;
    }

    /** creates a unique random code. the first 8 bytes of code is time-based
     * value allowing to produce incremental values and ease insertion
     * into database as a unique index. the resulting format is the following
     * (spaces added for readability, $bytes = 16):<pre>
     * TtTtTtTt FfFfFfFf RrRrRrRrRrRrRrRr</pre>
     * where<br>
     * Tt-part is 4-bytes unix time value,<br>
     * Ff-part is 4-bytes fraction of a second value,<br>
     * Rr-part is a random value.
     * @param int $bytes    length of resulting code in bytes (default = 8)
     * @return string hexadecimal representation of a random code
     */
    public static function uniqueCode($bytes=8)
    {
        list ($frac, $tm) = \explode(' ', \microtime());

        $suffix_len = $bytes - 8;

        $random_str = $suffix_len > 0
            ? (\function_exists('random_bytes')
                ? \random_bytes($suffix_len)
                : (\function_exists('openssl_random_pseudo_bytes')
                    ? \openssl_random_pseudo_bytes($suffix_len)
                    : function() use ($suffix_len) {
                        for ($str=''; $suffix_len; --$suffix_len) {
                            $str .= \chr(\mt_rand(0, 255));
                        }
                        return $str;
                    }
                )
            )
            : null;

        return
            // 8-bytes prefix
            \dechex((($tm & 0xffffffff) << 32) + (int)($frac*0x100000000)).
            // random part
            \bin2hex($random_str);
    }
}
