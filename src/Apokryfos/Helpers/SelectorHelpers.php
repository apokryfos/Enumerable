<?php

namespace Apokryfos\Helpers;


class SelectorHelpers {

    private static $pemittedOperators = [
       "==" => 1, "===" => 1, ">=" => 1, "<=" => 1, ">" => 1, "<" => 1, "!=" => 1, "!==" => 1
    ];

    public static function whereSelector($key = null, $operator = null, $value = null) {
        $keySelector = self::selector($key);
        if (func_num_args() <= 1) {
            return $keySelector;
        }

        if (func_num_args() == 2) {
            $operator = "==";
            $otherSelector = self::selector($operator);
        } else {
            $otherSelector = self::selector($value);
        }
        $operator = $operator && array_key_exists($operator, self::$pemittedOperators) ? $operator : "==";
        return function ($value, $key) use ($keySelector, $otherSelector, $operator) {
            $selectedLHS = $keySelector($value,  $key);
            $selectedRHS = $otherSelector($value, $key);
            if ($operator == "==") { return $selectedLHS == $selectedRHS; }
            if ($operator == "===") { return $selectedLHS === $selectedRHS; }
            if ($operator == "!=") { return $selectedLHS != $selectedRHS; }
            if ($operator == "!==") { return $selectedLHS !== $selectedRHS; }
            if ($operator == "<") { return $selectedLHS < $selectedRHS; }
            if ($operator == ">") { return $selectedLHS > $selectedRHS; }
            if ($operator == "<=") { return $selectedLHS <= $selectedRHS; }
            if ($operator == ">=") { return $selectedLHS >= $selectedRHS; }
            return $selectedLHS == $selectedRHS;
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

    public static function setHelper($keyHandling = GeneratorHelpers::DIFF_ONLY_VALUE, $setOperation = "diff") {
        switch ($setOperation) {
            case "diff":
                return function ($value, $key, $value2, $key2) use ($keyHandling) {
                    if (($keyHandling==GeneratorHelpers::DIFF_BOTH && $key === $key2 && $value === $value2)
                        || ($value === $value2 && $keyHandling == GeneratorHelpers::DIFF_ONLY_VALUE)
                        || ($key === $key2 && $keyHandling == GeneratorHelpers::DIFF_ONLY_KEY)) {
                        return false;
                    }
                    return true;
                };
            case "intersect":
                return function ($value, $key, $value2, $key2) use ($keyHandling) {
                    if (($keyHandling==GeneratorHelpers::DIFF_BOTH && $key !== $key2 && $value !== $value2)
                        || ($value !== $value2 && $keyHandling == GeneratorHelpers::DIFF_ONLY_VALUE)
                        || ($key !== $key2 && $keyHandling == GeneratorHelpers::DIFF_ONLY_KEY)) {
                        return false;
                    }
                    return true;
                };
            default:
                return function () use ($keyHandling) {
                    return true;
                };
        }
    }


}