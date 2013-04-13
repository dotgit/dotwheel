<?php

/**
 * some useful algorithms
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

class Crypt
{
    const SALT_ALPHABET = '0123456789.ABCDEFGHIJKLMNOPQRSTUVWXYZ/abcdefghijklmnopqrstuvwxyz';
    const SALT_LEN_BLOWFISH = 22;

    /** generate salt string used by crypt function. use Blowfish salt by default
     * @param int $cost base 2 log value specifying the num of iterations
     * @return string Blowfish-formatted salt string ($2a$xx$yyyyyyyyyyyyyyyyyyyy$) or null
     */
    public static function getSalt($cost=9)
    {
        return CRYPT_BLOWFISH == 1
            ? sprintf ('$2a$%02u$%s$'
                , $cost
// faster
                , substr(str_shuffle(self::SALT_ALPHABET)
                    , 0
                    , self::SALT_LEN_BLOWFISH
                    )
// stronger
//              , substr(str_shuffle(str_repeat(self::SALT_ALPHABET, 64))
//                  , rand(0, (64<<6) - self::SALT_LEN_BLOWFISH)
//                  , self::SALT_LEN_BLOWFISH
//                  )
                )
            : null
            ;
    }

    /** encode the password by using the crypt algorithm (bcrypt or another available)
     * @param string $pass  password to encode
     * @param string $salt  salt to use when encoding. when empty, randomly created
     * @return string 60-char encoded password (Blowfish) or other size (13 chars min)
     */
    public static function passEncode($pass, $salt=null)
    {
        if (empty($salt))
            $salt = self::getSalt();

        return crypt($pass, $salt);
    }

    /** check the user provided unencoded password against existing hash
     * @param string $pass          clear(unencoded) password
     * @param string $pass_encoded  password hash
     * @return boolean whether the encoded $pass equals $pass_encoded
     */
    public static function passCompare($pass, $pass_encoded)
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
echo $cost.PHP_EOL
    . $salt.' '.($t2-$t1).PHP_EOL
    . $hash.' '.($t3-$t2).PHP_EOL
    . $hash2.PHP_EOL
    . Crypt::passCompare('qwerty', $hash).PHP_EOL
    ;

*/
