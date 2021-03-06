<?php


namespace Tests\Apokryfos;

use Apokryfos\Enumerable;
use Apokryfos\Exceptions\CombineSizeMismatchException;
use Apokryfos\Helpers\SelectorHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Generator;
use Tests\Helpers\TestHelper;
use Tests\Helpers\TestObject;

class BasicEnumerableTest extends TestCase {

    public function testConstructor() {
        $initial = [ 1,2, 3];
        $e = new Enumerable($initial);
        $this->assertEquals($initial, iterator_to_array($e));
    }

    public function testConstructorNonEnumerable() {;
        $e = new Enumerable("NonEnumerable");
        $this->assertEquals([], iterator_to_array($e));
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::toArrayDataset
     * @param $input
     * @param $expected
     */
    public function testToArray($input, $expected) {
        $e = new Enumerable($input);
        $this->assertEquals($expected, $e->toArray());
    }

    public function testAllReturnsCurrent() {
        $initial = [ 1,2, 3];
        $e = new Enumerable($initial);

        $this->assertEquals($initial, $e->all());

    }

    public function testTake() {
        $initial = Generator::randomNumbersArray(1000);
        $e = new Enumerable($initial);
        $expected = array_slice($initial, 0, 10);

        $this->assertEquals($expected, iterator_to_array($e->take(10)));
    }


    public function testSkip() {
        $initial = Generator::randomNumbersArray(1000);
        $e = new Enumerable($initial);
        $expected = array_slice($initial, 10, null, true);
        $this->assertEquals($expected, $e->skip(10)->all());
    }


    public function testChunk() {
        $initial = Generator::randomNumbersArray(rand(10, 200));
        $chunkSize = rand(1, count($initial)-1);

        $e = new Enumerable($initial);
        $expected = array_chunk($initial, $chunkSize, true);
        $this->assertEquals(
            $expected,
            array_map('iterator_to_array', iterator_to_array($e->chunk($chunkSize)))
        );
    }

    public function testChunkEdge() {
        $e = new Enumerable("NotAnEnumerable");
        //$this->expectException(\ArithmeticError::class);
        $this->assertEquals([], $e->chunk(10)->toArray());
    }

    public function testChunkError() {
        $e = new Enumerable(Generator::randomArray());
        $this->expectException(\ArithmeticError::class);
        $e->chunk(0)->toArray();
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::collapseDataset
     * @param $input
     * @param $expected
     */
    public function testCollapse($input, $expected) {
        $e = new Enumerable($input);

        $this->assertEquals($expected, $e->collapse()->toArray());
    }


    public function testCombineBlind() {
        $keys = Generator::randomArray(10);
        $e = new Enumerable($keys);
        $values = Generator::randomArray(10);


        $this->assertEquals(array_combine($keys, $values), $e->combine($values)->toArray());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::combineAssymetricDataset()
     * @param $keys
     * @param $values
     * @throws CombineSizeMismatchException
     * @throws \Apokryfos\Exceptions\NotAnIteratorException
     */
    public function testCombineAssymetric($keys, $values) {
        $e = new Enumerable($keys);
        $expected = array_combine(
            $keys,
            count($values) > count($keys)
            ? array_slice($values, 0, count($keys))
            : array_pad($values, count($keys), null));

        $this->assertEquals($expected, $e->combine($values)->toArray());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::combineAssymetricDataset
     * @param $keys
     * @param $values
     * @throws CombineSizeMismatchException
     * @throws \Apokryfos\Exceptions\NotAnIteratorException
     */
    public function testCombineAssymetricStrict($keys, $values) {
        $this->expectException(CombineSizeMismatchException::class);
        $e = new Enumerable($keys);
        $e->combine($values,true)->toArray();
    }
    
    
    public function testMerge() {
        $a = Generator::randomArray(5);
        $b = Generator::randomArray(10);
        $e = new Enumerable($a);
        $this->assertEquals(array_merge($a,$b), $e->merge($b)->toArray());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDataset()
     * @param array $dataset
     */

    public function testMergeAssoc($dataset) {
        $array2 = Generator::randomKeyedArray()[0];

        $e = new Enumerable($dataset);
        $this->assertEquals(array_merge($dataset,$array2), $e->merge($array2)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomPair
     * @param $a
     * @param $b
     */
    public function testConcat($a, $b) {
        $e = new Enumerable($a);
        $this->assertEquals(array_merge($a,$b), $e->concat($b)->toArray());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::crossJoinDataset
     * @param $head
     * @param $values
     * @param $expected
     */
    public function testCrossJoin($head, $values, $expected) {
        $e = new Enumerable($head);
        $this->assertEquals($expected, $e->crossJoin(...$values)->toArray());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::diffDataset
     * @param $a
     * @param $b
     * @param $c
     * @param $expected
     */
    public function testDiff($a, $b, $c, $expected) {
        $e = new Enumerable($a);
        $this->assertEquals($expected, $e->diff($b,$c)->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomSmallNumbersDataset()
     */
    public function testDiffRandom(...$smallNumbers) {
        $e = new Enumerable($smallNumbers);
        $other = Generator::randomSmallNumbersArray();
        $other2 = Generator::randomSmallNumbersArray();
        $expected = array_diff($smallNumbers, $other, $other2);

        $this->assertEquals($expected, $e->diff($other, $other2)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::diffAssocDataset
     * @param $a
     * @param $b
     * @param $c
     * @param $expected
     */
    public function testDiffAssoc($a, $b, $c, $expected) {
        $e = new Enumerable($a);
        $this->assertEquals($expected, $e->diffAssoc($b,$c)->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::diffKeysDataset
     * @param $a
     * @param $b
     * @param $c
     * @param $expected
     */
    public function testDiffKeys($a, $b, $c, $expected) {
        $e = new Enumerable($a);
        $this->assertEquals($expected, $e->diffKeys($b,$c)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testEach(...$dataset) {
        $sum = 0;
        $expected = array_sum($dataset);
        $e = new Enumerable($dataset);

        $e->each(function ($value) use (&$sum) {
            $sum += $value;
        });
        $this->assertEquals($expected, $sum);
    }

    public function testToJson() {
        $value = [
            "identifier" => rand(),
            "label" => Generator::randomValue()
        ];
        $expected = json_encode($value);
        $this->assertEquals($expected, Enumerable::wrap($value)->toJson());

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $dataset
     */
    public function testPush(...$dataset) {
        $e = new Enumerable($dataset);
        $v = Generator::randomValue();
        $expected = array_merge($dataset, [ $v ]);
        $this->assertEquals($expected, $e->push($v)->all());

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $dataset
     */
    public function testPut(...$dataset) {
        $e = new Enumerable($dataset);
        $key = Generator::randomValue();
        $v = Generator::randomValue();
        $expected = $dataset;
        $expected[$key] = $v;
        $this->assertEquals($expected, $e->put($key, $v)->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $dataset
     */
    public function testEachSpread(...$dataset) {
        $expected = array_map(function ($value) {
            return implode("-", $value);
        }, $dataset);
        $e = new Enumerable($dataset);

        $result = [];
        $e->eachSpread(function ($identifier, $label) use (&$result) {
           $result[] = $identifier.'-'.$label;
        });
        $this->assertEquals($expected, $result);
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDatasetWithNulls
     * @param array $numbers
     */
    public function testDefaultFilter(...$numbers) {
        $expected = array_filter($numbers);
        $this->assertEquals($expected, Enumerable::wrap($numbers)->filter()->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testWhere(...$numbers) {
        $mean = array_sum(array_column($numbers, 'identifier')) / count($numbers);
        $expected = array_filter($numbers, function ($value) use ($mean) {
            return $value["identifier"] < $mean;
        });

        $this->assertEquals($expected, Enumerable::wrap($numbers)->where("identifier", "<", $mean)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testWhereIn(...$numbers) {
        $subset = array_column($numbers, "identifier");
        shuffle($subset);
        $subset = array_slice($subset, 0, count($subset)/4);
        $expected = array_filter($numbers, function ($value) use ($subset) {
            return in_array($value["identifier"], $subset);
        });
        $this->assertEquals($expected, Enumerable::wrap($numbers)->whereIn("identifier", $subset)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testWhereInStrict(...$numbers) {
        $subset = array_column($numbers, "identifier");
        shuffle($subset);
        $subset = array_slice($subset, 0, count($subset)/4);
        $noisySubset = array_map(function ($v) {
            if (rand(1,100) < 10) {
                return strval($v);
            }
            return $v;
        }, $subset);
        $expected = array_filter($numbers, function ($value) use ($noisySubset) {
            return in_array($value["identifier"], $noisySubset, true);
        });
        $this->assertEquals($expected, Enumerable::wrap($numbers)->whereInStrict("identifier", $noisySubset)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testWhereNotInStrict(...$numbers) {
        $subset = array_column($numbers, "identifier");
        shuffle($subset);
        $subset = array_slice($subset, 0, count($subset)/4);
        $noisySubset = array_map(function ($v) {
            if (rand(1,100) < 10) {
                return strval($v);
            }
            return $v;
        }, $subset);
        $expected = array_filter($numbers, function ($value) use ($noisySubset) {
            return !in_array($value["identifier"], $noisySubset, true);
        });
        $this->assertEquals($expected, Enumerable::wrap($numbers)->whereNotInStrict("identifier", $noisySubset)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testWhereNotIn(...$numbers) {
        $subset = array_column($numbers, "identifier");
        shuffle($subset);
        $subset = array_slice($subset, 0, count($subset)/4);
        $expected = array_filter($numbers, function ($value) use ($subset) {
            return !in_array($value["identifier"], $subset);
        });
        $this->assertEquals($expected, Enumerable::wrap($numbers)->whereNotIn("identifier", $subset)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::whereStrictDataset
     * @param array $numbers
     */
    public function testWhereImplicitEquals($numbers) {
        $this->assertEquals($numbers, Enumerable::wrap($numbers)->where("number", "1")->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::whereStrictDataset
     * @param array $numbers
     */
    public function testWhereStrict($numbers, $expected) {
        $this->assertEquals($expected, Enumerable::wrap($numbers)->whereStrict("number", 1)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testExcept(...$numbers) {
        $indices = range(0, count($numbers)-1);
        shuffle($indices);
        $except = array_slice($indices, 0, rand(1,20));
        $expected = array_diff_key($numbers, array_flip($except));
        $e = new Enumerable($numbers);
        $this->assertEquals($expected, $e->except($except)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testOnly(...$numbers) {
        $indices = range(0, count($numbers)-1);
        shuffle($indices);
        $only = array_slice($indices, 0, rand(1,20));
        $expected = array_intersect_key($numbers, array_flip($only));
        $e = new Enumerable($numbers);
        $this->assertEquals($expected, $e->only($only)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::mixedTestCase
     * @param array $numbers
     */
    public function testWhereInstanceOf(...$classes) {
        $expected = array_filter($classes, function ($c) {
            return $c instanceof TestHelper;
        });
        $e = new Enumerable($classes);
        $this->assertEquals($expected, $e->whereInstanceOf(TestHelper::class)->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::higherOrderTestCase
     * @param array $numbers
     */
    public function testEvery(...$classes) {
        $e = new Enumerable($classes);
        $this->assertEquals(true, $e->every(SelectorHelpers::instanceOfSelector(TestHelper::class)));
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testPeek($first, ...$arrays) {
        $e = new Enumerable(func_get_args());

        $this->assertEquals([ $first, 0 ], $e->peek());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testRepeek($first, ...$arrays) {
        $e = new Enumerable(func_get_args());

        $this->assertEquals([ $first, 0 ], $e->peek());
        $this->assertEquals([ $first, 0 ], $e->peek());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomSmallNumbersDataset
     * @param array $numbers
     */
    public function testCountValues(...$numbers) {
        $expected = array_count_values($numbers);
        $this->assertEquals($expected, Enumerable::wrap($numbers)->countValues()->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $numbers
     */
    public function testMode(...$numbers) {
        $count = array_count_values($numbers);
        arsort($count);
        $expected = key($count);
        $this->assertEquals($expected, Enumerable::wrap($numbers)->mode());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $numbers
     */
    public function testNth(...$numbers) {
        $n = rand(1, 5);
        $i = 0;
        $expected = array_filter($numbers, function () use (&$i, $n) {
            return ($i++)%$n === 0;
        });
        $this->assertEquals($expected, Enumerable::wrap($numbers)->nth($n)->all());

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset
     * @param array $numbers
     */
    public function testPad(...$numbers) {
        $paddingSize = 100;
        $paddingValue = 'padding';
        $expected = array_pad($numbers, count($numbers) + $paddingSize, $paddingValue);
        $this->assertEquals($expected, Enumerable::wrap($numbers)->pad(count($numbers) + $paddingSize, $paddingValue)->all());

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testPluck(...$numbers) {
        $expected = array_column($numbers, 'label');
        $this->assertEquals($expected, Enumerable::wrap($numbers)->pluck('label')->all());

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testPrepend(...$numbers) {
        $expected = array_merge($r = Generator::randomArray(10), $numbers);
        $this->assertEquals($expected, Enumerable::wrap($numbers)->prepend(...$r)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDatasetWithNulls
     * @param array $numbers
     */
    public function testReject(...$numbers) {
        $rejectFunc = function ($value) {
            return $value === null;
        };

        $expected = array_filter($numbers, function ($value) use ($rejectFunc) {
            return !$rejectFunc($value);
        });

        $this->assertEquals($expected, Enumerable::wrap($numbers)->reject($rejectFunc)->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDatasetWithNulls
     * @param array $numbers
     */
    public function testShift(...$numbers) {
        $clone = $numbers;

        $expected = array_shift($clone);
        $enumerable = Enumerable::wrap($numbers);
        $this->assertEquals($expected, $enumerable->shift());
        $this->assertEquals($clone, $enumerable->all());

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDatasetWithNulls
     * @param array $numbers
     */
    public function testNthElement(...$numbers) {
        $n = rand(1, count($numbers));

        $expected = $numbers[$n-1];
        $enumerable = Enumerable::wrap($numbers);
        list( $actual, $index ) = $enumerable->nthElement($n);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($n-1, $index);

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDatasetWithNullLabels
     * @param array $dataset
     */
    public function testFirstWhere(...$dataset) {
        $target = array_filter($dataset, function ($data) {
            return $data["label"] === null;
        });
        if (!empty($target)) {
            $target = current($target);
        } else {
            $target = null;
        }

        $this->assertEquals($target, Enumerable::wrap($dataset)->firstWhere("label","==",null));

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDataset()
     * @param $dataset
     */
    public function testFlip($dataset) {
        $expected = array_flip($dataset);
        $this->assertEquals($expected, Enumerable::wrap($dataset)->flip()->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDataset()
     */
    public function testFlatMap() {
        $dataset = array_map(function () {
            return Generator::randomNumbersArray(5);
        }, range(0, 4));

        $mapper = function ($value) {
            return sqrt($value);
        };

        $expected = [];
        foreach ($dataset as $values) {
            foreach ($values as $value) {
                $expected[] = $mapper($value);
            }
        }
        $this->assertEquals($expected, Enumerable::wrap($dataset)->flatMap(function ($values) use ($mapper) {
            return Enumerable::wrap($values)->map($mapper);
        })->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDataset()
     * @param $dataset
     */
    public function testForget($dataset) {
        $randomkey = array_keys($dataset)[rand(0, count($dataset)-1)];
        $expected = array_filter($dataset, function ($key) use ($randomkey) {
            return $key !== $randomkey;
        }, ARRAY_FILTER_USE_KEY);

        $this->assertEquals($expected, Enumerable::wrap($dataset)->forget($randomkey)->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDataset()
     * @param array $dataset
     */
    public function testGet($dataset) {
        $randomkey = array_keys($dataset)[rand(0, count($dataset)-1)];
        $expected = array_filter($dataset, function ($key) use ($randomkey) {
            return $key !== $randomkey;
        }, ARRAY_FILTER_USE_KEY);

        $this->assertEquals(current($expected), Enumerable::wrap($dataset)->get($randomkey));

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDatasetWithNulls()
     * @param array $dataset
     */
    public function testExistsAndIsNull($dataset) {
        $nullValues = array_filter($dataset, function ($value) {
            return $value === null;
        });
        $randomkey = array_keys($nullValues)[rand(0, count($nullValues)-1)];
        $this->assertTrue(Enumerable::wrap($dataset)->has($randomkey));
        $this->assertFalse(Enumerable::wrap($dataset)->has($randomkey, false));

    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDatasetWithNulls()
     * @param array $dataset
     */
    public function testImplode($dataset) {
        $string = implode('.', $dataset);
        $this->assertEquals($string, Enumerable::wrap($dataset)->implode('.'));
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDatasetWithNulls()
     * @param array $dataset
     */
    public function testIntersect($dataset) {
        $expected = array_filter($dataset, function ($v) {
            return $v === null;
        });
        $this->assertEquals($expected, Enumerable::wrap($dataset)->intersect([ null ])->all());
    }

    public function testIsEmpty() {
        $enumerable1 = Enumerable::make();
        $enumerable2 = Enumerable::wrap([ rand(1,10) ]);

        $this->assertTrue($enumerable1->isEmpty());
        $this->assertFalse($enumerable1->isNotEmpty());
        $this->assertFalse($enumerable2->isEmpty());
        $this->assertTrue($enumerable2->isNotEmpty());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset
     * @param array $numbers
     */
    public function testKeyBy(...$numbers) {
        $expected = [];
        foreach ($numbers as $number) {
            $expected[$number["identifier"]] = $number;
        }

        $this->assertEquals($expected, Enumerable::wrap($numbers)->keyBy("identifier")->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomAssociativeStringDatasetWithNulls()
     * @param array $dataset
     */
    public function testKeys($dataset) {
        $expected = array_keys($dataset);
        $this->assertEquals($expected, Enumerable::wrap($dataset)->keys()->all());
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDatasetWithNullLabels()
     * @param array $dataset
     */
    public function testLast(...$dataset) {
        $expected = array_filter($dataset, function ($data) {
            return $data["label"] === null;
        });
        $lastIndex =array_keys($expected)[count($expected)-1];
        $last = $expected[$lastIndex];
        $this->assertEquals([ $last, $lastIndex ], Enumerable::wrap($dataset)->last("label" ,"==", null));
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset()
     * @param array $dataset
     */
    public function testMapInto(...$dataset) {
        $result = Enumerable::wrap($dataset)->mapInto(TestObject::class, function ($data) {
            return array_values($data);
        });

        /**
         * @var int|string $index
         * @var TestObject $object
         */
        foreach ($result as $index => $object) {
            $this->assertInstanceOf(TestObject::class, $object);
            $this->assertEquals($dataset[$index], [
                "identifier" => $object->getIdentifier(),
                "label" => $object->getLabel()
            ]);
        }
    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testMapSpread(...$dataset) {
        $realDataset = array_map(function () {
            return Generator::randomNumbersArray();
        }, $dataset);

        $callback = function (...$values) {
            $weightedSum = 0;
            foreach ($values as $k=>$v) {
                $weightedSum += $k*$v;
            }
            return $weightedSum;
        };

        $expected = array_map(function ($array) use ($callback) {
            return $callback(...$array);
        }, $realDataset);

        $this->assertEquals($expected, Enumerable::wrap($realDataset)->mapSpread($callback)->all());


    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomComplexDataset()
     * @param array $dataset
     */
    public function testMapWithKeys(...$dataset) {
        $expected = array_combine(array_column($dataset,'identifier'), $dataset);

        $this->assertEquals($expected, Enumerable::wrap($dataset)->mapWithKeys(function ($value) {
            return [ $value["identifier"] => $value ];
        })->all());


    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testSlice(...$dataset) {
        $expected = array_slice($dataset, 3, 8);

        $this->assertEquals($expected, Enumerable::wrap($dataset)->slice(3,8)->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testSplice(...$dataset) {
        $expected = $dataset;
        array_splice($expected, 3, 3, [ 'AAAAA' ]);

        $this->assertEquals($expected, Enumerable::wrap($dataset)->splice(3,3, [ 'AAAAA' ])->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testTap(...$dataset) {
        $expected = Enumerable::wrap($dataset);

        $this->assertEquals($expected, $expected->tap(function () {
            // noop
        }));
    }

    public function testUnion() {
        $first = [
            'a' => 1, 'c' => 3
        ];
        $second = [
            'b' => 2, 'c' => 10
        ];

        $expected = [
            'a' => 1, 'b' => 2, 'c' => 3
        ];

        $this->assertEquals($expected, Enumerable::wrap($first)->union($second)->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testTimes(...$dataset) {
        $r = rand();
        $callback = function ($i) use ($r) {
            return $i + $r;
        };
        $expected = array_map($callback, range(0, count($dataset)-1));
        $this->assertEquals($expected, Enumerable::times(count($dataset), $callback)->all());

    }

    public function testWhenUnless() {

        $c = function (Enumerable $e) {
            $e->push(1);
        };

        $e = Enumerable::make();
        $this->assertEquals([1], $e->when(true, $c)->all());
        $e = Enumerable::make();
        $this->assertEquals([], $e->unless(true, $c)->all());
        $e = Enumerable::make();
        $this->assertEquals([], $e->when(false, $c)->all());
        $e = Enumerable::make();
        $this->assertEquals([1], $e->unless(false, $c)->all());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testUnwrap(...$expected) {
        $this->assertEquals($expected, Enumerable::unwrap($expected));
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::randomNumbersDataset()
     * @param array $dataset
     */
    public function testZip(...$dataset) {
        $other = Generator::randomNumbersArray(count($dataset));
        shuffle($other);
        $expected = [];
        foreach ($dataset as $key => $value) {
            $expected[$key] = [ $value, $other[$key] ];
        }

        $this->assertEquals($expected, Enumerable::wrap($dataset)->zip($other)->all());
    }


    public function testInvalidHigherOrder() {
        $array = Generator::randomArray();
        $e = Enumerable::wrap($array);

        $this->expectException(\BadMethodCallException::class);

        $e->zip->zoom;
    }



}