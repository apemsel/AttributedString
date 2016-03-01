<?php

namespace apemsel\AttributedString;

/**
 * Extends AttributedString to support a mutable (changeable) string.
 *
 * @author Adrian Pemsel <apemsel@gmail.com>
 */
class MutableAttributedString extends AttributedString
{
  public function insert($pos, $string) {
    $length = mb_strlen($string, "utf-8");
    
    if ($pos == $this->length) { // append instead
      $this->string .= $string;
    } else { // insert at $pos
      $this->string = self::mb_substr_replace($this->string, $string, $pos, 0);
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
    $leftPart = "";
    if ($pos > 0) {
      $leftPart = mb_substr($this->string, 0, $pos - 1, "utf-8");
    }
    
    $rightPart = "";
    if ($pos + $length < $this->length) {
      $rightPart = mb_substr($this->string, $pos + $length, NULL, "utf-8");
    }
    
    $this->string = $leftPart.$rightPart;
    $this->length -= $length;
    
    foreach ($this->attributes as $attribute => &$map) {
      array_splice($map, $pos, $length);
    }
  }
  
  protected static function mb_substr_replace($string, $replacement, $start, $length = NULL) {
    // taken from https://gist.github.com/stemar/8287074
    if (is_array($string)) {
      $num = count($string);
      // $replacement
      $replacement = is_array($replacement) ? array_slice($replacement, 0, $num) : array_pad(array($replacement), $num, $replacement);
      // $start
      if (is_array($start)) {
        $start = array_slice($start, 0, $num);
        foreach ($start as $key => $value) {
          $start[$key] = is_int($value) ? $value : 0;
        }
      } else {
        $start = array_pad(array($start), $num, $start);
      }
      // $length
      if (!isset($length)) {
        $length = array_fill(0, $num, 0);
      } elseif (is_array($length)) {
        $length = array_slice($length, 0, $num);
        foreach ($length as $key => $value)
          $length[$key] = isset($value) ? (is_int($value) ? $value : $num) : 0;
      } else {
        $length = array_pad(array($length), $num, $length);
      }
      // Recursive call
      return array_map(__FUNCTION__, $string, $replacement, $start, $length);
    }
    preg_match_all('/./us', (string)$string, $smatches);
    preg_match_all('/./us', (string)$replacement, $rmatches);
    if ($length === NULL) $length = mb_strlen($string, "utf-8");
    array_splice($smatches[0], $start, $length, $rmatches[0]);
    
    return join($smatches[0]);
  }
}
