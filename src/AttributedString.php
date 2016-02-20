<?php
namespace apemsel\AttributedString;

class AttributedString
{
  protected $s;
  
  public function __construct($string) {
    $this->s = $string;
  }
  
  public function __toString() {
    return $this->s;
  }
}
