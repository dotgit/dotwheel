<?php

/**
 * some useful algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

use Exception;

class Algo
{
    /** whether the string has a valid Luhn checksum. used for SIRET and CC checks.
     *
     * @param string $num_str string representing the tested number, like '79927398713'
     * @return bool             whether the string passes
     * @see https://en.wikipedia.org/wiki/Luhn_algorithm
     * @see https://en.wikipedia.org/wiki/Payment_card_number
     * @see https://fr.wikipedia.org/wiki/Syst%C3%A8me_d%27identification_du_r%C3%A9pertoire_des_entreprises
     * @see http://rosettacode.org/wiki/Luhn_test_of_credit_card_numbers#PHP
     * @see https://gist.github.com/troelskn/1287893
     */
    public static function luhn(string $num_str): bool
    {
        $impairs = [0, 2, 4, 6, 8, 1, 3, 5, 7, 9];
        $sum = 0;
        for ($len = strlen($num_str) - 1, $i = 0; $i <= $len; ++$i) {
            $sum += ($i & 1)
                ? $impairs[$num_str[$len - $i]]
                : $num_str[$len - $i];
        }

        return !($sum % 10);
    }

    /** MOD-97 value for a numeric string. used for IBAN and VAT checks.
     *
     * @param string $num_str string representing the tested number, like '1110271220658244971655161187'
     * @return int
     * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number
     */
    public static function mod97(string $num_str): int
    {
        if (strlen($num_str) > 9) {
            $mod = substr($num_str, 0, 2);
            foreach (str_split(substr($num_str, 2), 7) as $part) {
                $mod = "$mod$part" % 97;
            }
        } else {
            $mod = $num_str % 97;
        }

        return $mod;
    }

    /** create a unique random code. the first 8 bytes of code is time-based value allowing to produce incremental
     * values and ease insertion into database as a unique index. the resulting format is as follows (spaces added
     * for readability, $bytes = 16):
     * <pre>TtTtTtTt FfFfFfFf RrRrRrRrRrRrRrRr</pre>
     * where:
     * - Tt-part is 4-bytes unix time value,<br>
     * - Ff-part is 4-bytes fraction of a second value,<br>
     * - Rr-part is a random value.
     *
     * @param int $bytes length of resulting code in bytes (default = 8)
     * @return string hexadecimal representation of a random code
     * @throws Exception
     */
    public static function uniqueCode(int $bytes = 8): string
    {
        [$frac, $tm] = explode(' ', microtime());

        $suffix_len = $bytes - 8;

        $timestamp = dechex((($tm & 0xFFFF_FFFF) << 32) + (int)($frac * 0x1_0000_0000));

        if ($suffix_len > 0) {
            $random_suffix = bin2hex(random_bytes($suffix_len));
            return "$timestamp$random_suffix";
        } else {
            return $timestamp;
        }
    }

    /** create a globally unique sortable id generator (xid). the resulting format is as follows (spaces added for
     * readability):
     * <pre>TtTtTtTt MmMmMm PpPp CcCcCc</pre>
     * where:
     * - Tt-part is 4-bytes unix time value,<br>
     * - Mm-part is 3-bytes machine ID,<br>
     * - Pp-part is 2-bytes process ID,<br>
     * - Cc-part is 3-bytes increment counter randomly initialized.
     *
     * @param int $mid 3-byte machine identifier
     * @return string hexadecimal representation of a xid
     */
    public static function uniqueXid(int $mid): string
    {
        static $cnt;
        if (!isset($cnt)) {
            $cnt = rand(0, 0xFfFfFf);
        }

        return sprintf(
            '%08x%06x%04x%06x',
            time() & 0xFFFF_FFFF,
            $mid & 0xFF_FFFF,
            getmypid() & 0xFFFF,
            (++$cnt) & 0xFF_FFFF
        );
    }
}
