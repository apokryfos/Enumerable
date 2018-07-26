<?php

namespace Apokryfos;
use Apokryfos\Helpers\EventEmitter;
use Apokryfos\Helpers\GeneratorHelpers;
use Apokryfos\Helpers\SelectorHelpers;


/**
min
mode
nth
only
pad
partition
pipe
pluck
pop
prepend
pull
push
put
random
reduce
reject
reverse
search
shift
shuffle
slice
sort
sortBy
sortByDesc
sortKeys
sortKeysDesc
splice
split
sum
take
tap
times
toArray
toJson
transform
union
unique
uniqueStrict
unless
unwrap
values
when
where
whereStrict
whereIn
whereInStrict
whereInstanceOf
whereNotIn
whereNotInStrict
wrap
zip
 */

class Enumerable implements \Iterator, \Countable {

    use EventEmitter;

    protected $generator;
    protected $inner;
    protected $size = null;


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
        $this->generator = GeneratorHelpers::chain($inner)->value();
        if (is_array($initial) || $initial instanceof \Countable) {
            $this->size = count($initial);
        }
    }


    public function stream($keepOpen = false) {
        if ($keepOpen) {
            $this->all();
            yield from $this->inner;
        } else {
            $this->emit('closing');
            yield from $this->generator;
            $this->emit('closed');
        }
    }

    /**
     * Note: Caches result in memory but reopens the generator
     *
     * @return array
     */
    public function all() {
        $this->emit('caching');
        $this->inner = iterator_to_array($this->generator);
        $this->size = count($this->inner);
        $this->generator = GeneratorHelpers::chain($this->inner)->value();
        $this->emit('cached');
        return $this->inner;
    }

    /**
     * Note: Caches result in memory but reopens the generator
     *
     * @return array|\Generator
     */
    public function toArray() {
        $this->emit('caching');
        $this->inner = GeneratorHelpers::generatorToArray($this->generator);
        $this->size = count($this->inner);
        $this->generator = GeneratorHelpers::asGenerator($this->inner);
        $this->emit('cached');
        return $this->inner;
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
     * @param $number Number of elements to skip
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
     * @param $values Values to concatenate
     * @return Enumerable
     */
    public function concat($values) {
        return $this->merge($values);
    }

    public function merge($values) {
        $this->generator = GeneratorHelpers::merge($this->generator, $values);
        return $this;
    }

    public function put($key, $value) {
        return $this->merge([$key => $value]);
    }

    public function push($value) {
        return $this->merge([$value]);
    }



    public function crossJoin(...$values) {
        $this->generator = GeneratorHelpers::crossJoin($this->generator, ...$values);
        return $this;
    }

    public function diff(...$values) {
        $this->generator = GeneratorHelpers::diff($this->generator, GeneratorHelpers::DIFF_ONLY_VALUE,...$values);
        return $this;
    }

    public function diffAssoc(...$values) {
        $this->generator = GeneratorHelpers::diff($this->generator, GeneratorHelpers::DIFF_BOTH,...$values);
        return $this;
    }

    public function diffKeys(...$values) {
        $this->generator = GeneratorHelpers::diff($this->generator, GeneratorHelpers::DIFF_ONLY_KEY,...$values);
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
            if (call_user_func_array($callback, (new self($value))->all()) === false) {
                break;
            }
        }
    }

    public function except($keys) {
        return $this->diffKeys(array_flip($keys));
    }

    public function filter($callback = null) {
        $this->generator = GeneratorHelpers::filter($this->generator, $callback);
        return $this;
    }

    public function every($callback = null, $keepOpen = false) {
        $callback = SelectorHelpers::selector($callback);
        foreach ($this->stream($keepOpen) as $key => $item) {
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
        $value = $this->generator->next();
        $key = $this->generator->key();
        $this->generator = GeneratorHelpers::prepend($this->generator, [ $key=>$value]);
        return [ $value, $key ];
    }


    public function nthElement(int $n, $callback = null, $operator = null, $valueSelector = null, bool $keepOpen = false) {
        $elements = [];
        $nth = null;
        $callback = SelectorHelpers::whereSelector($callback, $operator, $valueSelector);
        $seen = 0;
        if (!$keepOpen) {
            $this->emit('closing');
        }
        foreach ($this->generator as $key => $value) {
            if ($keepOpen) {
                $elements[$key] = $value;
            }
            if ($callback($value, $key)) {
                $nth = [ $value, $key ];
                $seen++;
                if ($seen === $n) {
                    break;
                }
            }
        }

        if (!$keepOpen) {
            $this->emit('closed');
        } else {
            $this->generator = GeneratorHelpers::prepend($this->generator, $elements);
        }
        return $nth;
    }

    public function first($callback = null, $operator = null, $valueSelector = null, $keepOpen = false) {
        return $this->nthElement(1, $callback, $operator, $valueSelector, $keepOpen);
    }

    public function firstWhere($callback = null, $operator = null, $valueSelector = null, $keepOpen = false) {
        return $this->first($callback, $operator, $valueSelector, $keepOpen);
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
        list( $value ) = $this->first(function ($v, $k) use ($key) {
            return $key === $k;
        }, null, null, true);
        return $value;
    }

    public function has($key) {
        list( $value ) = $this->first(function ($v, $k) use ($key) {
            return $key === $k;
        }, null, null, true);
        return $value !== null;
    }

    public function implode(string $character, $keepOpen = false) {
        $str = null;
        foreach ($this->stream($keepOpen) as $value) {
            $str = $str !== null ? $character.strval($value) : strval($value);
        }
        return $str;
    }

    public function intersect(...$values) {
        $this->generator = GeneratorHelpers::intersect($this->generator, GeneratorHelpers::DIFF_ONLY_VALUE, ...$values);
        return $this;
    }

    public function intersectByKeys(...$values) {
        $this->generator = GeneratorHelpers::intersect($this->generator, GeneratorHelpers::DIFF_ONLY_KEY, ...$values);
        return $this;
    }

    public function isEmpty() {
        return $this->peek() !== null;
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


    public function make() {
        return new self();
    }


    public function last($callback = null, $operator = null, $valueSelector = null, $keepOpen = false) {
       return $this->nthElement(PHP_INT_MAX, $callback, $operator, $valueSelector, $keepOpen);
    }

    public function mapInto($class, $transformToCtorArgs = null) {
        $callback = SelectorHelpers::selector($transformToCtorArgs);
        return $this->map(function ($value, $key) use ($class, $callback) {
            return new $class($callback($value,$key));
        });
    }


    public function mapSpread(callable $callback) {
        return $this->map(function ($value, $key) use ($callback) {
            return $callback(...$value);
        });
    }

    public function mapWithKeys($callback = null) {
        $this->generator = GeneratorHelpers::map($this->generator, $callback, true);
        return $this;
    }

    public function reduce($callback, $initial, $keepOpen = false) {
        $next = $initial;
        $count = 0;
        foreach ($this->stream($keepOpen) as $key => $value) {
            $next = $callback($next, $value, $key);
            $count ++;
        }
        $this->size = $count;
        return $next;
    }

    /**
     * Note: This could close the generator.
     *
     * @param callable|null $mappingFunction Transform each element before summing.
     *        The mapping function should accept a $value and $key and return a numeric value
     * @param bool $keepOpen Whether the internal generator should be cached and reopened
     * @return float The sum
     */
    public function sum(callable $mappingFunction = null, $keepOpen = false) {
        $mapWith = SelectorHelpers::selector($mappingFunction);
        return $this->reduce(function ($sum, $value, $key) use ($mapWith) {
            return $sum+$mapWith($value, $key);
        }, 0, $keepOpen);
    }


    /**
     * Note: This could close the generator.
     * @param callable|null $callable
     * @return float The average
     */
    public function average(callable $callable = null, $keepOpen = false) : float {
        return  $this->sum($callable, $keepOpen) / $this->count();
    }

    /**
     * @inheritdoc self::average
     */
    public function avg(callable $callable = null) : float {
        return $this->average(...func_get_args());
    }

    public function max($callback = null) {
        $mapWith = SelectorHelpers::selector($callback);
        return $this->reduce(function ($currentMax, $value, $key) use ($mapWith) {
            $current = $mapWith($value, $key);
            return $currentMax === null || $current > $currentMax ? $current : $currentMax;
        }, null);
    }


    public function min($callback = null) {
        $mapWith = SelectorHelpers::selector($callback);
        return $this->reduce(function ($currentMin, $value, $key) use ($mapWith) {
            $current = $mapWith($value, $key);
            return $currentMin === null || $current < $currentMin ? $current : $currentMin;
        }, null);
    }

    /**
     * Find the median in O(nlogn) time using a min-heap
     *
     * @param mixed $callback
     * @param bool $keepOpen
     * @return float|int
     */
    public function median($callback = null, $keepOpen = false) {
        $mapWith = SelectorHelpers::selector($callback);
        $minHeap = new \SplMinHeap();
        $current = null;
        // Populate a min heap
        foreach ($this->stream($keepOpen) as $key => $value) {
            $minHeap->insert($mapWith($value,$key));
        }
        //Get half the elements out
        $count = $minHeap->count();
        $target = $count%2 == 0?$count/2-1:$count/2;
        for ($i = 0;$i < $target;$i++) {
            $current = $minHeap->extract();
        }
        if ($count%2 == 0) {
            return ($current + $minHeap->extract())/2;
        }
        return $current;
    }


    public function countValues($callback = null, $keepOpen = false) {
        $callback = SelectorHelpers::selector($callback);
        $counts = [];
        $total = 0;
        foreach ($this->stream($keepOpen) as $key => $item) {
            $index = $callback($item, $key);
            $counts[$index] = ($counts[$index] ?? 0) + 1;
            $total++;
        }
        return $counts;

    }


    public function mode($callback = null, $keepOpen = false) {
        $countValues = $this->countValues($callback, $keepOpen);
        arsort($countValues);
        return key($countValues);
    }

    public function nth($n, $keepOpen = false) {

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
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count() {
        if ($this->size !== null) {
            return $this->size;
        }
        return $this->reduce(function ($size) {
            return $size + 1;
        }, 0, true);
    }
}
