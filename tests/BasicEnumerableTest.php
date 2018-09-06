<?php


namespace Tests\Apokryfos;

use Apokryfos\Enumerable;
use Apokryfos\Exceptions\CombineSizeMismatchException;
use Apokryfos\Helpers\SelectorHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Generator;
use Tests\Helpers\TestHelper;

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
        $expected = $dataset + [ $key => $v ];
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

}