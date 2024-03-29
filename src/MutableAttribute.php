<?php

namespace apemsel\AttributedString;

interface MutableAttribute extends Attribute
{
    public function insert($offset, $length, $state);
    public function delete($offset, $length);
}
