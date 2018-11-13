<?php

namespace Apokryfos\Helpers;


class SelectorHelpers {

    private static $permittedOperators = [
       "==" => 1, "===" => 1, ">=" => 1, "<=" => 1, ">" => 1, "<" => 1, "!=" => 1, "!==" => 1
    ];

    public static function normalizeWhereArguments($key = null, $operator = null, $value = null) {
        if (func_num_args() === 0) {
            $key = self::identity();
        } else {
            $key = self::selector($key);
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = "==";
        }
        if (!array_key_exists($operator, self::$permittedOperators)) {
            $operator == "==";
        }
        return [ $key, $operator, $value ];
    }

    public static function whereSelector($keySelector, $operator, $compareValue, $strict = false) {
        $strictRemap = [
            null => "===",
            "==" => "===",
            "!=" => "!=="
        ];

        if ($strict) {
            $operator = $strictRemap[$operator] ?? $operator;
        }

        $operator = $operator && array_key_exists($operator, self::$permittedOperators) ? $operator : "==";
        return function ($value, $key) use ($keySelector, $operator, $compareValue) {
            $selectedLHS = $keySelector($value,  $key);

            if ($operator == "==") { return $selectedLHS == $compareValue; }
            if ($operator == "===") { return $selectedLHS === $compareValue; }
            if ($operator == "!=") { return $selectedLHS != $compareValue; }
            if ($operator == "!==") { return $selectedLHS !== $compareValue; }
            if ($operator == "<") { return $selectedLHS < $compareValue; }
            if ($operator == ">") { return $selectedLHS > $compareValue; }
            if ($operator == "<=") { return $selectedLHS <= $compareValue; }
            if ($operator == ">=") { return $selectedLHS >= $compareValue; }
            return $selectedLHS == $compareValue;
        };
    }

    public static function constant($value) {
        return function () use ($value) {
            return $value;
        };
    }

    public static function identity() {
        return function($value) {
            return $value;
        };
    }

    public static function key() {
        return function($value, $key) {
            return $key;
        };
    }

    public static function selector($selector) {
        if ($selector == null) {
            return self::identity();
        }
        if (is_callable($selector)) {
            return $selector;
        }
        if (!is_scalar($selector)) {
            return self::constant($selector);
        }

        return function($value) use ($selector) {
            if (method_exists($value, $selector)) {
                return $value->$selector();
            }
            if (is_int($selector) || is_string($selector)) {
                if (is_array($value) && array_key_exists($selector, $value)) {
                    return $value[$selector];
                }
                if ($value instanceof \ArrayAccess && $value->offsetExists($selector)) {
                    return $value->offsetGet($selector);
                }
            }
            return $selector;
        };
    }

    public static function instanceOfSelector($class) {
        return function($value) use ($class) {
            return $value instanceof $class;
        };
    }



}