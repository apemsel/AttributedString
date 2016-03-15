<?php
namespace apemsel\AttributedString;

class BooleanArray implements Attribute
{
  protected $attribute;
  protected $length;
  
  public function __construct($length) {
    $this->length = $length;
    $this->attribute = array_fill(0, $length, false);
  }
  
  public function __toString() {
    return $this->toString();
  }
  
  public function toString($true = "1", $false = "0") {
    return implode("", array_map(function($v) use ($true, $false) {
      return $v ? $true : $false;
    }, $this->attribute));
  }
  
  /**
   * Set given range to a state
   *
   * @param int $from start offset
   * @param int $to end offset
   * @param bool $state set state to true (default) or false
   */
  public function setRange($from, $to, $state = true) {
    // Set attribute state for given range
    $this->attribute = array_replace($this->attribute, array_fill($from, $to-$from+1, $state));
  }
  
  /**
   * Search inside bitmap for ranges with the given state
   *
   * @param int $offset start offset
   * @param bool $returnLength if true (default is false), return an array with position and length of the found range
   * @param bool $state the state to look for (default is true)
   * @param bool $strict perform strict comparison during search
   * @return int|int[] either position or position and lenght in an array
   */
  public function search($offset = 0, $returnLength = false, $state = true, $strict = true) {
    $a = $this->attribute;
    if ($offset) {
      $a = array_slice($a, $offset, NULL, true);
    }
    
    $pos = array_search($state, $a, $strict);
    
    if ($returnLength) {
      if (false === $pos) {
        return false;
      }
      
      $a = array_slice($a, $pos - $offset);
      $length = array_search(!$state, $a, $strict);
      $length = $length ? $length : $this->length - $pos;
      return [$pos, $length];
    } else {
      return $pos;
    }
  }
  
  public function insert($pos, $length, $state) {
    array_splice($this->attribute, $pos, 0, array_fill(0, $length, $state));
  }
  
  public function delete($pos, $length) {
    array_splice($this->attribute, $pos, $length);
  }
  
  // ArrayAccess interface
  
  public function offsetExists($offset) {
    return $offset < $this->length;
  }
  
  public function offsetGet($offset)
	{
		return $this->attribute[$offset];
	}
  
  public function offsetSet($offset, $value)
	{
    $this->attribute[$offset] = $value;
	}
  
  public function offsetUnset($offset) {
    unset($this->attribute[$offset]);
  }
  
  // Countable interface
  
  /**
   * Return array length
   *
   * @return int array length
   */
  public function count() {
    return $this->length;
  }
}
