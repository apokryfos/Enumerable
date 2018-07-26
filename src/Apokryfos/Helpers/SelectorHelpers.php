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
        if (array_key_exists($operator, self::$pemittedOperators)) {
            return function ($value, $key) use ($keySelector, $otherSelector, $operator) {
                $res = false;
                $selectedLHS = $keySelector($value,  $key);
                $selectedRHS = $otherSelector($value, $key);
                eval("\$res = \$selectedLHS $operator \$selectedRHS");
                return $res;
            };
        }


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

        return function($value, $key) use ($selector) {
            if ($value instanceof \ArrayAccess && $value->offsetExists($selector)) {
                return $value->offsetGet($selector);
            }
            if (!is_scalar($selector)) {
                return $selector;
            }
            if (method_exists($value, $selector)) {
                return $value->$selector();
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
        }
    }


}