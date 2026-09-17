<?php

/**
 * Same bytes as utf8_encode() (ISO-8859-1 → UTF-8) without the PHP 8.2+ deprecation.
 */
function latin1_to_utf8($value)
{
    if (!is_string($value)) {
        return $value;
    }
    if ($value === '') {
        return $value;
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
    if (function_exists('iconv')) {
        $converted = iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);
        return $converted === false ? $value : $converted;
    }
    return $value;
}
