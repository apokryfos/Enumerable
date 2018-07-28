<?php

namespace Apokryfos\Contracts;


interface Reducer {
    public function reduce($callback, $initial);
    public function sum($callback = null);
    public function average($callback = null);
}