<?php

namespace Apokryfos\Helpers;


final class GeneratorChain {

    private $generator;

    public function __construct(\Generator $generator) {
        $this->generator = $generator;
    }

    public function append(...$generators) {
        foreach ($generators as $generator) {
            $this->generator = GeneratorHelpers::append($this->generator, $generator);
        }
        return $this;
    }

    public function prepend(\Generator $generator) {
        $this->generator = GeneratorHelpers::prepend($this->generator, $generator);
        return $this;
    }



    public function value() {
        yield from $this->generator;
    }

}