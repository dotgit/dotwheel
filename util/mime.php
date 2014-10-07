<?php

/**
 * mime-type related functionnalities
 *
 * [type: app model]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

use Dotwheel\Nls\Nls;

class Mime
{
    /** concatenates message parts using calculated boundary, returns compiled mime message and sets $headers
     * @param array $parts      mime parts encoded using self::part*() methods
     * @param array $headers    returned headers containing mime version and content boundary used
     * @return string
     */
    public static function compileParts($parts, &$headers)
    {
        $boundary = $_SERVER['REQUEST_TIME'];

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";

        return
            "--$boundary\r\n".
            \implode("\r\n--$boundary\r\n", $parts).
            "\r\n--$boundary--";
    }

    /** returns base64-encoded $content chunk_split'ted and prefixed with corresponding mime headers
     * @param string $content
     * @param string $mime_type
     * @return string
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

    /** returns quoted-printable $subject ready to use as email Subject: header
     * @param string $subject
     * @return string
     */
    public static function subject($subject)
    {
        return
            '=?'.Nls::$charset.'?Q?'.
            \str_replace(
                ' ',
                '=20',
                \quoted_printable_encode($subject)
            ).
            '?=';
    }
}
