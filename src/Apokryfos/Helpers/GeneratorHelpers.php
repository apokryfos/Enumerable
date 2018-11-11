<?php


namespace Apokryfos\Helpers;


use Apokryfos\Exceptions\CombineSizeMismatchException;
use Apokryfos\Exceptions\MismatchException;
use Apokryfos\Exceptions\NotAnIteratorException;

class GeneratorHelpers {

    const ONLY_VALUE = 0b01;
    const ONLY_KEY = 0b10;
    const BOTH = 0b11;

    public static function asGenerator($iterable) {
        yield from $iterable;
    }

    public static function asNonAssociativeArray(\Generator $iterable) {
        $index = 0;
        foreach ($iterable as $value) {
            yield $index++ => $value;
        }
    }

    public static function skip(\Generator $generator, $size) {
        $count = 0;
        foreach ($generator as $key => $value) {
            if ($count < $size) {
                $count++;
                continue;
            }
            yield $key => $value;
        }
    }


    public static function take(\Generator $generator, $size) {
        $count = 0;
        foreach ($generator as $key => $value) {
            if ($count == $size) {
                break;
            }
            yield $key => $value;
            $count++;
        }
    }

    public static function chunk(\Generator $generator, $size) {
        if ($size == 0) {
            throw new \ArithmeticError("Set division by zero");
        }
        $currentChunk = [];
        $count = 0;
        foreach ($generator as $key => $value) {
            if ($count == $size) {
                yield $currentChunk;
                $currentChunk = [];
                $count = 0;
            }
            $currentChunk[$key] = $value;
            $count++;
        }
        if (!empty($currentChunk)) {
            yield $currentChunk;
        }
    }

    /**
     * @param \Generator $generator
     * @param bool $maintainKeys
     * @param int|null $levels
     * @return \Generator
     */
    public static function flatten(\Generator $generator, $maintainKeys = false, int $levels = null) {
        $levels = $levels ?: PHP_INT_MAX;
        $index = 0;
        foreach ($generator as $key => $value) {
            if (($value instanceof \Traversable || is_array($value)) && $levels >= 0) {
                foreach (
                    self::flatten(self::asGenerator($value), $maintainKeys, $levels - 1) as $innerKey =>
                    $innerValue
                ) {
                    yield ($maintainKeys ? $innerKey : $index++) => $innerValue;
                }
            } else {
                yield ($maintainKeys ? $key : $index++) => $value;
            }
        }
    }

    /**
     * @param \Generator $generator
     * @param $values
     * @param bool $strict
     * @return \Generator
     * @throws CombineSizeMismatchException
     * @throws NotAnIteratorException
     */
    public static function combine(\Generator $generator, $values, $strict = false) {
        $valuesIterator = is_array($values) ? new \ArrayIterator($values) : $values;
        if (!($valuesIterator instanceof \Iterator)) {
            throw new NotAnIteratorException("Expected an iterator but got a " . gettype($values));
        }
        $valuesIterator->rewind();
        foreach ($generator as $key) {
            if ($valuesIterator->valid()) {
                yield $key => $valuesIterator->current();
                $valuesIterator->next();
            } elseif (!$strict) {
                yield $key => null;
            } else { // Out of values but have more keys
                throw new CombineSizeMismatchException();
            }
        }
        $valuesIterator->next();
        if ($strict && $valuesIterator->valid()) { //Finished but have more values
            throw new CombineSizeMismatchException();
        }
    }

    public static function merge(\Generator $generator, $values, $renumber = true) {
        $index = 0;
        foreach ($generator as $key => $value) {
            if (is_int($key) && $renumber) {
                yield $index++ => $value;
            } else {
                yield $key => $value;
            }
        }
        foreach ($values as $key => $value) {
            if (is_int($key) && $renumber) {
                yield $index++ => $value;
            } else {
                yield $key => $value;
            }
        }
    }

    public static function crossJoin(\Generator $generator, ...$values) {
        if (count($values) == 0) {
            yield $generator;
        } else {
            yield from self::crossJoinStep($generator, ...$values);
        }
    }

    private static function crossJoinStep($head, ...$values) {
        foreach ($head as $value) {
            if (count($values) > 0) {
                foreach (self::crossJoinStep(...$values) as $joinValue) {
                    yield array_merge([$value], $joinValue);
                }
            } else {
                yield [$value];
            }
        }
    }

    public static function diff(\Generator $generator, $keyHandling = self::ONLY_VALUE, ...$values) {
        $shouldYield = function ($value, $key, $value2, $key2) use ($keyHandling) {
            if (($keyHandling==GeneratorHelpers::BOTH && $key === $key2 && $value === $value2)
                || ($value === $value2 && $keyHandling == GeneratorHelpers::ONLY_VALUE)
                || ($key === $key2 && $keyHandling == GeneratorHelpers::ONLY_KEY)) {
                return false;
            }
            return true;
        };
        foreach ($generator as $key => $value) {
            $yield = true;
            foreach ($values as $innerValue) {
                if ($yield) {
                    foreach ($innerValue as $key2 => $value2) {
                        $yield = $shouldYield($value, $key, $value2, $key2);
                        if (!$yield) {
                            break;
                        }
                    }
                }
            }
            if ($yield) {
                yield $key => $value;
            }
        }
    }


    public static function intersect(\Generator $generator, $keyHandling = self::ONLY_VALUE, ...$values) {
        $matches = function ($value, $key, $value2, $key2) use ($keyHandling) {
            if (($keyHandling==GeneratorHelpers::BOTH && $key === $key2 && $value === $value2)
                || ($value === $value2 && $keyHandling == GeneratorHelpers::ONLY_VALUE)
                || ($key === $key2 && $keyHandling == GeneratorHelpers::ONLY_KEY)) {
                return true;
            }
            return false;
        };
        foreach ($generator as $key => $value) {
            $matchCount = 0;
            foreach ($values as $innerValue) {
                foreach ($innerValue as $key2 => $value2) {
                    $yield = $matches($value, $key, $value2, $key2);
                    if ($yield) {
                        $matchCount++;
                        break;
                    }
                }

            }
            if ($matchCount == count($values)) {
                yield $key => $value;
            }
        }
    }

    /**
     * @param \Generator $generator
     * @param null $function
     * @return \Generator
     */
    public static function filter(\Generator $generator, $function = null) {
        $callback = SelectorHelpers::selector($function);
        foreach ($generator as $key => $value) {
            if ($callback($value, $key)) {
                yield $key => $value;
            }
        }
    }

    public static function flip(\Generator $generator) {
        foreach ($generator as $key => $value) {
            yield $value => $key;
        }
    }

    public static function keys(\Generator $generator) {
        foreach ($generator as $key => $value) {
            yield $key;
        }
    }

    public static function values(\Generator $generator) {
        foreach ($generator as $value) {
            yield $value;
        }
    }

    public static function mapWithKeys(\Generator $generator, $callback = null) {
        $callback = SelectorHelpers::selector($callback);
        $index = 0;
        foreach ($generator as $key => $item) {
            $result = $callback($item, $key, $index);
            if (is_array($result) && !empty($result)) {
                $resultKey = array_keys($result)[0];
                $resultValue = array_values($result)[0];
            } else {
                $resultKey = $key;
                $resultValue = $result;
            }
            yield  $resultKey => $resultValue;
            $index++;
        }
    }

    public static function map(\Generator $generator, $callback = null, $associative = false) {
        $callback = SelectorHelpers::selector($callback);
        $index = 0;
        foreach ($generator as $key => $item) {
            yield  ($associative ? $key : $index) => $callback($item, $key, $index);
            $index++;
        }
    }


    public static function generatorToArray(\Generator $generator) {
        $result = [];
        foreach ($generator as $key => $value) {
            if ($value instanceof \Traversable) {
                $result[$key] = self::generatorToArray(self::asGenerator($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public static function keyBy(\Generator $generator, $callback) {
        foreach ($generator as $key => $item) {
            yield $callback($item, $key) => $item;
        }
    }



    public static function prepend(\Generator $generator, $values) {
        $assoc = true;
        if (key($values) === 0) {
            $assoc = false;
        }
        if ($assoc) {
            yield from $values;
            yield from $generator;
        } else {
            $i = 0;
            foreach ($values as $key => $value) {
                yield ($i++) => $value;
            }
            foreach ($generator as $key => $value) {
                yield ($i++) => $value;
            }
        }

    }

    public static function splice(\Generator $generator, $offset, $length, $replacement = null) {
        for ($atIndex = 0; $atIndex < $offset; $atIndex++) {
            yield $generator->current();
            $generator->next();
        }
        foreach ($replacement ?? [] as $value) {
            yield $value;
        }
        for ($skipped = 0; $skipped < $length; $skipped++) {
            $generator->next();
        }
        foreach ($generator as $value) {
            yield $value;
        }
    }

    public static function slice(\Generator $generator, $offset, $length) {
        for ($atIndex = 0; $atIndex < $offset; $atIndex++) {
            $generator->next();
        }
        yield from self::take($generator, $length);
    }

    public static function nth(\Generator $generator, $n) {
        $i = 0;
        foreach ($generator as $key => $value) {
            if ($i % $n == 0) {
                yield $key => $value;
            }
            $i++;
        }
    }

    public static function pad(\Generator $generator, $n, $padding = null) {
        $count = 0;
        foreach ($generator as $key => $value) {
            yield $key => $value;
            $count++;
        }
        while ($count < $n) {
            yield $padding;
            $count++;
        }
    }

    public static function timesGenerator($n, $callback) {
        for ($i = 0; $i < $n; $i++) {
            yield $callback($i);
        }
    }

    public static function union(\Generator $generator, $array) {
        foreach ($generator as $key => $value) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
            yield $key => $value;
        }
        yield from $array;
    }

    /**
     * @param \Generator $generator
     * @param $array
     * @param bool $ignoreMismatches
     * @return \Generator
     * @throws MismatchException
     */
    public static function zip(\Generator $generator, $array, $ignoreMismatches = false) {
        foreach ($generator as $key => $item) {
            if (!array_key_exists($key, $array) && !$ignoreMismatches) {
                throw new MismatchException("Given array does not match all keys in the generator");
            } else {
                yield [ $item, $array[$key] ];
            }
        }
    }

}