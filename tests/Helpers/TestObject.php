<?php

namespace Tests\Helpers;


class TestObject {
    private $identifier;
    private $label;

    /**
     * TestObject constructor.
     * @param $identifier
     * @param $label
     */
    public function __construct($identifier, $label) {
        $this->identifier = $identifier;
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @return mixed
     */
    public function getLabel() {
        return $this->label;
    }



}