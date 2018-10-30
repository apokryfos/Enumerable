<?php

namespace Tests\Fixtures;


use Tests\Helpers\TestHelper;

class Datasets {

    public static $testCaseCount = 25;
    public static $datasetSize = 100;


    public function toArrayDataset() {
        return [
            [[1, new \ArrayObject([1])], [1, [1]]],
            [new \ArrayObject([1, new \ArrayObject([1])]), [1, [1]]],
            [["a" => new \ArrayObject([1])], ["a" => [1]]]
        ];

    }

    public function collapseDataset() {
        return [
            [[[1]], [1]],
            [[1, 1, 1, 1], [1, 1, 1, 1]],
            [[1, [1]], [1, 1]],
            [[1, [1, 1], 1], [1, 1, 1, 1]],
            [[[1], 1, [[1, [1]]]], [1, 1, 1, 1]]
        ];
    }

    public function combineDataset() {
        return [
            [["a", "b", "c"], [1, 2, 3], ["a" => 1, "b" => 2, "c" => 3]]
        ];
    }


    public function combineAssymetricDataset() {
        return [
            [Generator::randomArray(), Generator::randomArray(15)],
            [Generator::randomArray(15), Generator::randomArray()]
        ];
    }


    public function sumDataset() {
        $sqr = function ($value) {
            return pow($value, 2);
        };
        $keySum = function ($value, $key) {
            return $key;
        };
        return [
            [[1, 1, 1], 3],
            [[1, 2, 3], 6],
            [[3, 2, 1], 6],
            [[10], 10],
            [[], 0],
            [[1, 1, 1], 3, $sqr],
            [[1, 2, 3], 14, $sqr],
            [[3, 2, 1], 14, $sqr],
            [[10], 100, $sqr],
            [[], 0, $sqr],
            [[10], 0, $keySum],
            [[10, 20], 1, $keySum],

        ];
    }

    public function avgDataset() {
        $sqr = function ($value) {
            return pow($value, 2);
        };
        $keySum = function ($value, $key) {
            return $key;
        };
        return [
            [[1, 1, 1], 1],
            [[1, 2, 3], 2],
            [[3, 2, 1], 2],
            [[10], 10],
            [[1, 1, 1], 1, $sqr],
            [[1, 2, 3], 14 / 3, $sqr],
            [[3, 2, 1], 14 / 3, $sqr],
            [[10], 100, $sqr],
            [[10], 0, $keySum],
            [[10, 20], 1 / 2, $keySum],
        ];
    }

    public function crossJoinDataset() {
        $head = [1, 2];
        return [
            [$head, [['a', 'b']], [[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']]],
            [[], [], [[]]],
            [$head, [], [$head]],
            [$head, [['a'], ['I']], [[1, 'a', 'I'], [2, 'a', 'I']]],
            [$head, [['a', 'b'], ['I']], [[1, 'a', 'I'], [1, 'b', 'I'], [2, 'a', 'I'], [2, 'b', 'I']]]
        ];
    }

    public function diffDataset() {
        return [
            [[1, 2, 3], [2, 3], [4], [1]],
            [[1, 2, 3], [1, 3], [2], []],
            [[1, 2, 3], [4, 'a'], ['I'], [1, 2, 3]]
        ];
    }

    public function diffAssocDataset() {
        return [
            [[1, 2, 3], [2, 3], [4], [1, 2, 3]],
            [[1, 2, 3], ['a' => 1, 2 => 3], [1, 2], []],
            [['a' => 1, 1 => 2, 2 => 3], [1, 2, 3], [], ['a' => 1]]
        ];
    }


    public function diffKeysDataset() {
        return [
            [[1, 2, 3], [2, 3], [4], [2 => 3]],
            [['a' => 1, 'b' => 2, 'c' => 3], ['a' => 1, 3], ['c' => 1, 2], ['b' => 2]]
        ];
    }

    public function mixedTestCase() {
        return $this->randomDatasetOf(function () {
            return array_map(
                function () {
                    if (rand(1, 10) < 5) {
                        return new TestHelper();
                    } else {
                        return [ "identifier" => rand(), "label" => Generator::randomValue() ];
                    }
                },
                range(1, self::$datasetSize)
            );
        });
    }

    public function higherOrderTestCase() {
        return $this->randomDatasetOf(function () {
            return array_map(
                function () {
                    return new TestHelper();
                },
                range(1, self::$datasetSize)
            );
        });
    }

    public function randomNumbersDataset() {
        return $this->randomDatasetOf([ Generator::class, 'randomNumbersArray' ], self::$datasetSize);
    }

    public function randomAssociativeStringDataset() {
        return $this->randomDatasetOf([ Generator::class, 'randomKeyedArray' ], self::$datasetSize);
    }

    public function randomAssociativeStringDatasetWithNulls() {
        return $this->randomDatasetOf([ Generator::class, 'randomKeyedArray' ], self::$datasetSize, 0.1);
    }

    public function randomSmallNumbersDataset() {
        return $this->randomDatasetOf([ Generator::class, 'randomSmallNumbersArray' ], self::$datasetSize);
    }

    public function randomNumbersDatasetWithNulls() {
        return $this->randomDatasetOf([ Generator::class, 'randomNumbersArray' ], self::$datasetSize, 0.1);
    }

    public function randomComplexDatasetWithNullLabels() {
        return $this->randomComplexDataset(0.1);
    }

    public function randomComplexDataset($nullProbability = 0) {
        return $this->randomDatasetOf(function () use ($nullProbability) {
            return array_map(
                function () use ($nullProbability) {
                    return [
                        "identifier" => rand(),
                        "label" => Generator::randomValue(null, 1, 10, $nullProbability)
                    ];
                },
                range(1, self::$datasetSize)
            );
        });
    }

    public function whereStrictDataset() {
        return $this->randomDatasetOf(function () {
            $set = array_map(function () {
                return [ "number" => rand(1,100) < 5 ? "1" : 1 ];
            }, range(0, self::$datasetSize-1));

            return [
                $set,
                array_filter($set, function ($v) {
                    return $v["number"] !== "1";
                })
            ];
        });
    }


    public function medianDataset() {
        return [
            [[3, 2, 1], 2],
            [[1, 2, 3], 2],
            [[1, 2, 2, 3], 2],
            [[1, 2, 3, 4], 2.5],
            [[1, 1, 1, 10000], 1]
        ];
    }


    public function randomPair() {
        return $this->randomDatasetOf(function () {
            return [ Generator::randomArray(self::$datasetSize), Generator::randomArray(rand(1, self::$datasetSize)) ];
        });
    }


    private function randomDatasetOf(callable $callback = null, ...$args) {
        $testcases = [];
        for ($i = 0; $i < self::$testCaseCount; $i++) {
            $testcases[$i] = $callback ? $callback(...$args) : null;
        }
        return $testcases;
    }


}