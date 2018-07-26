<?php


namespace Tests\Apokryfos;

use Apokryfos\Enumerable;
use Apokryfos\Exceptions\CombineSizeMismatchException;
use Apokryfos\Helpers\GeneratorHelpers;
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
        $this->assertEquals($expected, iterator_to_array($e->skip(10)));
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
     * @dataProvider \Tests\Fixtures\Datasets::crossJoinDataset
     */
    public function testCrossJoin($head, $values, $expected) {
        $e = new Enumerable($head);
        $this->assertEquals($expected, $e->crossJoin(...$values)->toArray());

    }

    /**
     * @dataProvider \Tests\Fixtures\Datasets::diffDataset
     */
    public function testDiff($a, $b, $c, $expected) {
        $e = new Enumerable($a);
        $this->assertEquals($expected, $e->diff($b,$c)->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::diffAssocDataset
     */
    public function testDiffAssoc($a, $b, $c, $expected) {
        $e = new Enumerable($a);
        $this->assertEquals($expected, $e->diffAssoc($b,$c)->all());
    }


    /**
     * @dataProvider \Tests\Fixtures\Datasets::diffKeysDataset
     */
    public function testDiffKeys($a, $b, $c, $expected) {
        $e = new Enumerable($a);
        $this->assertEquals($expected, $e->diffKeys($b,$c)->all());
    }




}