<?php


namespace Tests\Apokryfos;

use Apokryfos\Enumerable;
use Apokryfos\Exceptions\CombineSizeMismatchException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Generator;

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
        $this->assertEquals(array_merge($a,$b), $e->merge($b)->toArray());
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
        $expected = array_merge($dataset, [ $key => $v ]);
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


}