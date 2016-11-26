<?php

/**
 * some useful algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class Iban
{
    /**
     *
     * @param string $country 2-letter country code, like 'BE'
     * @param string $account bank account number, like '510-0075470-61'
     * @return string|bool IBAN code or <i>false</i> on error
     */
    public static function generate($country, $account)
    {
        // clean $country and $account by only leaving uppercased alphanumerics
        $country_clean = \preg_replace('/[^A-Z]/', '', \strtoupper($country));
        $account_clean = \preg_replace('/[^A-Z0-9]/', '', \strtoupper($account));
        if (\strlen($country_clean) != 2 or empty($account_clean)) {
            return false;
        }

        // convert to numeric string by attaching to account the country code and 00 and replacing letters A..Z by numbers 10..35
        $num_str = '';
        foreach (\str_split("$account_clean{$country_clean}00") as $c) {
            $num_str .= \is_numeric($c) ? $c : (\ord($c) - 55);
        }

        // run the MOD-97 algo
        $check = 98 - Algo::mod97($num_str);

        return  \sprintf('%s%02u %s', $country_clean, $check, \implode(' ', \str_split($account_clean, 4)));
    }

    /** checks the validity of IBAN string
     * @param string $iban IBAN code, like 'DE44 5001 0517 5407 3249 31'
     * @return bool whether the value is a valid IBAN code
     * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number
     */
    public static function validate($iban)
    {
        // clean $iban by only leaving uppercased alphanumerics
        $iban_clean = \preg_replace('/[^A-Z0-9]/', '', \strtoupper($iban));

        // convert to numeric string by moving first 4-chars to the end and replacing letters A..Z by numbers 10..35
        $num_str = '';
        foreach (\str_split(\substr($iban_clean, 4).\substr($iban_clean, 0, 4)) as $c) {
            $num_str .= \is_numeric($c) ? $c : (\ord($c) - 55);
        }

        // run the MOD-97 algo
        return Algo::mod97($num_str) == 1;
    }
}
