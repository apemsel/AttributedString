<?php
namespace apemsel\AttributedString;

class AttributedString
{
  protected $string;
  protected $attributes;
  protected $length;
  
  public function __construct($string) {
    if (is_string($string)) {
      $this->string = $string;
      $this->length = mb_strlen($string, "utf-8");
    }
    else {
      throw new \InvalidArgumentException();
    }
  }
  
  public function __toString() {
    return $this->string;
  }
    
  public function createAttribute($attribute) {
    if ($this->hasAttribute($attribute)) {
      throw new \InvalidArgumentException();
    }
    
    $this->attributes[$attribute] = array_fill(0, $this->length, false);
  }
  
  public function hasAttribute($attribute) {
    return isset($this->attributes[$attribute]);
  }
  
  public function deleteAttribute($attribute) {
    if (isset($this->attributes[$attribute])) {
      unset($this->attributes[$attribute]);
    }
  }
  
  public function setRange($from, $to, $attribute, $state = true) {
    // Ensure correct range
    $from = min($from, $this->length);
    $from = max($from, 0);
    $to = min($to, $this->length);
    $to = max($to, 0);
    
    // Be kind and swap from and to if mixed up
    if ($from>$to) {
      list($from, $to) = array($to, $from);
    }
    
    // Create attribute if it does not exist
    if (!$this->hasAttribute($attribute)) {
      $this->createAttribute($attribute);
    }

    // Set attribute state for given range
    $this->attributes[$attribute] = array_replace($this->attributes[$attribute], array_fill($from, $to-$from+1, $state));
  }
  
  public function setLength($from, $length, $attribute, $state = true) {
    return $this->setRange($from, $from+$length-1, $attribute, $state);
  }
  
  public function is($attribute, $pos) {
    return (isset($this->attributes[$attribute][$pos]) and $this->attributes[$attribute][$pos]);
  }
  
  public function attributesAt($pos) {
    $attributes = [];

    foreach ($this->attributes as $attribute => &$map) {
      if ($map[$pos]) {
        $attributes[] = $attribute;
      }
    }

    return $attributes;
  }
}
