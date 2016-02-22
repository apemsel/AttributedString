<?php

namespace apemsel\AttributedString;

class MutableAttributedString extends AttributedString
{
  public function insert($pos, $string) {
    $length = mb_strlen($string, "utf-8");
    
    if ($pos == $this->length) { // append instead
      $this->string .= $string;
    } else { // insert at $pos
      $this->string = substr_replace($this->string, $string, $pos, 0);
    }
    
    $this->length += $length;
    $this->byte2Char = []; // invalidate cache
    
    foreach ($this->attributes as $attribute => &$map) {
      // Check state of surrounding map to determine state of inserted part
      $state = false;
      $maxPos = count($map) - 1;
      $leftState = $map[min($maxPos, $pos)];
      $rightState = $map[min($maxPos, $pos + 1)];
      
      if ($leftState == $rightState) {
        $state = $leftState;
      }
      
      array_splice($map, $pos, 0, array_fill(0, $length, $state));
    }
  }
  
  public function delete($pos, $length) {
    
  }
  
  public function replace($pos, $length, $string) {
    
  }
}
