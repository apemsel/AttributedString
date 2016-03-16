<?php
namespace apemsel\AttributedString;

/**
 * Extends AttributedString to support a mutable (changeable) string.
 *
 * During insert and delete operations the attribute layers are updated as smart as possible.
 *
 * @author Adrian Pemsel <apemsel@gmail.com>
 */
class MutableAttributedString extends AttributedString
{
  /**
   * @param string|AttributedString $string Either a simple string or another AttributedString to init the AttributedString
   * @param string $attributeClass Class to use for attributes
   */
  public function __construct($string, $attributeClass = "apemsel\AttributedString\BooleanArray") {
    if (!in_array("apemsel\AttributedString\MutableAttribute", class_implements($attributeClass))) {
      throw new \InvalidArgumentException("MutableAttributedString can only be used with attributes implementing MutableAttribute");
    }
    
    parent::__construct($string, $attributeClass);
  }
  
  /**
   * Insert string at given offset
   *
   * @param int $pos offset
   * @param string $string string to be inserted
   */
  public function insert($pos, $string) {
    $length = mb_strlen($string, "utf-8");
    
    if ($pos == $this->length) { // append instead
      $this->string .= $string;
    } else { // insert at $pos
      $this->string = self::mb_substr_replace($this->string, $string, $pos, 0);
    }
    
    $this->length += $length;
    $this->byte2Char = []; // invalidate cache
    
    foreach ($this->attributes as $name => $attribute) {
      // Check state of surrounding map to determine state of inserted part
      $state = false;
      $maxPos = count($attribute) - 1;
      $leftState = $attribute[min($maxPos, $pos)];
      $rightState = $attribute[min($maxPos, $pos + 1)];
      
      if ($leftState == $rightState) {
        $state = $leftState;
      }
      
      $attribute->insert($pos, $length, $state);
    }
  }
  
  /**
   * Delete substring of given offset and length
   *
   * @param int $pos offset
   * @param int $length length
   */
  public function delete($pos, $length) {
    $leftPart = "";
    if ($pos >= 0) {
      $leftPart = mb_substr($this->string, 0, $pos, "utf-8");
    }
    
    $rightPart = "";
    if ($pos + $length < $this->length) {
      $rightPart = mb_substr($this->string, $pos + $length, NULL, "utf-8");
    }
    
    $this->string = $leftPart.$rightPart;
    $this->length -= $length;
    
    foreach ($this->attributes as $name => $attribute) {
      $attribute->delete($pos, $length);
    }
  }
  
  /**
   * Missing mb_substr_replace() implementation
   *
   * @see https://gist.github.com/stemar/8287074 Original source
   * @param string $string string to work on
   * @param string $replacement replacement string
   * @param int $start offset
   * @param int $length length
   * @return string modified string
   */
  protected static function mb_substr_replace($string, $replacement, $start, $length = NULL) {
    
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
  
  // Modified ArrayAccess interface
  
  /**
   * Replace char at given offset
   *
   * @param int $offset offset
   */
  public function offsetSet($offset, $value) {
    $this->string = self::mb_substr_replace($this->string, $value, $offset, mb_strlen($value, "utf-8"));
  }
  
  /**
   * Unset char at given offset
   *
   * @param int $offset offset
   */
  public function offsetUnset($offset) {
    $this->delete($offset, 1);
  }
}
