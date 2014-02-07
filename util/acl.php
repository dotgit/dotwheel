<?php

/**
permissions management.

[type: library]

@author stas trefilov
*/

namespace Dotwheel\Util;

class Acl
{
    /** whether required acls are contained in present acls
     * @param array $present        present acls
     * @param array|int $required   required acls
     * @return boolean  returns true if at least one present acl is a subset of
     *  at least one required acl. $p is considered a subset of $r if:
     *  $r == 0x010000 and $p = 0x01xxxx
     *  $r == 0x010200 and $p = 0x0102xx
     *  $r == 0x010203 and $p = 0x010203
     */
    public static function containsRequired($present, $required)
    {
        foreach ((array)$required as $r)
        {
            if ($r & 0x0000ff)
            {
                foreach ($present as $p)
                    if ($r == $p)
                        return true;
            }
            elseif ($r & 0x00ffff)
            {
                foreach ($present as $p)
                    if ($r == ($p & 0xffff00))
                        return true;
            }
            else
            {
                foreach ($present as $p)
                    if ($r == ($p & 0xff0000))
                        return true;
            }
        }

        return false;
    }
}
