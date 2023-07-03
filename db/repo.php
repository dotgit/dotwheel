<?php

/**
 * repository management.
 *
 * [type: library]
 *
 * @author stas trefilov
 */

namespace Dotwheel\Db;

use Dotwheel\Nls\Nls;
use Dotwheel\Nls\Text;
use Dotwheel\Ui\Html;
use Dotwheel\Util\Misc;

class Repo
{
    public const P_CLASS = 1;
    public const P_LABEL = 11;   // always html-encoded
    public const P_LABEL_SHORT = 12;
    public const P_LABEL_LONG = 13;
    public const P_WIDTH = 21;
    public const P_ITEMS = 31;
    public const P_ITEMS_SHORT = 32;
    public const P_ITEMS_LONG = 33;
    public const P_ITEM_BLANK = 35;   // for specifying the first blank item
    public const P_ITEM_DELIM = 36;   // for specifying items delimiter in enums and sets
    public const P_COMMENT = 41;   // for checkboxes in forms
    public const P_ALIAS = 51;
    public const P_VALIDATE_CALLBACK = 61;   // for input validation via user callback (callback function receives value
    // to validate, returns true if ok, error message otherwise)
    public const P_VALIDATE_REGEXP = 62;   // for input validation via regexp
    public const P_REQUIRED = 71;   // for specifying required fields
    public const P_WHERE_CALLBACK = 81;
    public const P_FLAGS = 91;

    public const C_ID = 1;
    public const C_INT = 2;
    public const C_CENTS = 3;
    public const C_BOOL = 4;
    public const C_TEXT = 5;
    public const C_DATE = 6;
    public const C_ENUM = 7;
    public const C_SET = 8;
    public const C_FILE = 9;

    public const F_POSITIVE = 0x00000001;
    public const F_HIDE_DECIMAL = 0x00000002;
    public const F_SHOW_COMPACT = 0x00000004;

    public const F_PASSWORD = 0x00000010;
    public const F_UPPERCASE = 0x00000020;
    public const F_LOWERCASE = 0x00000040;
    public const F_UCFIRST = 0x00000080;
    public const F_TEL = 0x00000100;
    public const F_EMAIL = 0x00000200;
    public const F_URL = 0x00000400;
    public const F_TEXTAREA = 0x00000800;
    public const F_TEXT_FORMAT = 0x00001000;

    public const F_DATETIME = 0x00010000;

    public const F_CHAR = 0x00100000;
    public const F_RADIO = 0x00200000;    // display as radio buttons instead of select dropdown
    public const F_INLINE_ITEMS = 0x00400000;    // display enum or set items on one line instead of stacked
    public const F_ABBR = 0x00800000;    // using (P_ITEMS_SHORT || P_ITEMS) + (P_ITEMS_LONG || P_ITEMS) for abbr tag

    public const F_ASIS = 0x10000000;

    /**
     * var array fields description in the form:
     * {field: {PARAM_LABEL:'User last name', PARAM_CLASS:CLASS_TEXT, PARAM_FLAGS:FLAG_UPPERCASE, etc...}}
     */
    public static array $store = [];
    /** var array list of loaded packages */
    public static array $packages = [];
    /** var array list of unresolved aliases to lookup after each addPackage */
    public static array $unresolved = [];
    /** var array list of validated request variables */
    public static array $validated = [];
    /** var array list of errors during import */
    public static array $input_errors = [];


    /** add new package to repository (resolve aliases while adding). if the package already exists then skip.
     *
     * @param string $package package name
     * @param array $fields {fld:{repository field params}, ...}
     * @return bool whether the package was added
     */
    public static function registerPackage(string $package, array $fields): bool
    {
        // do not register twice
        if (isset(self::$packages[$package])) {
            return false;
        }

        // add fields resolving aliases
        foreach ($fields as $name => $repo) {
            if (isset($repo[self::P_ALIAS])) {
                $ref = $repo[self::P_ALIAS];
                if (isset(self::$store[$ref])) {
                    $repo += self::$store[$ref];
                } elseif (isset(self::$unresolved[$ref])) {
                    self::$unresolved[$ref][$name] = true;
                } else {
                    self::$unresolved[$ref] = [$name => true];
                }
            }
            self::$store[$name] = $repo;
        }

        // check unresolved links
        foreach (self::$unresolved as $ref => $names) {
            if (isset(self::$store[$ref])) {
                foreach ($names as $name => $_) {
                    self::$store[$name] += self::$store[$ref];
                }
                unset(self::$unresolved[$ref]);
            }
        }

        // register package
        self::$packages[$package] = true;

        return true;
    }

    /** specified field parameter if set
     *
     * @param ?string $name field name
     * @param string|int $param field parameter
     * @param array $repo {field repository attributes}
     * @return mixed
     */
    public static function getParam(?string $name, $param, array $repo = [])
    {
        return $repo[$param] ?? self::$store[$name][$param] ?? null;
    }

    /** specified html-escaped label if set (otherwise the P_LABEL)
     *
     * @param ?string $name field name
     * @param ?int $param which label to return
     * @param array $repo {field repository attributes}
     * @return ?string
     */
    public static function getLabel(?string $name, ?int $param = null, array $repo = []): ?string
    {
        if (isset(self::$store[$name])) {
            $repo += self::$store[$name];
        }
        switch ($param) {
            case self::P_LABEL_SHORT:
                return $repo[self::P_LABEL_SHORT] ?? $repo[self::P_LABEL] ?? null;
            case self::P_LABEL_LONG:
                return $repo[self::P_LABEL_LONG] ?? $repo[self::P_LABEL] ?? null;
            default:
                return $repo[self::P_LABEL] ?? null;
        }
    }

    /** specified list if set (otherwise the PARAM_LIST)
     *
     * @param ?string $name field name
     * @param ?int $param which list to return
     * @param array $repo {field repository attributes}
     * @return ?array
     */
    public static function getList(?string $name, ?int $param = null, array $repo = []): ?array
    {
        if (isset(self::$store[$name])) {
            $repo += self::$store[$name];
        }
        switch ($param) {
            case self::P_ITEMS_SHORT:
                return $repo[self::P_ITEMS_SHORT] ?? $repo[self::P_ITEMS] ?? [];
            case self::P_ITEMS_LONG:
                return $repo[self::P_ITEMS_LONG] ?? $repo[self::P_ITEMS] ?? [];
            default:
                return $repo[self::P_ITEMS] ?? [];
        }
    }

    /** field repository attributes
     *
     * @param ?string $name field name
     * @param array $repo {field repository attributes}
     * @return array
     */
    public static function get(?string $name, array $repo = []): array
    {
        return isset(self::$store[$name]) ? $repo + self::$store[$name] : $repo;
    }

    /** whether repository entry specifies numbers that can be added / subtracted
     *
     * @param array $repo {field repository attributes}
     * @return bool
     */
    public static function isArithmetical(array $repo): bool
    {
        switch ($repo[self::P_CLASS] ?? null) {
            case self::C_CENTS:
            case self::C_INT:
                return true;
            default:
                return false;
        }
    }

    /** whether repository entry specifies a date field
     *
     * @param array $repo {field repository attributes}
     * @return bool
     */
    public static function isDate(array $repo): bool
    {
        return ($repo[self::P_CLASS] ?? null) === self::C_DATE;
    }

    /** whether repository entry specifies textual data
     *
     * @param array $repo {field repository attributes}
     * @return bool
     */
    public static function isTextual(array $repo): bool
    {
        return ($repo[self::P_CLASS] ?? self::C_TEXT) === self::C_TEXT;
    }

    /** store validated input values in Repo::$validated public var. if errors occur during validation then
     * Repository::$input_errors array contains error messages.
     *
     * @param array $fields {field1:{repository parameters}, field2:{repository parameters}}
     * @param ?array $values {field1:val1, field2:val2, ...} if omitted then uses $_REQUEST
     * @return bool whether validation has passed ok (no input errors)
     */
    public static function validateInput(array $fields, ?array $values = null): bool
    {
        if (empty($values)) {
            $values = $_REQUEST;
        }

        self::$input_errors = [];

        foreach ($fields as $fld => $params) {
            if (is_scalar($params)) {
                $params = [$params => true];
            }
            $repo = self::get($fld, $params);

            $err = null;
            $label = $repo[self::P_LABEL] ?? '';
            $value = isset($values[$fld])
                ? (is_scalar($values[$fld])
                    ? trim($values[$fld])
                    : ($values[$fld] ?: null)
                )
                : null;

            if (($repo[self::P_CLASS] ?? null) === self::C_BOOL) {
                $val = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($val === null) {
                    $val = false;
                    $err = sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' is not a boolean"), $label);
                } else {
                    $val = (int)$val;
                }
            } elseif (isset($value)) {
                // convert value
                if ($value === '') {
                    $val = null;
                } else {
                    $flags = $repo[self::P_FLAGS] ?? null;
                    switch ($repo[self::P_CLASS] ?? self::C_TEXT) {
                        case self::C_ID:
                            $val = (int)$value;
                            $flags |= self::F_POSITIVE;
                            break;
                        case self::C_INT:
                            $val = is_numeric($value) ? (int)$value : false;
                            break;
                        case self::C_CENTS:
                            if (!is_scalar($value)) {
                                $val = false;
                            } elseif (!is_numeric(str_replace([' ', ' ', '.', ','], '', $value))) {
                                $val = false;
                            } else {
                                $value = str_replace([' ', ' ', '.'], ['', '', ','], $value);
                                $m = [];
                                if (preg_match('/^(.*),(\d{1,2})$/', $value, $m)) {
                                    $val = str_replace(',', '', $m[1]) . $m[2] . (isset($m[2][1]) ? '' : '0');
                                } else {
                                    $val = str_replace(',', '', $value) . '00';
                                }
                                $val = (int)$val;
                            }
                            break;
                        case self::C_TEXT:
                            if (is_scalar($value)) {
                                if (!($flags & self::F_TEXTAREA)) {
                                    $value = preg_replace('/\s{2,}/', ' ', $value);
                                }
                                if (isset($repo[self::P_WIDTH])
                                    and mb_strlen($value, Nls::$charset) > $repo[self::P_WIDTH]
                                ) {
                                    $val = false;
                                    $err = sprintf(
                                        Text::dget(Nls::FW_DOMAIN, "value in '%s' must not exceed %u characters"),
                                        $label,
                                        $repo[self::P_WIDTH]
                                    );
                                } else {
                                    $val = $value;

                                    if ($flags & self::F_EMAIL
                                        and !filter_var($val, FILTER_VALIDATE_EMAIL)
                                    ) {
                                        $val = false;
                                        $err = sprintf(
                                            Text::dget(
                                                Nls::FW_DOMAIN,
                                                "value in '%s' does not represent an email address"
                                            ),
                                            $label
                                        );
                                        break;
                                    }

                                    if ($flags & self::F_URL
                                        and !filter_var($val, FILTER_VALIDATE_URL)
                                    ) {
                                        $val = false;
                                        $err = sprintf(
                                            Text::dget(
                                                Nls::FW_DOMAIN,
                                                "value in '%s' does not represent a web address"
                                            ),
                                            $label
                                        );
                                        break;
                                    }

                                    if (isset($repo[self::P_VALIDATE_REGEXP])
                                        and !preg_match($repo[self::P_VALIDATE_REGEXP], $val)
                                    ) {
                                        $val = false;
                                        $err = sprintf(
                                            Text::dget(
                                                Nls::FW_DOMAIN,
                                                "value in '%s' does not match the required format"
                                            ),
                                            $label
                                        );
                                        break;
                                    }

                                    if ($flags & self::F_UPPERCASE) {
                                        $val = mb_strtoupper($val, Nls::$charset);
                                    }

                                    if ($flags & self::F_UCFIRST) {
                                        $val = mb_convert_case($val, MB_CASE_TITLE, Nls::$charset);
                                    }

                                    if ($flags & self::F_LOWERCASE) {
                                        $val = mb_strtolower($val, Nls::$charset);
                                    }

                                    if ($flags & self::F_TEL) {
                                        $val = Misc::formatTel($val);
                                    }
                                }
                            } else {
                                $val = false;
                            }
                            break;
                        case self::C_DATE:
                            if (is_scalar($value)) {
                                $val = Nls::toDate($value, $flags & self::F_DATETIME);
                            } else {
                                $val = false;
                            }

                            if ($val === false) {
                                $err = sprintf(
                                    Text::dget(Nls::FW_DOMAIN, "value in '%s' is not a valid date"),
                                    $label
                                );
                            }
                            break;
                        case self::C_ENUM:
                            if (is_scalar($value)) {
                                $val = isset($repo[self::P_ITEMS])
                                    ? (isset($repo[self::P_ITEMS][$value]) ? $value : false)
                                    : $value;
                            } else {
                                $val = false;
                            }

                            if ($val === false) {
                                $err = sprintf(
                                    Text::dget(Nls::FW_DOMAIN, "value in '%s' is not an option"),
                                    $label
                                );
                            }
                            break;
                        case self::C_SET:
                            if (is_scalar($value)) {
                                $value = explode(',', $value);
                            }

                            $val = implode(
                                ',',
                                array_keys(
                                    isset($repo[self::P_ITEMS])
                                        ? array_intersect_key($repo[self::P_ITEMS], array_flip($value))
                                        : array_flip($value)
                                )
                            );

                            if ($val === '') {
                                $val = null;
                            }
                            break;
                        default:
                            $val = null;
                    }

                    if ($flags & self::F_POSITIVE and $val < 1) {
                        $val = false;
                        $err = sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' must be positive"), $label);
                    }

                    if (empty($err)
                        and isset($repo[self::P_VALIDATE_CALLBACK])
                        and ($err = call_user_func_array($repo[self::P_VALIDATE_CALLBACK], [$val, $label])) !== true
                    ) {
                        $val = false;
                    }
                }
            } elseif (($repo[self::P_CLASS] ?? null) === self::C_FILE) {
                if (empty($_FILES[$fld])) {
                    $val = false;
                    $err = sprintf(
                        Text::dget(Nls::FW_DOMAIN, "too big file in '%s' (maximal allowed file size: %s)"),
                        $label,
                        Misc::humanBytes(Misc::getMaxUploadSize())
                    );
                } elseif ($_FILES[$fld]['error'] == UPLOAD_ERR_NO_FILE) {
                    $val = null;
                } elseif ($_FILES[$fld]['error'] != UPLOAD_ERR_OK) {
                    switch ($_FILES[$fld]['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $msg = sprintf(
                                Text::dget(Nls::FW_DOMAIN, "too big file in '%s' (maximal allowed file size: %s)"),
                                $label,
                                Misc::humanBytes(Misc::getMaxUploadSize())
                            );
                            break;
                        default:
                            $msg = sprintf(
                                Text::dget(Nls::FW_DOMAIN, "error uploading file from '%s'"),
                                $label
                            );
                    }
                    $val = false;
                    $err = $msg . " [err: {$_FILES[$fld]['error']}]";
                } elseif (!$_FILES[$fld]['size']) {
                    $val = null;
                } else {
                    $val = $_FILES[$fld];
                }
            } else {
                $val = null;
            }

            // check required flags
            if ($val === null and !empty($repo[self::P_REQUIRED])) {
                self::$input_errors[] = sprintf(Text::dget(Nls::FW_DOMAIN, "value is required in '%s'"), $label);
            }

            if ($val !== false) {
                self::$validated[$fld] = $val;
            } else {
                self::$input_errors[] = $err ?? sprintf(Text::dget(Nls::FW_DOMAIN, "type mismatch for '%s'"), $label);
            }
        }

        return empty(self::$input_errors);
    }

    /** html representation of the field
     *
     * @param ?string $name field name
     * @param mixed $value field value
     * @param array $repo {field repository attributes}
     * @return string
     */
    public static function asHtmlStatic(?string $name, $value, array $repo = []): string
    {
        if (isset(self::$store[$name])) {
            $repo += self::$store[$name];
        }
        if (isset($value)) {
            // if class not provided return value as is
            if (empty($repo[self::P_CLASS])) {
                return $value;
            }

            switch ($repo[self::P_CLASS]) {
                case self::C_TEXT:
                    return (($repo[self::P_FLAGS] ?? 0) & self::F_TEXTAREA)
                        ? Html::encodeNl($value, $repo[self::P_FLAGS] & self::F_TEXT_FORMAT)
                        : Html::encode($value);
                case self::C_DATE:
                    return Html::asDateNls(
                        $value,
                        ($repo[self::P_FLAGS] ?? 0) & self::F_DATETIME
                    );
                case self::C_ENUM:
                    if (isset($repo[self::P_FLAGS])) {
                        $asis = $repo[self::P_FLAGS] & self::F_ASIS;
                        $abbr = $repo[self::P_FLAGS] & self::F_ABBR;
                    } else {
                        $asis = false;
                        $abbr = false;
                    }
                    $items = $repo[self::P_ITEMS] ?? [];
                    return $abbr
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
                        : self::enumToString($value, $items, !$asis);
                case self::C_SET:
                    return self::setToString(
                        $value,
                        $repo[self::P_ITEMS] ?? [],
                        !(($repo[self::P_FLAGS] ?? 0) & self::F_ASIS)
                    );
                case self::C_ID:
                case self::C_INT:
                    if (!is_numeric($value)) {
                        return '';
                    }
                    $val = Html::asCents($value * 100, false);
                    return (($repo[self::P_FLAGS] ?? 0) & self::F_ASIS)
                        ? str_replace('&nbsp;', ' ', $val)
                        : $val;
                case self::C_CENTS:
                    if (!is_numeric($value)) {
                        return '';
                    }
                    $val = Html::asCents(
                        $value,
                        ($repo[self::P_FLAGS] ?? 0) & self::F_SHOW_COMPACT
                            ? null
                            : !(($repo[self::P_FLAGS] ?? 0) & self::F_HIDE_DECIMAL)
                    );
                    return ($repo[self::P_FLAGS] ?? 0) & self::F_ASIS
                        ? str_replace('&nbsp;', ' ', $val)
                        : $val;
                case self::C_BOOL:
                    // if P_ITEMS provided, must be array of 2 items
                    return self::enumToString(
                        (int)((bool)$value),
                        $repo[self::P_ITEMS] ?? [
                            Text::dget(Nls::FW_DOMAIN, 'no'),
                            Text::dget(Nls::FW_DOMAIN, 'yes'),
                        ],
                        !(($repo[self::P_FLAGS] ?? 0) & self::F_ASIS)
                    );
                case self::C_FILE:
                    return isset($value['name'])
                        ? (($repo[self::P_FLAGS] ?? 0) & self::F_ASIS
                            ? $value['name']
                            : Html::encode($value['name'])
                        )
                        : '';
                default:
                    return $value;
            }
        } else {
            return '';
        }
    }

    /** returns html representation of the field for input
     *
     * @param ?string $name field name
     * @param mixed $value field value
     * @param mixed $input {html input tag attributes}
     * @param array $repo {field repository attributes}
     * @return string
     */
    public static function asHtmlInput(?string $name, $value, $input = [], array $repo = []): string
    {
        if (isset(self::$store[$name])) {
            $repo += self::$store[$name];
        }

        if (!is_array($input)) {
            $input = (array)$input;
        }

        // if class not provided return value as is
        if (empty($repo[self::P_CLASS])) {
            return (string)$value;
        }

        switch ($repo[self::P_CLASS]) {
            case self::C_TEXT:
                if (isset($repo[self::P_FLAGS])) {
                    if ($repo[self::P_FLAGS] & self::F_TEXTAREA) {
                        return Html::inputTextarea(
                            $input + [
                                'name' => $name,
                                'value' => $value,
                                'maxlength' => $repo[self::P_WIDTH] ?? null,
                            ]
                        );
                    }

                    if ($repo[self::P_FLAGS] & self::F_PASSWORD) {
                        $type = 'password';
                    } elseif ($repo[self::P_FLAGS] & self::F_EMAIL) {
                        $type = 'email';
                    } elseif ($repo[self::P_FLAGS] & self::F_TEL) {
                        $type = 'tel';
                    } else {
                        $type = 'text';
                    }

                    return Html::input(
                        $input + [
                            'name' => $name,
                            'value' => $value,
                            'type' => $type,
                            'maxlength' => $repo[self::P_WIDTH] ?? null,
                        ]
                    );
                } else {
                    return Html::inputText(
                        $input + [
                            'name' => $name,
                            'value' => $value,
                            'maxlength' => $repo[self::P_WIDTH] ?? null,
                        ]
                    );
                }
            case self::C_DATE:
                return Html::inputDate(
                    $input + [
                        'name' => $name,
                        'value' => $value,
                        Html::P_DATETIME => ($repo[self::P_FLAGS] ?? 0) & self::F_DATETIME,
                    ]
                );
            case self::C_ENUM:
                $items = $repo[self::P_ITEMS] ?? [];
                if (!(($repo[self::P_FLAGS] ?? 0) & self::F_ASIS)) {
                    foreach ($items as &$item) {
                        $item = Html::encode($item);
                    }
                }
                return (($repo[self::P_FLAGS] ?? 0) & self::F_RADIO)
                    ? Html::inputRadio(
                        $input + [
                            'name' => $name,
                            'value' => $value,
                            Html::P_ITEMS => $items,
                            Html::P_DELIM => $repo[self::P_ITEM_DELIM] ?? null,
                        ]
                    )
                    : Html::inputSelect(
                        $input + [
                            'name' => $name,
                            'value' => $value,
                            Html::P_ITEMS => $items,
                            Html::P_BLANK => $repo[self::P_ITEM_BLANK] ?? null,
                        ]
                    );
            case self::C_SET:
                $items = $repo[self::P_ITEMS] ?? [];
                if (!(($repo[self::P_FLAGS] ?? 0) & self::F_ASIS)) {
                    foreach ($items as &$item) {
                        $item = Html::encode($item);
                    }
                }
                return Html::inputSet(
                    $input + [
                        'name' => $name,
                        'value' => $value,
                        Html::P_ITEMS => $items,
                        Html::P_DELIM => $repo[self::P_ITEM_DELIM] ?? null,
                    ]
                );
            case self::C_ID:
            case self::C_INT:
                return Html::inputInt($input + ['name' => $name, 'value' => $value]);
            case self::C_CENTS:
                return Html::inputCents($input + ['name' => $name, 'value' => $value]);
            case self::C_BOOL:
                // if P_ITEMS provided, must be array of 2 items
                return Html::inputCheckbox(
                    $input + [
                        'name' => $name,
                        'checked' => $value ? 'on' : null,
                        Html::P_HEADER => isset($repo[self::P_ITEMS])
                            ? (($repo[self::P_FLAGS] ?? 0) & self::F_ASIS
                                ? $repo[self::P_ITEMS][1]
                                : Html::encode($repo[self::P_ITEMS][1])
                            )
                            : null,
                    ]
                );
            case self::C_FILE:
                return Html::input($input + ['name' => $name, 'type' => 'file']);
            default:
                return Html::input($input + ['name' => $name, 'value' => $value, 'type' => 'text']);
        }
    }

    /** representation of a list item: 'On'
     *
     * @param ?string $value 'x'
     * @param array $items {'x':'On', '':'Off'}
     * @param bool $encode whether to html encode the result
     * @return string
     */
    public static function enumToString(?string $value, array $items = [], bool $encode = true): string
    {
        return isset($items[$value])
            ? ($encode ? Html::encode($items[$value]) : $items[$value])
            : '';
    }

    /** html representation of set: 'High, Low'
     *
     * @param ?string $value 'a,c'
     * @param array $items {a:High, b:Normal, c:Low} (array values will be html-escaped)
     * @param bool $encode whether to html encode the result
     * @return string
     */
    public static function setToString(?string $value, array $items = [], bool $encode = true)
    {
        if ($set = array_flip(explode(',', $value))) {
            if ($encode) {
                $res = Html::encode(
                    implode(
                        Nls::$formats[Nls::P_LIST_DELIM] . ' ',
                        array_intersect_key($items, $set)
                    )
                );
                return Nls::$formats[Nls::P_LIST_DELIM] == Nls::$formats[Nls::P_LIST_DELIM_HTML]
                    ? $res
                    : str_replace(Nls::$formats[Nls::P_LIST_DELIM], Nls::$formats[Nls::P_LIST_DELIM_HTML], $res);
            } else {
                return implode(
                    Nls::$formats[Nls::P_LIST_DELIM] . ' ',
                    array_intersect_key($items, $set)
                );
            }
        } else {
            return '';
        }
    }

    /** validate cents value to represent a number between 0 and 100
     *
     * @param int|string $value field value
     * @param ?string $label field name to use in error message
     * @return bool|string <i>true</i> on success, error message on validation error
     */
    public static function validatePct100($value, ?string $label)
    {
        return (is_numeric($value) and 0 <= $value and $value <= 100)
            ? true
            : sprintf(Text::dget(Nls::FW_DOMAIN, "value in '%s' must be between 0 and 100"), $label);
    }

    /** value for use in sql where clause
     *
     * @param ?string $name field name
     * @param mixed $value field value
     * @param array $repo {field repository attributes}
     * @return string|null|false
     */
    public static function asSql(?string $name, $value, array $repo = [])
    {
        $Rep = $repo + (self::$store[$name] ?? []);

        if (isset($Rep[self::P_WHERE_CALLBACK])) {
            return call_user_func_array($Rep[self::P_WHERE_CALLBACK], [$name, $value, $Rep]);
        } elseif (isset($name)) {
            switch ($Rep[self::P_CLASS] ?? self::C_TEXT) {
                case self::C_ID:
                case self::C_INT:
                    return self::asSqlInt($name, $value);
                case self::C_CENTS:
                    return self::asSqlInt($name, is_numeric($value) ? $value * 100 : $value);
                case self::C_BOOL:
                    return self::asSqlBool($name, $value);
                case self::C_DATE:
                    return self::asSqlDate($name, $value, $Rep);
                case self::C_SET:
                    return self::asSqlSet($name, $value);
                default:
                    return self::asSqlText($name, $value);
            }
        } else {
            return false;
        }
    }

    /** sql condition for the integer field value
     *
     * @param string $name field name
     * @param mixed $value field value
     * @return string|null|false
     */
    public static function asSqlInt(string $name, $value)
    {
        if (is_numeric($value)) {
            return "$name=" . Db::escapeInt($value);
        } else {
            return empty($value) ? null : false;
        }
    }

    /** sql condition for the boolean field value
     *
     * @param string $name field name
     * @param mixed $value field value
     * @return ?string
     */
    public static function asSqlBool(string $name, $value): ?string
    {
        if (isset($value)) {
            return !empty($value)
                ? $name
                : "not $name";
        } else {
            return null;
        }
    }

    /** sql condition for the text field value
     *
     * @param string $name field name
     * @param mixed $value field value
     * @return ?string
     */
    public static function asSqlText(string $name, $value): ?string
    {
        return isset($value)
            ? "$name=" . Db::wrapChar($value)
            : null;
    }

    /** sql condition for the set field where all (or some) values from
     * <code>$value</code> are present in a field
     *
     * @param string $name field name
     * @param array|string $value field values
     * @param bool $all whether all values must be present or only some [false]
     * @return string
     */
    public static function asSqlSet(string $name, $value, bool $all = false): ?string
    {
        $cond = [];

        foreach ((array)$value as $v) {
            if (isset($v)) {
                $cond[] = 'find_in_set(' . Db::wrapChar($v) . ",$name)";
            }
        }

        switch (count($cond)) {
            case 0:
                return null;
            case 1:
                return $cond[0];
            default:
                return '(' . implode($all ? 'and ' : 'or ', $cond) . ')';
        }
    }

    /** sql condition for the date field value
     *
     * @param string $name field name
     * @param mixed $value field value (possible values:
     *  '31/12/2012'
     *  || '1/12/2012 - 31/12/2012' // spaces around - sign!
     *  || '< 31/12/2012'
     *  || '> 31/12/2012'
     *  )
     * @param array $repo {field repository attributes}
     * @return string|null|false
     */
    public static function asSqlDate(string $name, $value, array $repo = [])
    {
        $datetime = ($repo[self::P_FLAGS] ?? 0) & self::F_DATETIME;
        $matches = [];

        if (empty($value)) {
            return null;
        } elseif (preg_match('/^(\S+)\s+-\s+(\S+)$/', $value, $matches)
            and $d1 = Nls::toDate($matches[1], $datetime)
            and $d2 = Nls::toDate($matches[2], $datetime)
        ) {
            if ($datetime and substr($d2, -8) == '00:00:00') {
                $d2 = substr_replace($d2, '23:59:59', -8);
            }
            return sprintf("%s between%sand%s", $name, Db::wrapChar($d1), Db::wrapChar($d2));
        } elseif (preg_match('/^<\s*(\S+)$/', $value, $matches)
            and $d = Nls::toDate($matches[1], $datetime)
        ) {
            return "$name<" . Db::wrapChar($d);
        } elseif (preg_match('/^>\s*(\S+)$/', $value, $matches)
            and $d = Nls::toDate($matches[1], $datetime)
        ) {
            return "$name>" . Db::wrapChar($d);
        } elseif ($d = Nls::toDate($value, $datetime)) {
            return "$name=" . Db::wrapChar($d);
        } else {
            return false;
        }
    }
}
