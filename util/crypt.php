<?php

/**
 * some useful algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class Crypt
{
    public const SALT_ALPHABET = '0123456789.ABCDEFGHIJKLMNOPQRSTUVWXYZ/abcdefghijklmnopqrstuvwxyz';
    public const SALT_LEN_BLOWFISH = 22;


    /** generate salt string used by crypt function. use Blowfish salt by default
     *
     * @param int $cost base 2 log value specifying the num of iterations
     * @return ?string Blowfish-formatted salt string ($2a$xx$yyyyyyyyyyyyyyyyyyyyyy$) or null
     */
    public static function getSalt(int $cost = 9): ?string
    {
        return CRYPT_BLOWFISH == 1
            ? sprintf(
                '$2y$%02u$%s$',
                $cost,
// faster, small memory footprint
                substr(
                    str_shuffle(self::SALT_ALPHABET),
                    0,
                    self::SALT_LEN_BLOWFISH
                )
// stronger, large memory footprint
// 64 is a length of SALT_ALPHABET
// 4096 == 64*64
//              \substr(
//                  \str_shuffle(\str_repeat(self::SALT_ALPHABET, 64)),
//                  \mt_rand(0, 4096 - self::SALT_LEN_BLOWFISH),
//                  self::SALT_LEN_BLOWFISH
//              )
            )
            : null;
    }

    /** encode the password by using the crypt algorithm (bcrypt or another available)
     *
     * @param string $pass password to encode
     * @param ?string $salt salt to use when encoding. when empty, randomly created
     * @return string 60-char encoded password (Blowfish) or other size (13 chars min)
     */
    public static function passEncode(string $pass, ?string $salt = null): string
    {
        if (empty($salt)) {
            $salt = self::getSalt();
        }

        return \crypt($pass, $salt);
    }

    /** check the user provided unencoded password against existing hash
     *
     * @param string $pass clear (unencoded) password
     * @param string $pass_encoded password hash
     * @return bool whether the encoded $pass equals $pass_encoded
     */
    public static function passCompare(string $pass, string $pass_encoded): bool
    {
        return self::passEncode($pass, $pass_encoded) == $pass_encoded;
    }
}

/* Test case

$cost = 9;
$t1 = microtime(true);
$salt = Crypt::getSalt($cost);
$t2 = microtime(true);
$hash = Crypt::passEncode('qwerty', $salt);
$t3 = microtime(true);
$hash2 = Crypt::passEncode('qwerty', $hash);
echo $cost.PHP_EOL.
    $salt.' '.($t2-$t1).PHP_EOL.
    $hash.' '.($t3-$t2).PHP_EOL.
    $hash2.PHP_EOL.
    Crypt::passCompare('qwerty', $hash).PHP_EOL;

*/
