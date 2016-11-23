<?php

/**
 * mime-type related functionnalities
 *
 * [type: app model]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class Mime
{
    const RFC5322_ATOMS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&\'*+-/=?^_`{|}~';



    /** concatenates message parts using calculated boundary, returns compiled mime message and sets $headers
     * @param array $parts      mime parts encoded using self::part*() methods
     * @param array $headers    returned headers containing mime version and content boundary used
     * @return string
     */
    public static function compileParts($parts, &$headers)
    {
        $boundary = 'BOUND:'.\mt_rand().\mt_rand().':';

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";

        return
            "--$boundary\r\n".
            \implode("\r\n--$boundary\r\n", $parts).
            "\r\n--$boundary--";
    }

    /** returns the display name formatted as mime 5322/6532 for the destination field of the message
     * @param string $name      destination name to use
     * @param string $charset   charset [UTF-8]
     * @return string the converted display name
     * @assert('Resnick') == 'Resnick'
     * @assert('Mary Smith') == 'Mary Smith'
     * @assert('Joe Q. Public') == '"Joe Q. Public"'
     * @assert('Giant; "Big" Box') == '"Giant; \\"Big\\" Box"'
     * @assert('Jérôme') == '"Jérôme"'
     */
    public static function displayName($name)
    {
        $phrase_preg = \preg_quote(self::RFC5322_ATOMS, '/');

        if (\preg_match("/[^$phrase_preg\s]/", $name)) {
            return '"'.\str_replace(array('\\', '"'), array('\\\\', '\\"'), $name).'"';
        } else {
            return $name;
        }
    }

    /** returns base64-encoded $content chunk_split'ted and prefixed with corresponding mime headers
     * @param string $content
     * @param string $mime_type
     * @return string
     * @assert ('Text', 'text/plain') == Text
     */
    public static function partBase64($content, $mime_type)
    {
        return \implode("\r\n", array(
            "Content-Type: $mime_type",
            'Content-Transfer-Encoding: base64',
            '',
            \chunk_split(\base64_encode($content)),
        ));
    }

    /** returns quoted-printable-encoded $content prefixed with corresponding mime headers
     * @param string $content
     * @param string $mime_type
     * @return string
     */
    public static function partQuoted($content, $mime_type)
    {
        return \implode("\r\n", array(
            "Content-Type: $mime_type",
            'Content-Transfer-Encoding: quoted-printable',
            '',
            \quoted_printable_encode($content),
        ));
    }
}
