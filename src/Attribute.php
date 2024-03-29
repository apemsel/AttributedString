<?php

namespace apemsel\AttributedString;

interface Attribute extends \ArrayAccess, \Countable
{
    public function setRange(int $from, int $to, bool $state = true): void;
    public function toString(string $true = "1", string $false = "0"): string;
    public function search(int $offset = 0, bool $returnLength = false, bool $state = true, bool $strict = true);
}
