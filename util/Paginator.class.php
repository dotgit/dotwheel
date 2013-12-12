<?php

/**
 * Lists pagination using different methods.
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

class Paginator
{
    const P_ACTIVE  = 1;
    const P_RANGE   = 2;
    const P_LAST    = 3;
    const P_MAX_SLOTS   = 4;

    /** use logarithmic scale for page sets
     * @param array $params {P_ACTIVE:current page number
     * , P_RANGE: how many pages are in the range
     * , P_LAST: last page number
     * , P_SLOTS: how many pages to display
     * }
     * @return array an array of page numbers to display
     */
    public static function getListBinary($params)
    {
        $active_page = Params::extract($params, self::P_ACTIVE);
        $range = Params::extract($params, self::P_RANGE, 1);
        $last_page = Params::extract($params, self::P_LAST);
        $slots = Params::extract($params, self::P_MAX_SLOTS, 11);

        if ($last_page < 2)
            return array();

        if ($last_page <= $slots)
            return range(1, $last_page);

        if(abs($range) < $slots)
            return range ($active_page, min($last_page, $active_page + $slots - 1));

        $d = 1;
        if($active_page > 1)
            $ret = array(1, $active_page);
        else
            $ret = array(1);

        for($i = count($ret); $i < $slots; ++$i)
        {
            $ret[$i] = $active_page + $d;
            $d <<= 1;
        }

        return $ret;
    }

    /** use classic linear scale for big page sets
     * compatibility call: getListLinear($page+1, (int)(($total_items-1) / $items_per_page) + 1, $max_slots=10, $items=100)
     * @param array $params {P_ACTIVE:current page number
     * , P_LAST: last page number
     * , P_SLOTS: how many pages to display
     * }
     * @return array an array of page numbers to display
     */
    public static function getListLinear($params)
    {
        $active_page = Params::extract($params, self::P_ACTIVE);
        $last_page = Params::extract($params, self::P_LAST);
        $slots = Params::extract($params, self::P_MAX_SLOTS, 10);

        if ($last_page < 2)
            return array();

        if ($active_page < 1)
            $active_page = 1;
        if ($active_page > $last_page)
            $active_page = $last_page;

        $first_in_block = (int)(($active_page - 1)/$slots)*$slots + 1;
        $last_in_block = min($first_in_block + $slots - 1, $last_page);

        return range($first_in_block, $last_in_block);
    }

    /** use inversed logarithmic scale for big page sets
     * @param array $params {P_ACTIVE:current page number
     * , P_LAST: last page number
     * , P_SLOTS: how many pages to display
     * }
     * @return array an array of page numbers to display
     */
    public static function getListLog($params)
    {
        $active_page = Params::extract($params, self::P_ACTIVE);
        $last_page = Params::extract($params, self::P_LAST);
        $slots = Params::extract($params, self::P_MAX_SLOTS, 10);

        if ($last_page < 2)
            return array();

        if ($last_page <= $slots)
            return range(1, $last_page);

        if ($active_page < 1)
            $active_page = 1;
        if ($active_page > $last_page)
            $active_page = $last_page;

        if ($active_page == 1)
        {
            $l = 0;
            $r = $slots - 3;
        }
        elseif ($active_page == $last_page)
        {
            $l = $slots - 3;
            $r = 0;
        }
        elseif ($active_page == 2)
        {
            $l = 0;
            $r = $slots - 4;
        }
        elseif ($active_page == $last_page - 1)
        {
            $l = $slots - 4;
            $r = 0;
        }
        else
        {
            $lp = $active_page - 3;
            $rp = $last_page - $active_page - 2;
            $empty_slots = $slots - 5;
            if ($lp)
            {
                $l = (int)round($active_page/$last_page*$empty_slots);
                if ($l == 0)
                    $l = 1;
                elseif ($l > $lp)
                    $l = $lp;
            }

            $r = $empty_slots - $l;
            if ($rp and $r == 0)
            {
                $r = 1;
                $l = $empty_slots - $r;
            }
        }

        // first page
        $ret = array (1);
        // left slots (linear)
        if ($l)
            $ret = array_merge($ret, range($active_page - $l - 1, $active_page - 2));
        // previous page
        if ($active_page > 2)
            $ret[] = $active_page - 1;
        // active page
        if ($active_page > 1 and $active_page < $last_page)
            $ret[] = $active_page;
        // next page
        if ($active_page < $last_page - 1)
            $ret[] = $active_page + 1;
        // right slots (linear)
        if ($r)
            $ret = array_merge($ret, range($active_page + 2, $active_page + $r + 1));
        // last page
        $ret[] = $last_page;

        // left slots (logarithmic)
        if ($l)
        {
            for ($d = (1 + $active_page - 1) >> 1, $i = 1
                ; $i <= $l and $d > $ret[$i - 1] and $d < $ret[$i]
                ; ++$i
                )
            {
                $ret[$i] = $d;
                $d = ($d + $active_page) >> 1;
            }
        }
        // right slots (logarithmic)
        if ($r)
        {
            for ($d = ($active_page + 1 + $last_page) >> 1, $i = $slots - 2, $j = $slots - $r - 2
                ; $i > $j and $d < $ret[$i + 1] and $d > $ret[$i]
                ; --$i
                )
            {
                $ret[$i] = $d;
                $d = ($d + $active_page) >> 1;
            }
        }

        return $ret;
    }
}
