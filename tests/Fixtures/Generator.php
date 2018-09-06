<?php

namespace Tests\Fixtures;


class Generator {

    private static $defaultValuesPool = "ABCDEFGHIJKLMNOPQRSTWXYZabcdefghijklmnopqrstwxwy012345679";

    public static function randomValue($values = null, $minimumValueLength = 1, $maximumValueLength = 10, $nullProbability = 0) {
        if (rand(1, 1000000)/1000000 <= $nullProbability) {
            return null;
        }
        $pool = str_split($values ?? self::$defaultValuesPool);
        shuffle($pool);
        return implode("", array_slice($pool, 0, rand($minimumValueLength, $maximumValueLength)));
    }

    public static function randomArray($size = 10, $values = null, $minimumValueLength = 1, $maximumValueLength = 10, $randomKeys = false, $nullProbability = 0) {
        $result = [];
        while (count($result) < $size) {
            $value = self::randomValue($values, $minimumValueLength, $maximumValueLength, $nullProbability);
            if ($randomKeys) {
                $result[self::randomValue($values, $minimumValueLength, $maximumValueLength)] = $value;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    public static function randomKeyedArray($size = 10) {
        return self::randomArray($size, self::$defaultValuesPool, 1, 10, true);
    }

    public static function randomSmallNumbersArray($size=10, $nullProbablilty = 0) {
        return array_map('intval', self::randomArray($size, "0123456789", 1, 3, false, $nullProbablilty));
    }

    public static function randomNumbersArray($size=10, $nullProbablilty = 0) {
        return array_map('intval', self::randomArray($size, "0123456789", 1, 10, false, $nullProbablilty));
    }

    public static function randomGrid($width, $height, $size = 10, $values = null, $minimumValueLength = 1, $maximumValueLength = 10) {
        return self::randomGridCallback($width, $height, function () use ($size, $values, $minimumValueLength, $maximumValueLength) {
            return self::randomArray($size, $values, $minimumValueLength, $maximumValueLength);
        });
    }

    public static function randomGridCallback($width, $height, callable  $callback) {
        $res = [];
        for ($i = 0;$i < $height;$i++) {
            $res[$i] = [];
            for ($j = 0;$j < $width;$j++) {
                $res[$i][$j] = $callback($i, $j);
            }
        }
        return $res;
    }


}