<?php

/**
 * handling the list of parameters
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Util;

class Params
{
    /** add new attribute to the list of attributes or add new value to the existing attribute
     *
     * @param mixed $params array of attributes (reference)
     * @param ?string $value attribute value
     * @param string $name attribute name
     * @param string $sep separator of attribute values
     * @assert() == 1
     */
    public static function add(&$params, ?string $value, string $name = 'class', string $sep = ' ')
    {
        if (!is_array($params)) {
            $params = [$name => $value];
        } elseif (empty($params[$name])) {
            $params[$name] = $value;
        } elseif (strpos("$sep$params[$name]$sep", "$sep$value$sep") === false) {
            $params[$name] .= "$sep$value";
        }
    }

    /** return the value of the specified attribute and unset it in the attributes array
     *
     * @param mixed $params array of attributes
     * @param string $name attribute name
     * @param mixed $default default value to return if $params[$name] is not set
     * @return mixed
     * @assert() == 1
     */
    public static function extract(&$params, string $name, $default = null)
    {
        if (!is_array($params) or !array_key_exists($name, $params)) {
            return $default;
        }

        $ret = $params[$name];
        unset($params[$name]);

        return $ret;
    }
}
