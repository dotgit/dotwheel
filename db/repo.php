<?php

/**
repository management.

[type: library]

@author stas trefilov
*/

namespace Dotwheel\Db;

use Dotwheel\Nls\Nls;
use Dotwheel\Nls\Text;
use Dotwheel\Ui\Html;
use Dotwheel\Util\Misc;

class Repo
{
    const P_CLASS               = 1;
    const P_LABEL               = 11;   // always html-encoded
    const P_LABEL_SHORT         = 12;
    const P_LABEL_LONG          = 13;
    const P_WIDTH               = 21;
    const P_ITEMS               = 31;
    const P_ITEMS_SHORT         = 32;
    const P_ITEMS_LONG          = 33;
    const P_ITEM_BLANK          = 35;   // for specifying the first blank item
    const P_ITEM_DELIM          = 36;   // for specifying items delimiter in enums and sets
    const P_COMMENT             = 41;   // for checkboxes in forms
    const P_ALIAS               = 51;
    const P_VALIDATE_CALLBACK   = 61;   // for input validation via user callback (callback function receives value
                                        // to validate, returns true if ok, error message otherwise)
    const P_VALIDATE_REGEXP     = 62;   // for input validation via regexp
    const P_REQUIRED            = 71;   // for specifying required fields
    const P_WHERE_CALLBACK      = 81;
    const P_FLAGS               = 91;

    const C_ID      = 1;
    const C_INT     = 2;
    const C_CENTS   = 3;
    const C_BOOL    = 4;
    const C_TEXT    = 5;
    const C_DATE    = 6;
    const C_ENUM    = 7;
    const C_SET     = 8;
    const C_FILE    = 9;

    const F_POSITIVE        = 0x00000001;
    const F_SHOW_DECIMAL    = 0x00000002;
    const F_SHOW_COMPACT    = 0x00000004;

    const F_PASSWORD        = 0x00000010;
    const F_UPPERCASE       = 0x00000020;
    const F_LOWERCASE       = 0x00000040;
    const F_UCFIRST         = 0x00000080;
    const F_TEL             = 0x00000100;
    const F_EMAIL           = 0x00000200;
    const F_URL             = 0x00000400;
    const F_TEXTAREA        = 0x00000800;
    const F_TEXT_FORMAT     = 0x00001000;

    const F_DATETIME        = 0x00010000;

    const F_CHAR            = 0x00100000;
    const F_RADIO           = 0x00200000;    // display as radio buttons instead of select dropdown
    const F_INLINE_ITEMS    = 0x00400000;    // display enum or set items on one line instead of stacked
    const F_ABBR            = 0x00800000;    // using (P_ITEMS_SHORT || P_ITEMS) + (P_ITEMS_LONG || P_ITEMS) for abbr tag
    const F_ARRAY           = 0x01000000;    // list items are using [[k,v], [k,v], ...] form instead of {k:v, k:v, ...}

    const F_ASIS            = 0x10000000;

    /**
     * var array fields description in the form:
     * {field: {PARAM_LABEL:'User last name', PARAM_CLASS:CLASS_TEXT, PARAM_FLAGS:FLAG_UPPERCASE, etc...}}
     */
    public static $store = array();
    /** var array list of loaded packages */
    public static $packages = array();
    /** var array list of unresolved aliases to lookup after each addPackage */
    public static $unresolved = array();
    /** var array list of validated request variables */
    public static $validated = array();
    /** var array list of errors during import */
    public static $input_errors = array();



    /** adds new package to repository (resolves aliases while adding). if the package
     * already exists then does nothing.
     * @param string $package   package name
     * @param array $fields     {fld:{repository field params}, ...}
     * @return bool whether the package was added
     */
    public static function registerPackage($package, $fields)
    {
        // do not register twice
        if (isset(self::$packages[$package]))
            return false;

        // add fields resolving aliases
        foreach ($fields as $name=>$repo)
        {
            if (isset($repo[self::P_ALIAS]))
            {
                $ref = $repo[self::P_ALIAS];
                if (isset(self::$store[$ref]))
                    $repo += self::$store[$ref];
                elseif (isset(self::$unresolved[$ref]))
                    self::$unresolved[$ref][$name] = true;
                else
                    self::$unresolved[$ref] = array($name=>true);
            }
            self::$store[$name] = $repo;
        }

        // check unresolved links
        foreach (self::$unresolved as $ref=>$names)
            if (isset(self::$store[$ref]))
            {
                foreach ($names as $name=>$_)
                    self::$store[$name] += self::$store[$ref];
                unset(self::$unresolved[$ref]);
            }

        // register package
        self::$packages[$package] = true;

        return true;
    }

    /** returns a specified field parameter if set
     * @param string $name  field name
     * @param int $param    field parameter
     * @param array $repo   {field repository attributes}
     * @return array|null
     */
    public static function getParam($name, $param, $repo=array())
    {
        if (isset($repo[$param]))
            return $repo[$param];
        elseif (isset(self::$store[$name][$param]))
            return self::$store[$name][$param];

        return null;
    }

    /** returns a specified html-escaped label if set (otherwise the P_LABEL)
     * @param string $name  field name
     * @param int $param    which label to return
     * @param array $repo   {field repository attributes}
     * @return string|null
     */
    public static function getLabel($name, $param=null, $repo=array())
    {
        if (isset(self::$store[$name]))
            $repo += self::$store[$name];
        switch ($param)
        {
        case self::P_LABEL_SHORT:
            return isset($repo[self::P_LABEL_SHORT])
                ? $repo[self::P_LABEL_SHORT]
                : (isset($repo[self::P_LABEL])
                    ? $repo[self::P_LABEL]
                    : null
                );
        case self::P_LABEL_LONG:
            return isset($repo[self::P_LABEL_LONG])
                ? $repo[self::P_LABEL_LONG]
                : (isset($repo[self::P_LABEL])
                    ? $repo[self::P_LABEL]
                    : null
                );
        default:
            return isset($repo[self::P_LABEL])
                ? $repo[self::P_LABEL]
                : null;
        }

        return null;
    }

    /** returns a specified list if set(otherwise the PARAM_LIST)
     * @param string $name  field name
     * @param int $param    which list to return
     * @param array $repo   {field repository attributes}
     * @return array
     */
    public static function getList($name, $param=null, $repo=array())
    {
        if (isset(self::$store[$name]))
            $repo += self::$store[$name];
        switch ($param)
        {
        case self::P_ITEMS_SHORT:
            return isset($repo[self::P_ITEMS_SHORT])
                ? $repo[self::P_ITEMS_SHORT]
                : $repo[self::P_ITEMS];
        case self::P_ITEMS_LONG:
            return isset($repo[self::P_ITEMS_LONG])
                ? $repo[self::P_ITEMS_LONG]
                : $repo[self::P_ITEMS];
        default:
            return $repo[self::P_ITEMS];
        }

        return array();
    }

    /** returns field repository attributes
     * @param string $name  field name
     * @param array $repo   {field repository attributes}
     * @return array
     */
    public static function get($name, $repo=array())
    {
        return isset(self::$store[$name]) ? $repo+self::$store[$name] : $repo;
    }

    /** whether the repository entry specifies numbers that can be added / substracted
     * @param array $repo   {field repository attributes}
     * @return bool
     */
    public static function isArithmetical($repo)
    {
        if (isset($repo[self::P_CLASS]))
            switch ($repo[self::P_CLASS])
            {
            case self::C_CENTS:
            case self::C_INT:
                return true;
            }
        return false;
    }

    /** whether the repository entry specifies date
     * @param array $repo   {field repository attributes}
     * @return bool
     */
    public static function isDate($repo)
    {
        if (isset($repo[self::P_CLASS])
            and $repo[self::P_CLASS] == self::C_DATE
        )
            return true;
        else
            return false;
    }

    /** whether the repository entry specifies the textual data or not
     * @param array $repo   {field repository attributes}
     * @return bool
     */
    public static function isTextual($repo)
    {
        if (empty($repo[self::P_CLASS])
            or $repo[self::P_CLASS] == self::C_TEXT
        )
            return true;
        else
            return false;
    }

    /** stores validated input values in Repo::$validated public var. if errors occured
     * during validation then Repository::$input_errors array contains error messages.
     * @param array $fields {field1:{repository parameters}, field2:{repository parameters}}
     * @param array $values {field1:val1, field2:val2, ...} if omitted then uses $_REQUEST
     * @return bool whether validation is passed ok (no input errors)
     */
    public static function validateInput($fields, $values=null)
    {
        if (empty($values))
            $values = $_REQUEST;

        self::$input_errors = array();

        foreach ($fields as $fld=>$params)
        {
            if (\is_scalar($params))
                $params = array($params=>true);
            $repo = self::get($fld, $params);

            $err = null;
            $label = isset($repo[self::P_LABEL]) ? $repo[self::P_LABEL] : '';
            $value = isset($values[$fld])
                ? (\is_scalar($values[$fld])
                    ? \trim($values[$fld])
                    : ($values[$fld] ? $values[$fld] : null)
                )
                : null;

            if (isset($value))
            {
                // convert value
                if ($value === '')
                    $val = null;
                else
                {
                    $flags = isset($repo[self::P_FLAGS]) ? $repo[self::P_FLAGS] : null;
                    switch (isset($repo[self::P_CLASS]) ? $repo[self::P_CLASS] : self::C_TEXT)
                    {
                    case self::C_ID:
                        $val = (int)$value;
                        $flags |= self::F_POSITIVE;
                        break;
                    case self::C_INT:
                        $val = \is_numeric($value) ? (int)$value : false;
                        break;
                    case self::C_CENTS:
                        if (! \is_scalar($value))
                            $val = false;
                        elseif (! \is_numeric(\str_replace(array(' ', ' ', '.', ','), '', $value)))
                            $val = false;
                        else
                        {
                            $value = \str_replace(array(' ', ' ', '.'), array('', '', ','), $value);
                            $m = array();
                            if (\preg_match('/^(.*),(\d{1,2})$/', $value, $m))
                                $val = \str_replace(',', '', $m[1]) . $m[2] . (isset($m[2][1]) ? '' : '0');
                            else
                                $val = \str_replace(',', '', $value).'00';
                            $val = (int)$val;
                        }
                        break;
                    case self::C_BOOL:
                        $val = $value ? 1 : 0;
                        break;
                    case self::C_TEXT:
                        if (\is_scalar($value))
                        {
                            if (! ($flags & self::F_TEXTAREA))
                                $value = \preg_replace('/\s{2,}/', ' ', $value);
                            if (isset($repo[self::P_WIDTH])
                                and \mb_strlen($value, Nls::$charset) > $repo[self::P_WIDTH]
                            )
                            {
                                $val = false;
                                $err = \sprintf(
                                    Text::dget(Nls::FW_DOMAIN, "value in '%s' must not exceed %u characters"),
                                    $label,
                                    $repo[self::P_WIDTH]
                                );
                            }
                            else
                            {
                                $val = $value;

                                if ($flags & self::F_EMAIL
                                    and ! \filter_var($val, \FILTER_VALIDATE_EMAIL)
                                )
                                {
                                    $val = false;
                                    $err = \sprintf(
                                        Text::dget(Nls::FW_DOMAIN, "value in '%s' does not represent an email address"),
                                        $label
                                    );
                                    break;
                                }

                                if ($flags & self::F_URL
                                    and ! \filter_var($val, \FILTER_VALIDATE_URL)
                                )
                                {
                                    $val = false;
                                    $err = \sprintf(
                                        Text::dget(Nls::FW_DOMAIN, "value in '%s' does not represent a web address"),
                                        $label
                                    );
                                    break;
                                }

                                if (isset($repo[self::P_VALIDATE_REGEXP])
                                    and ! \preg_match($repo[self::P_VALIDATE_REGEXP], $val)
                                )
                                {
                                    $val = false;
                                    $err = \sprintf(
                                        Text::dget(Nls::FW_DOMAIN, "value in '%s' does not match the required format"),
                                        $label
                                    );
                                    break;
                                }

                                if ($flags & self::F_UPPERCASE)
                                    $val = \mb_strtoupper($val, Nls::$charset);

                                if ($flags & self::F_UCFIRST)
                                    $val = \mb_strtoupper(\mb_substr($val, 0, 1, Nls::$charset), Nls::$charset).
                                        \mb_substr($val, 1, \strlen($val), Nls::$charset);

                                if ($flags & self::F_LOWERCASE)
                                    $val = \mb_strtolower($val, Nls::$charset);

                                if ($flags & self::F_TEL)
                                    $val = Misc::formatTel($val);
                            }
                        }
                        else
                            $val = false;
                        break;
                    case self::C_DATE:
                        if (\is_scalar($value))
                            $val = Nls::asDate($value, $flags & self::F_DATETIME);
                        else
                            $val = false;

                        if ($val === false)
                            $err = \sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' is not a valid date"), $label);
                        break;
                    case self::C_ENUM:
                        if (\is_scalar($value))
                            $val = isset($repo[self::P_ITEMS])
                                ? (isset($repo[self::P_ITEMS][$value]) ? $value : null)
                                : $value;
                        else
                            $val = false;
                        break;
                    case self::C_SET:
                        if (\is_scalar($value))
                            $value = \explode(',', $value);
                        $val = \implode(',', \array_keys(isset($repo[self::P_ITEMS])
                            ? \array_intersect_key($repo[self::P_ITEMS], \array_flip($value))
                            : \array_flip($value)
                        ));
                        break;
                    }

                    if ($flags & self::F_POSITIVE and $val < 1)
                    {
                        $val = false;
                        $err = \sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' must be positive"), $label);
                    }

                    if (empty($err)
                        and isset($repo[self::P_VALIDATE_CALLBACK])
                        and ($err = \call_user_func_array(
                            $repo[self::P_VALIDATE_CALLBACK],
                            array($val, $label)
                        )) !== true
                    )
                    {
                        $val = false;
                    }
                }
            }
            elseif (isset($repo[self::P_CLASS]) and $repo[self::P_CLASS] == self::C_FILE)
            {
                if (empty($_FILES[$fld]))
                {
                    $val = false;
                    $err = \sprintf(Text::dget(Nls::FW_DOMAIN, "incorrect file information in '%s'"), $label);
                }
                elseif ($_FILES[$fld]['error'] == UPLOAD_ERR_NO_FILE)
                    $val = null;
                elseif ($_FILES[$fld]['error'] != UPLOAD_ERR_OK)
                {
                    switch ($_FILES[$fld]['error'])
                    {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $msg = \sprintf(
                            Text::dget(
                                Nls::FW_DOMAIN,
                                "file too big (max allowed size %uMb) in '%s'"
                            ),
                            Misc::getMaxUploadSize() / 1048576,
                            $label
                        );
                        break;
                    default:
                        $msg = \sprintf(Text::dget(Nls::FW_DOMAIN, "error uploading file from '%s'"), $label);
                    }
                    $val = false;
                    $err = $msg." [err: {$_FILES[$fld]['error']}]";
                }
                elseif (! $_FILES[$fld]['size'])
                    $val = null;
                else
                    $val = $_FILES[$fld];
            }
            elseif (isset($repo[self::P_CLASS]) and $repo[self::P_CLASS] == self::C_BOOL)
                $val = 0;
            else
                $val = null;

            // check required flags
            if ($val === null and ! empty($repo[self::P_REQUIRED]))
                $err = \sprintf(Text::dget(Nls::FW_DOMAIN, "value is required in '%s'"), $label);

            if ($val !== false)
                self::$validated[$fld] = $val;
            else
                self::$input_errors[] = isset($err)
                    ? $err
                    : \sprintf(Text::dget(Nls::FW_DOMAIN, "type mismatch for '%s'"), $label);
        }

        return empty(self::$input_errors);
    }

    /** validates value to represent a number between 0 and 100
     * @param int $value    field value
     * @param string $label field name to use in error message
     * @return bool|string <i>true</i> on success, error message on validation error
     */
    public static function validatePct($value, $label)
    {
        return (0 <= $value and $value <= 100)
            ? true
            : \sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' must be between 0 and 100"), $label);
    }

    /** validates cents value to represent a number between 0 and 100
     * @param int $value    field value
     * @param string $label field name to use in error message
     * @return bool|string <i>true</i> on success, error message on validation error
     */
    public static function validatePct100($value, $label)
    {
        return (0 <= $value and $value <= 10000)
            ? true
            : \sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' must be between 0 and 100"), $label);
    }

    /** returns html representation of the field
     * @param string $name  field name
     * @param mixed $value  field value
     * @param array $repo   {field repository attributes}
     * @return string
     */
    public static function asHtmlStatic($name, $value, $repo=array())
    {
        if (isset(self::$store[$name]))
            $repo += self::$store[$name];
        if (isset($value))
        {
            // if class not provided return value as is
            if (empty($repo[self::P_CLASS]))
                return $value;

            switch ($repo[self::P_CLASS])
            {
            case self::C_TEXT:
                return (isset($repo[self::P_FLAGS]) and $repo[self::P_FLAGS] & self::F_TEXTAREA)
                    ? Html::encodeNl($value, $repo[self::P_FLAGS] & self::F_TEXT_FORMAT)
                    : Html::encode($value);
            case self::C_DATE:
                return Html::asDateNls($value, isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_DATETIME);
            case self::C_ENUM:
                $asis = isset($repo[self::P_FLAGS]) && ($repo[self::P_FLAGS] & self::F_ASIS);
                return (isset($repo[self::P_ITEMS]) && isset($repo[self::P_ITEMS][$value]))
                    ? (isset($repo[self::P_FLAGS])
                        ? ($repo[self::P_FLAGS] & self::F_ABBR
                            ? Html::asAbbr(
                                isset($repo[self::P_ITEMS_SHORT][$value])
                                    ? ($asis
                                        ? $repo[self::P_ITEMS_SHORT][$value]
                                        : Html::encode($repo[self::P_ITEMS_SHORT][$value])
                                    )
                                    : ($asis
                                        ? $repo[self::P_ITEMS][$value]
                                        : Html::encode($repo[self::P_ITEMS][$value])
                                    ),
                                isset($repo[self::P_ITEMS_LONG][$value])
                                    ? ($asis
                                        ? $repo[self::P_ITEMS_LONG][$value]
                                        : Html::encode($repo[self::P_ITEMS_LONG][$value])
                                    )
                                    : ($asis
                                        ? $repo[self::P_ITEMS][$value]
                                        : Html::encode($repo[self::P_ITEMS][$value])
                                    )
                            )
                            : Html::asEnum(
                                $value
                                , $asis
                                    ? $repo[self::P_ITEMS]
                                    : \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS])
                                , $repo[self::P_FLAGS] & self::F_ARRAY
                            )
                        )
                        : Html::asEnum(
                            $value,
                            \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS])
                        )
                    )
                    : '';
            case self::C_SET:
                return Html::asSet(
                    $value,
                    (isset($repo[self::P_FLAGS]) && ($repo[self::P_FLAGS] & self::F_ASIS))
                        ? $repo[self::P_ITEMS]
                        : \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS])
                );
            case self::C_ID:
            case self::C_INT:
                return (int)$value;
            case self::C_CENTS:
                return Html::asCents(
                    $value,
                    isset($repo[self::P_FLAGS])
                        ? (($repo[self::P_FLAGS] & self::F_SHOW_COMPACT)
                            ? null
                            : ($repo[self::P_FLAGS] & self::F_SHOW_COMPACT)
                        )
                        : false
                );
            case self::C_BOOL:
                // if P_ITEMS provided, must be array of 2 items
                return Html::asEnum(
                    (int)((bool)$value),
                    isset($repo[self::P_ITEMS])
                        ? ((isset($repo[self::P_FLAGS]) && ($repo[self::P_FLAGS] & self::F_ASIS))
                            ? $repo[self::P_ITEMS]
                            : array(
                                Html::encode($repo[self::P_ITEMS][0]),
                                Html::encode($repo[self::P_ITEMS][1]),
                            )
                        )
                        : array(
                            Text::dget(Nls::FW_DOMAIN, 'no'),
                            Text::dget(Nls::FW_DOMAIN, 'yes'),
                        )
                );
            case self::C_FILE:
                return isset($value['name']) ? Html::encode($value['name']) : '';
            default:
                return $value;
            }
        }
        else
            return '';
    }

    /** returns html representation of the field for input
     * @param string $name  field name
     * @param mixed $value  field value
     * @param array $input  {html input tag attributes}
     * @param array $repo   {field repository attributes}
     * @return string
     */
    public static function asHtmlInput($name, $value, $input=array(), $repo=array())
    {
        if (isset(self::$store[$name]))
            $repo += self::$store[$name];

        if (! \is_array($input))
            $input = (array)$input;

        // if class not provided return value as is
        if (empty($repo[self::P_CLASS]))
            return $value;

        switch ($repo[self::P_CLASS])
        {
        case self::C_TEXT:
            if (isset($repo[self::P_FLAGS]))
            {
                if ($repo[self::P_FLAGS] & self::F_TEXTAREA)
                    return Html::inputTextarea($input + array(
                        'name'=>$name,
                        'value'=>$value,
                        'maxlength'=>isset($repo[self::P_WIDTH]) ? $repo[self::P_WIDTH] : null
                    ));

                if ($repo[self::P_FLAGS] & self::F_PASSWORD)
                    $type = 'password';
                elseif ($repo[self::P_FLAGS] & self::F_EMAIL)
                    $type = 'email';
                elseif ($repo[self::P_FLAGS] & self::F_TEL)
                    $type = 'tel';
                else
                    $type = 'text';

                return Html::input($input + array(
                    'name'=>$name,
                    'value'=>$value,
                    'type'=>$type,
                    'maxlength'=>isset($repo[self::P_WIDTH]) ? $repo[self::P_WIDTH] : null
                ));
            }
            else
                return Html::inputText($input + array(
                    'name'=>$name,
                    'value'=>$value,
                    'maxlength'=>isset($repo[self::P_WIDTH]) ? $repo[self::P_WIDTH] : null
                ));
        case self::C_DATE:
            return Html::inputDate($input + array(
                'name'=>$name,
                'value'=>$value,
                Html::P_DATETIME=>isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_DATETIME
            ));
        case self::C_ENUM:
            return (isset($repo[self::P_ITEMS]))
                ? (isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_RADIO
                    ? Html::inputRadio($input + array(
                        'name'=>$name,
                        'value'=>$value,
                        Html::P_ITEMS=>($repo[self::P_FLAGS] & self::F_ASIS)
                            ? $repo[self::P_ITEMS]
                            : \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS]),
                        Html::P_TYPE=>isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_ARRAY
                            ? Html::T_ARRAY
                            : null,
                        Html::P_DELIM=>isset($repo[self::P_ITEM_DELIM])
                            ? $repo[self::P_ITEM_DELIM]
                            : null
                    ))
                    : Html::inputSelect($input + array(
                        'name'=>$name,
                        'value'=>$value,
                        Html::P_ITEMS=>isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_ASIS
                            ? $repo[self::P_ITEMS]
                            : \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS]),
                        Html::P_TYPE=>isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_ARRAY
                            ? Html::T_ARRAY
                            : null,
                        Html::P_BLANK=>isset($repo[self::P_ITEM_BLANK])
                            ? $repo[self::P_ITEM_BLANK]
                            : null
                    ))
                )
                : Html::inputSelect($input + array(
                    'name'=>$name,
                    'value'=>$value,
                    Html::P_ITEMS=>(isset($repo[self::P_FLAGS]) && ($repo[self::P_FLAGS] & self::F_ASIS))
                        ? $repo[self::P_ITEMS]
                        : \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS]),
                    Html::P_TYPE=>isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_ARRAY
                        ? Html::T_ARRAY
                        : null,
                    Html::P_BLANK=>isset($repo[self::P_ITEM_BLANK])
                        ? $repo[self::P_ITEM_BLANK]
                        : null
                ));
        case self::C_SET:
            return Html::inputSet($input + array(
                'name'=>$name,
                'value'=>$value,
                Html::P_ITEMS=>(isset($repo[self::P_FLAGS]) && ($repo[self::P_FLAGS] & self::F_ASIS))
                    ? $repo[self::P_ITEMS]
                    : \array_map(function ($item) {return Html::encode($item);}, $repo[self::P_ITEMS]),
                Html::P_TYPE=>(isset($repo[self::P_FLAGS])) && ($repo[self::P_FLAGS] & self::F_ARRAY)
                    ? Html::T_ARRAY
                    : null,
                Html::P_DELIM=>isset($repo[self::P_ITEM_DELIM])
                    ? $repo[self::P_ITEM_DELIM]
                    : null
            ));
        case self::C_ID:
        case self::C_INT:
            return Html::inputInt($input + array('name'=>$name, 'value'=>$value));
        case self::C_CENTS:
            return Html::inputCents($input + array('name'=>$name, 'value'=>$value));
        case self::C_BOOL:
            // if P_ITEMS provided, must be array of 2 items
            return Html::inputCheckbox($input + array(
                'name'=>$name,
                'checked'=>$value ? 'on' : null,
                Html::P_HEADER=>isset($repo[self::P_ITEMS])
                    ? ((isset($repo[self::P_FLAGS]) && ($repo[self::P_FLAGS] & self::F_ASIS))
                        ? $repo[self::P_ITEMS][1]
                        : Html::encode($repo[self::P_ITEMS][1])
                    )
                    : null
            ));
        case self::C_FILE:
            return Html::input($input + array('name'=>$name, 'type'=>'file'));
        default:
            return Html::input($input + array('name'=>$name, 'value'=>$value, 'type'=>'text'));
        }
    }

    /** return value for use in sql where clause
     * @param string $name  field name
     * @param mixed $value  field value
     * @param array $repo   {field repository attributes}
     * @return string
     */
    public static function asSql($name, $value, $repo=array())
    {
        $Rep = $repo + (isset(self::$store[$name]) ? self::$store[$name] : array());

        if (isset($Rep[self::P_WHERE_CALLBACK]))
            return \call_user_func_array($Rep[self::P_WHERE_CALLBACK], array($name, $value, $Rep));
        else
        {
            switch (isset($Rep[self::P_CLASS]) ? $Rep[self::P_CLASS] : self::C_TEXT)
            {
            case self::C_ID:
            case self::C_INT:
                return self::asSqlInt($name, (int)$value);
            case self::C_CENTS:
                return self::asSqlInt($name, (int)$value*100);
            case self::C_BOOL:
                return self::asSqlBool($name, (bool)$value);
            case self::C_DATE:
                return self::asSqlDate($name, $value, $Rep);
            case self::C_SET:
                return self::asSqlSet($name, $value);
            default:
                return self::asSqlText($name, $value);
            }
        }
    }

    /** returns sql condition for the integer field value
     * @param string $name  field name
     * @param mixed $value  field value
     * @param array $repo   {field repository attributes}
     * @return string
     */
    public static function asSqlInt($name, $value, $repo=array())
    {
        if (isset($value))
            return "$name = ".Db::escapeInt($value);
        else
            return "$name is null";
    }

    /** returns sql condition for the boolean field value
     * @param string $name  field name
     * @param mixed $value  field value
     * @return string
     */
    public static function asSqlBool($name, $value)
    {
        if (! empty($value))
            return $name;
        else
            return "not $name";
    }

    /** returns sql condition for the text field value
     * @param string $name  field name
     * @param mixed $value  field value
     * @return string
     */
    public static function asSqlText($name, $value)
    {
        if (isset($value))
            return "$name = ".Db::wrapChar($value);
        else
            return "$name is null";
    }

    /** returns sql condition for the set field where all (or some) values from
     * <code>$value</code> are present in a field
     * @param string $name  field name
     * @param array $value  field values
     * @param bool $all     whether all values must be present or only some [false]
     * @return string
     */
    public static function asSqlSet($name, $value, $all=false)
    {
        $cond = array();

        if (\is_array($value))
            foreach ($value as $v)
                $cond[] = 'find_in_set('.Db::wrapChar($v).",$name)";
        else
            $cond[] = 'find_in_set('.Db::wrapChar($value).",$name)";

        return '('.\implode($all ? ' and ' : ' or ', $cond).')';
    }

    /** returns sql condition for the date field value
     * @param string $name  field name
     * @param mixed $value  field value (possible values: '31/12/2012'
     *                      || '1/12/2012 - 31/12/2012' // spaces around - sign!
     *                      || '< 31/12/2012'
     *                      || '> 31/12/2012'
     *                      )
     * @param array $repo   {field repository attributes}
     * @return string
     */
    public static function asSqlDate($name, $value, $repo=array())
    {
        $datetime = isset($repo[self::P_FLAGS]) && $repo[self::P_FLAGS] & self::F_DATETIME;
        $matches = array();
        if (empty($value))
            return "$name is null";
        elseif (\preg_match('/^(\S+)\s+-\s+(\S+)$/', $value, $matches)
            and $d1 = Nls::asDate($matches[1], $datetime)
            and $d2 = Nls::asDate($matches[2], $datetime)
        )
        {
            if ($datetime and \substr($d2, -8) == '00:00:00')
                $d2 = \substr_replace($d2, '23:59:59', -8);
            return "$name between ".Db::wrapChar($d1).' and '.Db::wrapChar($d2);
        }
        elseif (\preg_match('/^<\s*(\S+)$/', $value, $matches)
            and $d = Nls::asDate($matches[1], $datetime)
        )
            return "$name < ".Db::wrapChar($d);
        elseif (\preg_match('/^>\s*(\S+)$/', $value, $matches)
            and $d = Nls::asDate($matches[1], $datetime)
        )
            return "$name > ".Db::wrapChar($d);
        elseif ($d = Nls::asDate($matches[1], $datetime))
            return "$name = ".Db::wrapChar($d);
        else
            return null;
    }
}
