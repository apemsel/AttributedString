<?php
namespace apemsel\AttributedString;

class Bitmap implements \ArrayAccess, \Countable
{
  protected $bitmap;
  protected $length;
  
  public function __construct($length) {
    $this->length = $length;
    $this->bitmap = str_repeat(chr(0), ceil($this->length / 8));
  }
  
  public function __toString() {
    return $this->toString();
  }
  
  public function toString($true = "1", $false = "0") {
    $string = str_repeat($false, $this->length);
    for ($offset = 0; $offset < $this->length; $offset++) {
      if (ord($this->bitmap[(int) ($offset / 8)]) & (1 << $offset % 8)) {
        $string[$offset] = $true;
      }
    }
    
    return $string;
  }
  
  // ArrayAccess interface
  
  public function offsetExists($offset) {
    return is_int($offset) && $offset >= 0 && $offset < $this->length;
  }
  
  public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return (bool) (ord($this->bitmap[(int) ($offset / 8)]) & (1 << $offset % 8));
		} else {
			throw new \OutOfRangeException();
		}
	}
  
  public function offsetSet($offset, $value)
	{
		if ($this->offsetExists($offset)) {
			$index = (int) ($offset / 8);
			if ($value) {
				$this->bitmap[$index] = chr(ord($this->bitmap[$index]) | (1 << $offset % 8));
			} else {
				$this->bitmap[$index] = chr(ord($this->bitmap[$index]) & ~(1 << $offset % 8));
			}
		} else {
			throw new \OutOfRangeException();
		}
	}
  
  public function offsetUnset($offset) {
    throw new \RuntimeException("Bitmap does not support offsetUnset");
  }
  
  // Countable interface
  
  /**
   * Return bitmap length
   *
   * @return int bitmap length
   */
  public function count() {
    return $this->length;
  }
}
