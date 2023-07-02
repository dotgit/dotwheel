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
    public const RFC5322_ATOMS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&\'*+-/=?^_`{|}~';


    /** concatenate message parts using calculated boundary, return compiled mime message and set $headers
     *
     * @param array $parts mime parts encoded using self::part*() methods
     * @param array $headers returned headers containing mime version and content boundary used
     * @return string
     */
    public static function compileParts(array $parts, array &$headers): string
    {
        $boundary = 'BOUND:' . mt_rand() . mt_rand() . ':';

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";

        return
            "--$boundary\r\n" .
            implode("\r\n--$boundary\r\n", $parts) .
            "\r\n--$boundary--";
    }

    /** display name formatted as mime 5322/6532 for the destination field of the message
     *
     * @param ?string $name destination name to use
     * @return string the converted display name
     * @assert('Resnick') == 'Resnick'
     * @assert('Mary Smith') == 'Mary Smith'
     * @assert('Joe Q. Public') == '"Joe Q. Public"'
     * @assert('Giant; "Big" Box') == '"Giant; \\"Big\\" Box"'
     * @assert('Jérôme') == '"Jérôme"'
     */
    public static function displayName(?string $name): string
    {
        $phrase_preg = preg_quote(self::RFC5322_ATOMS, '/');

        if (preg_match("/[^$phrase_preg\s]/", $name)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $name) . '"';
        } else {
            return $name;
        }
    }

    /** base64-encoded $content chunk_split-ted and prefixed with corresponding mime headers
     *
     * @param ?string $content
     * @param ?string $mime_type
     * @return string
     * @assert ('Text', 'text/plain') == Text
     */
    public static function partBase64(?string $content, ?string $mime_type): string
    {
        return implode("\r\n", [
            "Content-Type: $mime_type",
            'Content-Transfer-Encoding: base64',
            '',
            chunk_split(base64_encode($content)),
        ]);
    }

    /** quoted-printable-encoded $content prefixed with corresponding mime headers
     *
     * @param ?string $content
     * @param ?string $mime_type
     * @return string
     */
    public static function partQuoted(?string $content, ?string $mime_type): string
    {
        return implode("\r\n", [
            "Content-Type: $mime_type",
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($content),
        ]);
    }
}
