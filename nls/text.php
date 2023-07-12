<?php

/**
 * handling translations and locales
 *
 * this class is intended to eliminate the dependency on gettext library since
 * unstable in web environments.
 * the existing .PO catalogue is transformed by a po2php tool to a .PHP file
 * that may be stored by opcode cache. two variables are defined in the file:
 * $PLURALFORMS and $TRANSLATIONS.
 * $PLURALFORMS is a 'plural' attribute of Plural-Forms header from .PO file
 * with 'n' replaced by '$n' to facilitate evaluation.
 * $TRANSLATIONS is a hash array of the form crc32=>TranslatedString, where
 * crc32 is a CRC32 hash of the source string from .PO file and
 * TranslatedString is a corresponding translated string.
 * when class methods are called with source message as parameter it is first
 * translated into CRC32 form and then looked up in $TRANSLATIONS array.
 * domains, plural forms and contexts are maintained via corresponding d*, n*
 * and p* methods.
 *
 * class methods replace main gettext functions.
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Nls;

class Text
{
    public static string $pluralForms = '0';
    public static array $domainTranslations = [];
    public static string $domain = 'messages'; // default gettext domain

    /** fetch an existing string from $domainTranslations by its hash and message name (in case of collision)
     *
     * @param string $domain domain to lookup
     * @param int $hash existing hash in $domainTranslations[$domain]
     * @param string $message the original message used to produce the hash
     * @return string
     */
    protected static function fetch(string $domain, int $hash, string $message): string
    {
        return is_array(self::$domainTranslations[$domain][$hash])
            ? (self::$domainTranslations[$domain][$hash][$message] ?? self::$domainTranslations[$domain][$hash][$hash])
            : self::$domainTranslations[$domain][$hash];
    }

    /** load translations from the provided translations file (generated from .PO file). existing translation file must
     * be present in the specified location.
     * ex: english translation for the application is stored in the file
     *      /app/locale/en_US/myapp.php
     *
     * @param string $domain domain filename (like 'myapp')
     * @param string $dir path to the locale dir without language part (like '/app/locale')
     * @param string $lang language part of dir (like 'en_US')
     */
    public static function binddomain(string $domain, string $dir, string $lang)
    {
        include("$dir/$lang/$domain.php");

        if (isset($PLURALFORMS)) {
            self::$pluralForms = $PLURALFORMS;
        }
        if (isset($TRANSLATIONS)) {
            self::$domainTranslations[$domain] = $TRANSLATIONS;
        }
        self::$domain = $domain;
    }

    /** set global domain to new value
     *
     * @param string $domain
     */
    public static function domain(string $domain)
    {
        self::$domain = $domain;
    }

    /** get message translation
     *
     * @param string $message
     * @return string translated message
     */
    public static function _(string $message): string
    {
        $crc = crc32($message);

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $message)
            : $message;
    }

    /** get message translation from specific domain
     *
     * @param string $domain
     * @param string $message
     * @return string translated message
     */
    public static function dget(string $domain, string $message): string
    {
        $crc = crc32($message);

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $message)
            : $message;
    }

    /** get message translation in specific context
     *
     * @param string $context
     * @param string $message
     * @return string translated message
     */
    public static function pget(string $context, string $message): string
    {
        $crc = crc32("$message\f$context");

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $message)
            : $message;
    }

    /** get message translation from specific domain in specific context
     *
     * @param string $domain
     * @param string $context
     * @param string $message
     * @return string translated message
     */
    public static function dpget(string $domain, string $context, string $message): string
    {
        $crc = crc32("$message\f$context");

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $message)
            : $message;
    }

    /** use singular or one of plural forms of a message depending on $n and NLS settings for the language
     *
     * @param string $message1
     * @param string $message2
     * @param int $n
     * @return string translated message
     */
    public static function nget(string $message1, string $message2, int $n): string
    {
        eval('$num = ' . self::$pluralForms . ';');
        $idn = "$message1\f$message2\f" . (int)$num;
        $crc = crc32($idn);

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $idn)
            : ($n == 1 ? $message1 : $message2);
    }

    /** use singular or one of plural forms of a message from specific domain depending on $n and NLS settings for the
     * language
     *
     * @param $domain
     * @param $message1
     * @param $message2
     * @param $n
     * @return string translated message
     */
    public static function dnget($domain, $message1, $message2, $n)
    {
        eval('$num = ' . self::$pluralForms . ';');
        $idn = "$message1\f$message2\f" . (int)$num;
        $crc = crc32($idn);

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $idn)
            : ($n == 1 ? $message1 : $message2);
    }

    /** use singular or one of plural forms of a message in specific context depending on $n and NLS settings for the
     * language
     *
     * @param string $context
     * @param string $message1
     * @param string $message2
     * @param int $n
     * @return string translated message
     */
    public static function npget(string $context, string $message1, string $message2, int $n): string
    {
        eval('$num = ' . self::$pluralForms . ';');
        $idn = "$message1\f$context\f$message2\f" . (int)$num;
        $crc = crc32($idn);

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $idn)
            : ($n == 1 ? $message1 : $message2);
    }

    /** use singular or one of plural forms of a message from specific domain in specific context depending on $n and
     * NLS settings for the language
     *
     * @param string $domain
     * @param string $context
     * @param string $message1
     * @param string $message2
     * @param int $n
     * @return string
     */
    public static function dnpget(string $domain, string $context, string $message1, string $message2, int $n): string
    {
        eval('$num = ' . self::$pluralForms . ';');
        $idn = "$message1\f$context\f$message2\f" . (int)$num;
        $crc = crc32($idn);

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $idn)
            : ($n == 1 ? $message1 : $message2);
    }
}
