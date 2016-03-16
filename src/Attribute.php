<?php
namespace apemsel\AttributedString;

interface Attribute extends \ArrayAccess, \Countable
{
  public function setRange($from, $to, $state = true);
  public function toString($true = "1", $false = "0");
  public function search($offset = 0, $returnLength = false, $state = true, $strict = true);
}
