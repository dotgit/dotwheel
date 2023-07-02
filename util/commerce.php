<?php

/**
 * commerce and banking related algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class Commerce
{
    /** IBAN value for account number and origin country
     *
     * @param string $country 2-letter country code, like 'BE'
     * @param string $account bank account number, like '510-0075470-61'
     * @return string|bool IBAN code or <i>false</i> on error
     * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number
     * @assert('be', '510-0075470-61') == 'BE62 5100 0754 7061'
     */
    public static function ibanGenerate(string $country, string $account)
    {
        // clean $country and $account by only leaving uppercased alphanumerics
        $country_clean = preg_replace('/[^A-Z]/', '', strtoupper($country));
        $account_clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($account));
        if (strlen($country_clean) != 2 or empty($account_clean)) {
            return false;
        }

        // convert to numeric string by attaching to account the country code and 00 and replacing letters A..Z by numbers 10..35
        $num_str = '';
        foreach (str_split("$account_clean{$country_clean}00") as $c) {
            $num_str .= is_numeric($c) ? $c : (ord($c) - 55);
        }

        // run the MOD-97 algo
        $check = 98 - Algo::mod97($num_str);

        return sprintf('%s%02u %s', $country_clean, $check, implode(' ', str_split($account_clean, 4)));
    }

    /** check validity of IBAN string
     *
     * @param string $iban IBAN code, like 'DE44 5001 0517 5407 3249 31'
     * @return bool whether the value is a valid IBAN code
     * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number
     * @assert('BE62 5100 0754 7061') === true
     * @assert('DE44 5001 0517 5407 3249 31') === true
     */
    public static function ibanValidate(string $iban): bool
    {
        // clean $iban by only leaving uppercased alphanumerics
        $iban_clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($iban));
        if (!preg_match('/^[A-Z]{2}\d{2}\w+$/', $iban_clean)) {
            return false;
        }

        // convert to numeric string by moving first 4-chars to the end and replacing letters A..Z by numbers 10..35
        $num_str = '';
        foreach (str_split(substr($iban_clean, 4) . substr($iban_clean, 0, 4)) as $c) {
            $num_str .= is_numeric($c) ? $c : (ord($c) - 55);
        }

        // run the MOD-97 algo
        return Algo::mod97($num_str) == 1;
    }
}
