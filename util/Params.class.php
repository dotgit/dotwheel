<?php

/**
 * handling the list of parameters
 *
 * [type: framework]
 *
 * @author stas trefilov
 */

namespace dotwheel\util;

class Params
{
    /** inject a new attributes into the list of attributes or add new value to the existing attribute
     * @param array $params array of attributes (reference)
     * @param string $value attribute value
     * @param string $name  attribute name
     * @param string $sep   separator of attribute values
     */
    public static function add(&$params, $value, $name='class', $sep=' ')
    {
        if (! is_array($params))
            $params = array($name=>$value);
        elseif (empty($params[$name]))
            $params[$name] = $value;
        elseif (strpos("$sep{$params[$name]}$sep", "$sep$value$sep") === false)
            $params[$name] .= "$sep$value";
    }

    /** returns the value of the specified attribute and unsets it in the attributes array
     * @param array $params     array of attributes
     * @param string $name      attribute name
     * @param mixed $default    default value to return if $params[$name] is not set
     * @return mixed
     */
    public static function extract(&$params, $name, $default=null)
    {
        if (! is_array($params) or ! array_key_exists($name, $params))
            return $default;

        $ret = $params[$name];
        unset($params[$name]);

        return $ret;
    }
}
