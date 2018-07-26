<?php

namespace Tests\Fixtures;


class Datasets {


    public function toArrayDataset() {
        return [
            [ [ 1, new \ArrayObject([1]) ], [ 1, [1] ] ],
            [ new \ArrayObject([1, new \ArrayObject([1])]), [ 1, [1] ] ],
            [ [ "a" => new \ArrayObject([1]) ], [ "a" => [ 1 ] ] ]
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
            [Generator::randomArray(),Generator::randomArray(15)],
            [Generator::randomArray(15),Generator::randomArray()]
        ];
    }


    public function sumAndCountDataset() {
        $sqr = function ($value) {
            return pow($value, 2);
        };
        $keySum = function ($value, $key) {
            return $key;
        };
        return [
            [[1, 1, 1], 3, 3],
            [[1, 2, 3], 6, 3],
            [[3, 2, 1], 6, 3],
            [[10], 10, 1],
            [[], 0, 0],
            [[1, 1, 1], 3, 3, $sqr],
            [[1, 2, 3], 14, 3, $sqr],
            [[3, 2, 1], 14, 3, $sqr],
            [[10], 100, 1, $sqr],
            [[], 0, 0, $sqr],
            [[10], 0, 1, $keySum],
            [[10, 20], 1, 2, $keySum],

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
        $head = [1,2];
        return [
            [ $head, [ [ 'a', 'b' ]  ], [ [ 1, 'a' ], [ 1, 'b' ], [ 2, 'a' ], [ 2, 'b' ] ] ],
            [ [ ], [  ], [ []  ] ],
            [ $head, [ ], [ $head ] ],
            [ $head, [ ['a'], ['I'] ], [ [ 1, 'a', 'I' ], [ 2, 'a', 'I' ] ] ],
            [ $head, [ ['a','b'], ['I'] ], [ [ 1, 'a', 'I' ], [ 1, 'b', 'I' ], [ 2, 'a', 'I' ], [ 2, 'b', 'I' ] ] ]
        ];
    }

    public function diffDataset() {
        return [
            [ [ 1,2,3 ], [ 2, 3 ], [ 4 ], [ 1 ] ],
            [ [ 1,2,3 ], [ 1, 3 ], [ 2 ], [ ] ],
            [ [ 1,2,3 ], [ 4, 'a' ], [ 'I' ], [ 1,2,3 ] ]
        ];
    }

    public function diffAssocDataset() {
        return [
            [ [ 1,2,3 ], [ 2, 3 ], [ 4 ], [ 1,2,3 ] ],
            [ [ 1,2,3 ], [ 'a' => 1, 2 => 3 ], [ 1, 2 ], [ ] ],
            [ [ 'a' => 1, 1 => 2, 2 => 3 ], [ 1, 2,  3 ], [ ], [ 'a' => 1 ] ]
        ];
    }



    public function diffKeysDataset() {
        return [
            [ [ 1,2,3 ], [ 2, 3 ], [ 4 ], [ 2 => 3 ] ],
            [ [ 'a'=>1,'b'=>2,'c' => 3 ], [ 'a' => 1, 3 ], [ 'c' => 1, 2 ], [ 'b'=>2 ] ]
        ];
    }



}