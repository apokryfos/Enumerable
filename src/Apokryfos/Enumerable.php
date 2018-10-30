<?php

namespace Apokryfos;
use Apokryfos\Helpers\EventEmitter;
use Apokryfos\Helpers\GeneratorHelpers;
use Apokryfos\Helpers\HigherOrderProxy;
use Apokryfos\Helpers\SelectorHelpers;
use Tests\Fixtures\Generator;


/**
 * @property-read HigherOrderProxy $average
 * @property-read HigherOrderProxy $avg
 * @property-read HigherOrderProxy $each
 * @property-read HigherOrderProxy $every
 * @property-read HigherOrderProxy $filter
 * @property-read HigherOrderProxy $first
 * @property-read HigherOrderProxy $flatMap
 * @property-read HigherOrderProxy $keyBy
 * @property-read HigherOrderProxy $map
 * @property-read HigherOrderProxy $max
 * @property-read HigherOrderProxy $mix
 * @property-read HigherOrderProxy $reject
 * @property-read HigherOrderProxy $sum
 *
 */
class Enumerable implements \Iterator, \JsonSerializable {


    protected $generator;
    protected $inner;

    protected static $higherOrderMethods = [
        'average', 'avg', 'each', 'every', 'filter',
        'first', 'flatMap', 'keyBy', 'map', 'max', 'min',
        'median', 'reject', 'sum'
    ];


    /**
     * Enumerable constructor.
     * @param array|\Traversable|null $initial
     */
    public function __construct($initial = null) {
        if ($initial !== null && ($initial instanceof \Traversable || is_array($initial))) {
            $inner = $initial;
        } else {
            $inner = [];
        }
        $this->generator = GeneratorHelpers::asGenerator($inner);
    }


    public function stream() {
        yield from $this->generator;
    }

    public function all() {
        $array = iterator_to_array($this->generator);
        return $array;
    }

    public function toArray() {
        $array = GeneratorHelpers::generatorToArray($this->generator);
        return $array;
    }

    public function toJson() {
        return json_encode($this);
    }

    /**
     * Skip the first $number elements and yields the rest. Fluent.
     * @param $number Number of elements to skip
     * @return Enumerable
     */
    public function skip($number) {
        $this->generator = GeneratorHelpers::skip($this->generator, $number);
        return $this;
    }

    /**
     * Takes the first $number elements and ignores the rest. Fluent.
     * @param $number Number of elements to take
     * @return Enumerable
     */
    public function take($number) {
        $this->generator = GeneratorHelpers::take($this->generator, $number);
        return $this;
    }

    /**
     * Chunks the enumerable into a number of roughly equally sized enumerables. Fluent.
     * @param $number Number of elements to skip
     * @return Enumerable|Enumerable[]
     */
    public function chunk($number) {
        $generator = function ($generator) use ($number) {
            foreach (GeneratorHelpers::chunk($generator, $number) as $chunk) {
                yield new static($chunk);
            }
        };
        $this->generator = $generator($this->generator);
        return $this;
    }

    /**
     * Skip the first $number elements and yields the rest. Fluent.
     * @return Enumerable
     */
    public function collapse() {
        return $this->flatten();
    }


    /**
     * @param $values
     * @param bool $strict
     * @return $this
     * @throws Exceptions\CombineSizeMismatchException
     * @throws Exceptions\NotAnIteratorException
     */
    public function combine($values, $strict = false) {
        $this->generator = GeneratorHelpers::combine($this->generator, $values, $strict);
        return $this;
    }


    /**
     * Alias of @see self::merge
     * @param array|\Iterator $values Values to concatenate
     * @return Enumerable
     */
    public function concat($values) {
        return $this->merge($values);
    }

    public function merge($values, $assoc = false) {
        $this->generator = GeneratorHelpers::merge($this->generator, $values, $assoc);
        return $this;
    }

    public function put($key, $value) {
        return $this->merge([$key => $value], true);
    }

    public function push($value) {
        return $this->merge([$value]);
    }


    public function crossJoin(...$values) {
        $this->generator = GeneratorHelpers::crossJoin($this->generator, ...$values);
        return $this;
    }

    public function diff(...$values) {
        $this->generator = GeneratorHelpers::diff($this->generator, GeneratorHelpers::ONLY_VALUE,...$values);
        return $this;
    }

    public function diffAssoc(...$values) {
        $this->generator = GeneratorHelpers::diff($this->generator, GeneratorHelpers::BOTH,...$values);
        return $this;
    }

    public function diffKeys(...$values) {
        $this->generator = GeneratorHelpers::diff($this->generator, GeneratorHelpers::ONLY_KEY,...$values);
        return $this;
    }

    public function each(callable $callback) {
        foreach ($this->generator as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function eachSpread(callable $callback) {
        foreach ($this->generator as $value) {
            if (call_user_func_array($callback, self::wrap($value)->values()->all()) === false) {
                break;
            }
        }
    }

    public function except($keys) {
        return $this->diffKeys(array_flip($keys));
    }

    public function only($keys) {
        return $this->intersectByKeys(array_flip($keys));
    }

    public function filter($callback = null) {
        $this->generator = GeneratorHelpers::filter($this->generator, $callback);
        return $this;
    }

    public function where($keySelector, $operator = null, $valueSelector = null) {
        $args = SelectorHelpers::normalizeWhereArguments(...func_get_args());
        return $this->filter(SelectorHelpers::whereSelector(...$args));
    }
    public function whereStrict($keySelector, $operator = null, $valueSelector = null) {
        $args = SelectorHelpers::normalizeWhereArguments(...func_get_args());
        return $this->filter(SelectorHelpers::whereSelector(...array_merge($args, [ true ])));
    }

    public function whereIn($keySelector, $array, $strict = false, $negate = false) {
        $selector = SelectorHelpers::selector($keySelector);
        return $this->filter(function ($value, $key) use ($selector, $array, $strict, $negate) {
            $in = in_array($selector($value, $key), $array, $strict);
            return $negate ? !$in : $in;
        });
    }

    public function whereNotIn($keySelector, $array, $strict = false) {
        return $this->whereIn($keySelector, $array, $strict, true);
    }

    public function whereNotInStrict($keySelector, $array) {
        return $this->whereNotIn($keySelector, $array, true);
    }

    public function whereInStrict($keySelector, $array) {
        return $this->whereIn($keySelector, $array, true);
    }

    public function whereInstanceOf($class) {
        return $this->filter(SelectorHelpers::instanceOfSelector($class));
    }

    public function every($callback = null) {
        $callback = SelectorHelpers::selector($callback);
        foreach ($this->stream() as $key => $item) {
            if (!$callback($item, $key)) {
                return false;
            }
        }
        return true;
    }

    public function peek() {
        if (!$this->generator->valid() || $this->generator->key() === null) {
            return null;
        }
        $value = $this->generator->current();
        $key = $this->generator->key();
        return [ $value, $key ];
    }


    public function nthElement(int $n) {
        $nth = null;
        $seen = 0;
        foreach ($this->generator as $key => $value) {
            $nth = [$value, $key];
            $seen++;
            if ($seen === $n) {
                break;
            }
        }
        return $nth;
    }

    public function first($callback = null, $operator = null, $valueSelector = null) {
        list($element) = $this->where($callback, $operator, $valueSelector)->nthElement(1);
        return $element;
    }

    public function firstWhere($callback = null, $operator = null, $valueSelector = null) {
        return $this->first($callback, $operator, $valueSelector);
    }

    public function map($callback = null) {
        $this->generator = GeneratorHelpers::map($this->generator, $callback);
        return $this;
    }

    public function flip() {
        $this->generator = GeneratorHelpers::flip($this->generator);
        return $this;
    }

    public function flatten($levels = null) {
        $this->generator = GeneratorHelpers::flatten($this->generator, false, $levels);
        return $this;
    }

    public function flatMap($callback) {
        return $this->map($callback)
            ->flatten(1);
    }

    public function forget($key) {
        return $this->except([$key]);
    }

    public function get($key) {
        return $this->first(function ($v, $k) use ($key) {
            return $key === $k;
        }, null, null);
    }

    public function has($key, $allowNullValue = true) {
        foreach ($this->stream() as $k => $value) {
            if ($key === $k && ($allowNullValue || $value !== null)) {
                return true;
            }
        }
        return false;
    }

    public function implode(string $character) {
        return $this->reduce(function ($current, $value) use ($character) {
            return $current === null ? strval($value) : $current.$character.strval($value);
        }, null);
    }

    public function intersect(...$values) {
        $this->generator = GeneratorHelpers::intersect($this->generator, GeneratorHelpers::ONLY_VALUE, ...$values);
        return $this;
    }

    public function intersectByKeys(...$values) {
        $this->generator = GeneratorHelpers::intersect($this->generator, GeneratorHelpers::ONLY_KEY, ...$values);
        return $this;
    }

    public function isEmpty() {
        return $this->peek() === null;
    }

    public function isNotEmpty() {
        return !$this->isEmpty();
    }

    public function keyBy($callback = null) {
        $this->generator = GeneratorHelpers::keyBy($this->generator, SelectorHelpers::selector($callback));
        return $this;
    }

    public function keys() {
        $this->generator = GeneratorHelpers::keys($this->generator);
        return $this;
    }


    public static function make() {
        return new self();
    }


    public function last($callback = null, $operator = null, $valueSelector = null) {
       return $this->where($callback, $operator, $valueSelector)->nthElement(PHP_INT_MAX);
    }

    public function mapInto($class, $transformToCtorArgs = null) {
        $callback = SelectorHelpers::selector($transformToCtorArgs);
        return $this->map($callback)->map(function ($value) use ($class) {
            return new $class(...$value);
        });
    }


    public function mapSpread(callable $callback) {
        return $this->map(function ($value) use ($callback) {
            return $callback(...$value);
        });
    }

    public function mapWithKeys($callback = null) {
        $this->generator = GeneratorHelpers::mapWithKeys($this->generator, $callback);
        return $this;
    }

    public function reduce($callback, $initial) {
        $next = $initial;
        $count = 0;
        foreach ($this->stream() as $key => $value) {
            $next = $callback($next, $value, $key);
            $count ++;
        }
        return $next;
    }

    /**
     * Note: This could close the generator.
     *
     * @param callable|null $mappingFunction Transform each element before summing.
     *        The mapping function should accept a $value and $key and return a numeric value
     * @return float The sum
     */
    public function sum(callable $mappingFunction = null) {
        $mapWith = SelectorHelpers::selector($mappingFunction);
        return $this->map($mapWith)->reduce(function ($sum, $value) {
            return $sum+$value;
        }, 0);
    }


    /**
     * Note: This could close the generator.
     * @param callable|null $callable
     * @return float The average
     */
    public function average($callable = null) : float {
        $selector = SelectorHelpers::selector($callable);
        $sum = $this->map($selector)->reduce(function ($current, $value) {
            $current[0]++;
            $current[1] += $value;
            return $current;
        }, [ 0, 0 ]);
        return $sum[1]/$sum[0];
    }

    /**
     * @inheritdoc self::average
     */
    public function avg(callable $callable = null) : float {
        return $this->average(...func_get_args());
    }

    public function max($callback = null) {
        $mapWith = SelectorHelpers::selector($callback);
        return $this->map($mapWith)->reduce(function ($currentMax, $current) {
            return $currentMax === null || $current > $currentMax ? $current : $currentMax;
        }, null);
    }


    public function min($callback = null) {
        $mapWith = SelectorHelpers::selector($callback);
        return $this->map($mapWith)->reduce(function ($currentMin, $current) {
            return $currentMin === null || $current < $currentMin ? $current : $currentMin;
        }, null);
    }

    /**
     * Find the median in O(nlogn) time using a min-heap
     *
     * @param mixed $callback
     * @return float|int
     */
    public function median($callback = null) {
        $mapWith = SelectorHelpers::selector($callback);
        $minHeap = new \SplMinHeap();
        $current = null;
        // Populate a min heap
        foreach ($this->map($mapWith)->stream() as $key => $value) {
            $minHeap->insert($value);
        }
        //Get half the elements out
        $count = $minHeap->count();
        $target = $count/2;
        for ($i = 0;$i < $target;$i++) {
            $current = $minHeap->extract();
        }
        if ($count%2 == 0) {
            return ($current + $minHeap->extract())/2;
        }
        return $current;
    }


    public function countValues() {
        $counts = [];
        $total = 0;
        foreach ($this->stream() as $key => $item) {
            $counts[$item] = ($counts[$item] ?? 0) + 1;
            $total++;
        }
        return self::wrap($counts);

    }


    public function mode() {
        $countValues = $this->countValues()->all();
        arsort($countValues);
        return key($countValues);
    }

    public function nth($n) {
        $this->generator = GeneratorHelpers::nth($this->generator, $n);
        return $this;
    }


    public function pad($n, $padValue = null) {
        $this->generator = GeneratorHelpers::pad($this->generator, $n, $padValue);
        return $this;
    }


    public function pluck($selector = null) {
        return $this->map(SelectorHelpers::selector($selector));
    }

    public function prepend(...$item) {
        $this->generator = GeneratorHelpers::prepend($this->generator, $item);
        return $this;
    }

    public function reject($rejector) {
        $callback = SelectorHelpers::selector($rejector);
        return $this->filter(function ($value, $key) use ($callback) {
            return !$callback($value, $key);
        });
    }

    public function shift() {
        $first = $this->current();
        $key = $this->key();
        $this->next();
        if ($key === 0) {
            $this->generator = GeneratorHelpers::asNonAssociativeArray($this->generator);
        }
        return $first;
    }

    /**
     * Extract a slice of the array
     * It works like the built-in array slice when used with positive parameters
     * Negative values not supproted.
     *
     * @param $from Start from index (must be positive)
     * @param $size Size of the slice (must be positive)
     * @return $this
     *
     */
    public function slice($from, $size) {
        $isAssociative = $this->key() !== 0;
        $this->generator = GeneratorHelpers::slice($this->generator, $from, $size);
        if (!$isAssociative) {
            $this->generator = GeneratorHelpers::asNonAssociativeArray($this->generator);
        }
        return $this;
    }

    public function splice($from, $size, $replacement = null) {
        $this->generator = GeneratorHelpers::splice($this->generator, $from, $size, $replacement);
        return $this;
    }

    public function tap($callback) {
        $callback($this);
        return $this;
    }


    public static function times($n, $callback) {
        return new self(GeneratorHelpers::timesGenerator($n, $callback));
    }

    public function union($array) {
        $this->generator = GeneratorHelpers::union($this->generator, $array);
        return $this;
    }

    public function when($condition, $callback) {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    public function unless($condition, $callback) {
        return $this->when(!$condition, $callback);
    }

    public static function wrap($something) {
        return new self($something);
    }

    public static function unwrap($something) {
        return self::wrap($something)->all();
    }

    public function values() {
        $this->generator = GeneratorHelpers::values($this->generator);
        return $this;
    }

    /**
     * @param $other
     * @param bool $ignoreMismatches
     * @return $this
     * @throws Exceptions\MismatchException
     */
    public function zip($other, $ignoreMismatches = false) {
        $this->generator = GeneratorHelpers::zip($this->generator, $other, $ignoreMismatches);
        return $this;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current() {
        return $this->generator->current();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next() {
        $this->generator->next();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key() {
        return $this->generator->key();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid() {
        return $this->generator->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind() {
        $this->generator->rewind();
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize() {
        return $this->all();
    }

    public function __get($name) {
        if (in_array($name, self::$higherOrderMethods)) {
            return new HigherOrderProxy($this, $name);
        }
        throw new \BadMethodCallException("Tried to access property $name");
    }
}
