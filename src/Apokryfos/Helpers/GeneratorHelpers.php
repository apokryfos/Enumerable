<?php


namespace Apokryfos\Helpers;


use Apokryfos\Exceptions\CombineSizeMismatchException;
use Apokryfos\Exceptions\MismatchException;
use Apokryfos\Exceptions\NotAnIteratorException;

class GeneratorHelpers {

    const DIFF_ONLY_VALUE = 0b01;
    const DIFF_ONLY_KEY = 0b10;
    const DIFF_BOTH = 0b11;

    public static function chain($generator) : GeneratorChain {
        return new GeneratorChain($generator);
    }

    public static function asGenerator($iterable) {
        yield from $iterable;
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
        $levels = $levels ? : PHP_INT_MAX;
        $index = 0;
        foreach ($generator as $key => $value) {
            if (($value instanceof \Traversable || is_array($value)) && $levels >= 0) {
                foreach (self::flatten(self::chain($value), $maintainKeys, $levels - 1) as $innerKey => $innerValue) {
                    yield ($maintainKeys ? $innerKey : $index++) => $innerValue;
                }
            } else {
                yield ($maintainKeys ? $key : $index++) => $value;
            }
        }
    }

    public static function combine(\Generator $generator, $values, $strict = false) {
        $valuesIterator = is_array($values) ? new \ArrayIterator($values) : $values;
        if (!($valuesIterator instanceof \Iterator)) {
            throw new NotAnIteratorException("Expected an iterator but got a ".gettype($values));
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

    public static function merge(\Generator $generator, $values) {
        foreach ($generator as $value) {
            yield $value;
        }
        foreach ($values as $value) {
            yield $value;
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

    public static function setOperation(\Generator $generator, $operation, $keyHandling = self::DIFF_ONLY_VALUE, ...$values) {
        $shouldYield = SelectorHelpers::setHelper($keyHandling, $operation);
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

    public static function diff(\Generator $generator, $keyHandling = self::DIFF_ONLY_VALUE, ...$values) {
        return self::asGenerator(self::setOperation($generator, "diff", $keyHandling, ...$values));
    }

    public static function intersect(\Generator $generator, $keyHandling = self::DIFF_ONLY_VALUE, ...$values) {
        return self::asGenerator(self::setOperation($generator, "intersect", $keyHandling, ...$values));
    }

    public static function filter(\Generator $generator, $function = null) {
        $callback = SelectorHelpers::selector($function);
        foreach ($generator as $key => $value) {
            if ($callback($value,$key)) {
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


    public static function map(\Generator $generator, $callback = null, $withKeys = false) {
        $callback = SelectorHelpers::selector($callback);
        $index = 0;
        foreach ($generator as $key => $item) {
            yield  ($withKeys ? $key : $index) => $callback($item, $key, $index);
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
        yield from $values;
        yield from $generator;
    }

    public static function append(\Generator $generator, $values) {
        yield from $generator;
        yield from $values;
    }

    public static function splice(\Generator $generator, $offset, $length, $replacement = null) {
        for ($atIndex = 0;$atIndex < $offset;$atIndex++) {
            yield $generator->next();
        }
        yield from $replacement ?? [];

        for ($skipped = 0; $skipped < $length;$skipped++) {
            $generator->next();
        }
        yield from $generator;
    }

    public static function slice(\Generator $generator, $offset, $length) {
        for ($atIndex = 0;$atIndex < $offset;$atIndex++) {
            $generator->next();
        }
        yield from self::take($generator, $length);
    }

    public static function nth(\Generator $generator, $n) {
        $i = 0;
        foreach ($generator as $key => $value) {
            if ($i > 0 && $i%$n == 0) {
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
        for ($i = 0; $i < $n;$i++) {
            yield $callback($i);
        }
    }

    public static function union(\Generator $generator, $array) {
        foreach ($generator as $key => $value) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                yield $key => $value;
            }
        }
        yield from $array;
    }

    public static function zip(\Generator $generator, $array, $ignoreMismatches = false) {
        foreach ($generator as $key => $item) {
            if (!array_key_exists($key,$array) && !$ignoreMismatches) {
                throw new MismatchException("Given array does not match all keys in the generator");
            } else {
                yield $item => $array[$key];
            }
        }
    }

}