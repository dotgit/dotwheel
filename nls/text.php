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
    public static $pluralForms = 0;
    public static $domainTranslations = array();
    public static $domain = 'messages'; // default gettext domain



    /** fetches an existing string from $domainTranslations by its hash and
     * message name (in case of collision)
     * @param string $domain    domain to lookup
     * @param int $hash         existing hash in $domainTranslations[$domain]
     * @param string $message   the original message used to produce the hash
     * @return string
     */
    protected static function fetch($domain, $hash, $message)
    {
        return \is_array(self::$domainTranslations[$domain][$hash])
            ? (isset(self::$domainTranslations[$domain][$hash][$message])
                ? self::$domainTranslations[$domain][$hash][$message]
                : self::$domainTranslations[$domain][$hash][$hash]
            )
            : self::$domainTranslations[$domain][$hash];
    }

    /** loads translations from the provided translations file (generated from .PO file)
     * existing translation file must be present in the specified location.
     * ex: english translation for the application is stored in the file
     *      /app/locale/en_US/myapp.php
     *
     * @param string $domain    domain filename (like 'myapp')
     * @param string $dir       path to the locale dir without language part (like '/app/locale')
     * @param string $lang      language part of dir (like 'en_US')
     */
    public static function binddomain($domain, $dir, $lang)
    {
        include ("$dir/$lang/$domain.php");

        if (isset($PLURALFORMS))
            self::$pluralForms = $PLURALFORMS;
        if (isset($TRANSLATIONS))
            self::$domainTranslations[$domain] = $TRANSLATIONS;
        self::$domain = $domain;
    }

    /** sets global domain to new value
     * @param string $domain
     */
    public static function domain($domain)
    {
        self::$domain = $domain;
    }

    public static function _($message)
    {
        $crc = \crc32($message);

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $message)
            : $message;
    }

    public static function dget($domain, $message)
    {
        $crc = \crc32($message);

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $message)
            : $message;
    }

    public static function pget($context, $message)
    {
        $crc = \crc32("$message\f$context");

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $message)
            : $message;
    }

    public static function dpget($domain, $context, $message)
    {
        $crc = \crc32("$message\f$context");

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $message)
            : $message;
    }

    public static function nget($message1, $message2, $count)
    {
        $n = (int)$count;
        eval('$num = '.self::$pluralForms.';');
        $idn = "$message1\f$message2\f".(int)$num;
        $crc = \crc32($idn);

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $idn)
            : ($count == 1 ? $message1 : $message2);
    }

    public static function dnget($domain, $message1, $message2, $count)
    {
        $n = (int)$count;
        eval('$num = '.self::$pluralForms.';');
        $idn = "$message1\f$message2\f".(int)$num;
        $crc = \crc32($idn);

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $idn)
            : ($count == 1 ? $message1 : $message2);
    }

    public static function pnget($context, $message1, $message2, $count)
    {
        $n = (int)$count;
        eval('$num = '.self::$pluralForms.';');
        $idn = "$message1\f$context\f$message2\f".(int)$num;
        $crc = \crc32($idn);

        return (isset(self::$domainTranslations[self::$domain][$crc]))
            ? self::fetch(self::$domain, $crc, $idn)
            : ($count == 1 ? $message1 : $message2);
    }

    public static function dpnget($domain, $context, $message1, $message2, $count)
    {
        $n = (int)$count;
        eval('$num = '.self::$pluralForms.';');
        $idn = "$message1\f$context\f$message2\f".(int)$num;
        $crc = \crc32($idn);

        return (isset(self::$domainTranslations[$domain][$crc]))
            ? self::fetch($domain, $crc, $idn)
            : ($count == 1 ? $message1 : $message2);
    }
}
