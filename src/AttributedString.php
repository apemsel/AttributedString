<?php
namespace apemsel\AttributedString;

/**
 * Basic class to work with attributed strings.
 *
 * Attributed strings are strings that can have multiple attributes per character of the string
 *
 * @author Adrian Pemsel <apemsel@gmail.com>
 */
class AttributedString implements \Countable, \ArrayAccess
{
  protected $string;
  protected $attributes = [];
  protected $length;
  protected $byteToChar;
  
  /**
   * @param string|AttributedString $string Either a simple string or another AttributedString to init the AttributedString
   */
  public function __construct($string) {
    if (is_string($string)) {
      $this->string = $string;
      $this->length = mb_strlen($string, "utf-8");
    }
    elseif ($string instanceof AttributedString) {
      $this->string = $string->string;
      $this->attributes = $string->attributes;
      $this->lenght = $string->length;
      $this->byteToChar = $string->byteToChar;
    }
    else {
      throw new \InvalidArgumentException();
    }
  }
  
  /**
   * Returns the native string
   *
   * @return string The native string representation of the AttributedString without attributes
   */
  public function __toString() {
    return $this->string;
  }
  
  /**
   * Creates a new attribute layer
   *
   * @param string $attribute The name of the new attribute
   * @throws InvalidArgumentException if the attribute already exists
   */
  public function createAttribute($attribute) {
    if ($this->hasAttribute($attribute)) {
      throw new \InvalidArgumentException();
    }
    
    $this->attributes[$attribute] = array_fill(0, $this->length, false);
  }
  
  /**
   * Check if the given attribute exists
   *
   * @param string $attribute The name of the attribute to check
   * @return bool
   */
  public function hasAttribute($attribute) {
    return isset($this->attributes[$attribute]);
  }
  
  public function deleteAttribute($attribute) {
    if (isset($this->attributes[$attribute])) {
      unset($this->attributes[$attribute]);
    }
  }
  
  /**
   * Set given range of the string to an attribute and state
   *
   * @param int $from start offset
   * @param int $to end offset
   * @param string $attribute name of the attribute to be set
   * @param bool $state set state to true (default) or false
   */
  public function setRange($from, $to, $attribute, $state = true) {
    // Ensure correct range
    $from = min($from, $this->length);
    $from = max($from, 0);
    $to = min($to, $this->length);
    $to = max($to, 0);
    
    // Be kind and swap from and to if mixed up
    if ($from>$to) {
      list($from, $to) = [$to, $from];
    }
    
    // Create attribute if it does not exist
    if (!$this->hasAttribute($attribute)) {
      $this->createAttribute($attribute);
    }

    // Set attribute state for given range
    $this->attributes[$attribute] = array_replace($this->attributes[$attribute], array_fill($from, $to-$from+1, $state));
  }
  
  /**
   * Set given length of the string to an attribute and state
   *
   * @param int $from start offset
   * @param int $length length to be set
   * @param string $attribute name of the attribute to be set
   * @param bool $state set state to true (default) or false
   */
  public function setLength($from, $length, $attribute, $state = true) {
    return $this->setRange($from, $from + $length - 1, $attribute, $state);
  }
  
  /**
   * Set parts of the string matching a given regex to an attribute and state
   *
   * @param string $pattern regex pattern
   * @param string $attribute name of the attribute to be set
   * @param bool $state set state to true (default) or false
   * @return int number of matches
   */
  public function setPattern($pattern, $attribute, $state = true) {
    if ($ret = preg_match_all($pattern, $this->string, $matches, PREG_OFFSET_CAPTURE)) {
      foreach($matches[0] as $match)
      {
        $match[1] = $this->byteToCharOffset($match[1]);
        $this->setRange($match[1], $match[1]+mb_strlen($match[0], "utf-8")-1, $attribute, $state);
      }

      return $ret;
    }
  }
  
  /**
   * Set given substring to an attribute and state
   *
   * @param string $substring the substring to search
   * @param string $attribute name of the attribute to be set
   * @param bool $all set first or all occurences of the substring
   * @param bool $matchCase match or ignore case
   * @param bool $state set state to true (default) or false
   */
  public function setSubstring($substring, $attribute, $all = true, $matchCase = true, $state = true) {
    $offset = 0;
    $length = mb_strlen($substring, "utf-8");
    $func = $matchCase ? "mb_strpos" : "mb_stripos";
    
    while (false !== $pos = $func($this->string, $substring, $offset, "utf-8")) {
      $this->setRange($pos, $pos + $length - 1, $attribute, $state);
      if (!$all) {
        return;
      }
      $offset = $pos + $length;
    }
  }
  
  /**
   * Search inside the string for ranges with the given attribute
   *
   * @param string $attribute name of the attribute to search
   * @param int $offset start offset
   * @param bool $returnLength if true (default is false), return an array with position and length of the found range
   * @param bool $state the state to look for (default is true)
   * @param bool $strict perform strict comparison during search
   * @return int|int[] either position or position and lenght in an array
   */
  public function searchAttribute($attribute, $offset = 0, $returnLength = false, $state = true, $strict = true) {
    if (!$this->hasAttribute($attribute)) {
      return false;
    }
    
    $a = $this->attributes[$attribute];

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
  
  /**
   * Check for given attribute at a offset
   *
   * @param string $attribute name of the attribute to check
   * @param int $pos offset to check
   * @return bool true if string has the attribute at the given position
   */
  public function is($attribute, $pos) {
    return (isset($this->attributes[$attribute][$pos]) and $this->attributes[$attribute][$pos]);
  }
  
  /**
   * Return an array of substrings that have a given attribute
   *
   * @param string $attribute name of the attribute
   * @param int $pos offset
   * @param bool $state the state to look for (default is true)
   * @param bool $strict perform strict comparison during search
   * @return string[] array of strings with given attribute
   */
  public function substrings($attribute, $offset = 0, $state = true, $strict = true)
  {
    $substrings = [];
    while (false !== $pl = $this->searchAttribute($attribute, $offset, true, $state, $strict))
    {
      //var_dump($pl);
      $substring = mb_substr($this->string, $pl[0], $pl[1], "UTF-8");
      $substrings[] = $substring;
      $offset = $pl[0] + $pl[1];
    }
    
    return $substrings;
  }
  
  /**
   * Return all parts of the string that have a given attribute as new string
   *
   * @param string $attribute name of the attribute
   * @param int $pos offset
   * @param bool $state the state to look for (default is true)
   * @param bool $strict perform strict comparison during search
   * @param string $glue glue that is inserted between the parts, default is nothing ("")
   * @return string combined filtered string
   */
  public function filter($attribute, $offset = 0, $state = true, $strict = true, $glue = "")
  {
    return implode($glue, $this->substrings($attribute, $offset, $state, $strict));
  }
  
  /**
   * Return all attributes at a given offset
   *
   * @param int $pos offset
   * @return string[] attributes at the given offset
   */
  public function attributesAt($pos) {
    $attributes = [];

    foreach ($this->attributes as $attribute => &$map) {
      if ($map[$pos]) {
        $attributes[] = $attribute;
      }
    }

    return $attributes;
  }
  
  /**
   * Convert to HTML, using a given class to mark attribute spans
   *
   * @param string $tag HTML tag to use for the spans (defaults is "<span>")
   * @param string $classPrefix Optional prefix used to convert the attribute names to class names
   * @return string HTML
   */
  public function toHtml($tag = "span", $classPrefix = "") {
    foreach($this->attributes as $attribute => $map) $state[$attribute] = false;

    $html = "";
    $stack = [];
    $lastPos = 0;

    for ($i=0; $i<$this->length; $i++)
    {
      foreach($this->attributes as $attribute => &$map)
      {
        if ($this->attributes[$attribute][$i] != $state[$attribute])
        {
          $state[$attribute] = $this->attributes[$attribute][$i];

          $html .= mb_substr($this->string, $lastPos, $i-$lastPos, "utf-8");
          $lastPos = $i;

          if ($state[$attribute])
          {
            $html .= "<$tag class=\"$classPrefix$attribute\">";
            $stack[] = $attribute;
          }
          else
          {
            // Close attribute span. If the top of the stack does not equal the attribute to be closed
            // close, pop and stash it. This happens when span a ends in span b.
            $stashed = [];
            while($open = array_pop($stack))
            {
              $html .= "</$tag>";
              if ($attribute == $open) {
                break;
              }
              $stashed[] = $open;
            }
            
            // Now repopen the stashed spans and put them back on the stack.
            foreach($stashed as $a) {
              $stack[] = $a;
              $html .= "<$tag class=\"$classPrefix$a\">";
            }
          }
        }
      }
    }

    $html .= mb_substr($this->string, $lastPos, $this->length-$lastPos, 'utf-8');

    // Close all spans that remained open
    $html .= str_repeat("</$tag>", count($stack));

    return $html;
  }
  
  /**
   * Combine attributes with the given boolean operation
   *
   * @param string $op one of or|xor|and|not
   * @param string $attribute1 name of the first attribute
   * @param string $attribute2 Name of the second attribute. Ignored for "not" operation.
   * @param string $to optional name of the attribute to copy the result to
   * @throws InvalidArgumentException if one of the attributes does not exist or an unkown operation is given
   */
  public function combineAttributes($op, $attribute1, $attribute2 = false, $to = false)
  {
    $to = isset($to) ? $to : $attribute1;
    $op = strtolower($op);
    
    if ($op == "not") {
      $attribute2 = $attribute1;
    }

    if (!$this->hasAttribute($attribute1) or !$this->hasAttribute($attribute2)) {
      throw new \InvalidArgumentException("Attribute does not exist");
    }
    
    if (!isset($this->attributes[$to])) {
      $this->attributes[$to] = []; // No need to init because array is created below
    }
    
    // Switch outside the loops for speed
    switch ($op) {
      case 'or':
        for($i = 0; $i < $this->length; $i++) {
          $this->attributes[$to][$i] = $this->attributes[$attribute1][$i] || $this->attributes[$attribute2][$i];
        }
      break;
      
      case 'xor':
        for($i = 0; $i < $this->length; $i++) {
          $this->attributes[$to][$i] = ($this->attributes[$attribute1][$i] xor $this->attributes[$attribute2][$i]);
        }
      break;

      case 'and':
        for($i = 0; $i < $this->length; $i++) {
          $this->attributes[$to][$i] = $this->attributes[$attribute1][$i] && $this->attributes[$attribute2][$i];
        }
      break;

      case 'not':
        for($i = 0; $i < $this->length; $i++) {
          $this->attributes[$to][$i] = !$this->attributes[$attribute1][$i];
        }
      break;
      
      default:
        throw new \InvalidArgumentException("Unknown operation");
    }
  }
  
  /**
   * Convert attribute map to a visual string representation (e.g. for debugging)
   *
   * @param string $attribute name of the attribute
   * @param string $true char to use for true state of attribute
   * @param string $false char to use for false state of attribute
   */
  public function attributeToString($attribute, $true = "-", $false = " ") {
    $map = $this->attributes[$attribute];
    
    return implode("", array_map(function($v) use ($true, $false) {
      return $v ? $true : $false;
    }, $map));
  }
  
  /**
   * Enable and fill cache for byte to char offset conversion
   *
   * May improve performance if setPattern is used extensively
   */
  public function enableByteToCharCache() {
    $this->byteToChar = [];
    $char = 0;
    for ($i = 0; $i < strlen($this->string); ) {
      $char++;
      $byte = $this->string[$i];
      $cl = self::utf8CharLen($byte);
      $i += $cl;
      
      $this->byteToChar[$i] = $char;
    }
  }
  
  protected function byteToCharOffset($boff) {
    if (isset($this->byteToChar[$boff])) return $this->byteToChar[$boff];
    
    return $this->byteToChar[$boff] = self::byteToCharOffsetString($this->string, $boff);
  }

  protected function charToByteOffset($char) {
    $byte = strlen(mb_substr($this->string, 0, $char, "utf-8"));
    if (!isset($this->byteToChar[$byte])) $this->byteToChar[$byte] = $char;
    
    return $byte;
  }
  
  protected static function byteToCharOffsetString($string, $boff) {
    $result = 0;
    
    for ($i = 0; $i < $boff; ) {
      $result++;
      $byte = $string[$i];
      $cl = self::utf8CharLen($byte);
      $i += $cl;
    }
    
    return $result;
  }
  
  protected static function utf8CharLen($byte) {
    $base2 = str_pad(base_convert((string) ord($byte), 10, 2), 8, "0", STR_PAD_LEFT);
    $p = strpos($base2, "0");
    
    if ($p == 0) {
      return 1;
    } elseif ($p <= 4) {
      return $p;
    } else {
      throw new \InvalidArgumentException();
    }
  }
  
  // Countable interface
  
  /**
   * Return string length (number of UTF-8 chars, not strlen())
   *
   * @return int string length
   */
  public function count() {
    return $this->length;
  }
  
  // ArrayAccess interface
  
  /**
   * Check if the given offset exists in the string
   *
   * @param int $offset offset
   * @return bool does the offset exist
   */
  public function offsetExists($offset) {
    return $offest < $this->length;
  }
  
  /**
   * Get char at given offset
   *
   * Note: Since AttributedString is using UTF-8, the returned char may be longer than 1 byte!
   *
   * @param int $offset offset
   * @return string character
   */
  public function offsetGet($offset) {
    return mb_substr($this->string, $offset, 1, "utf-8");
  }
  
  /**
   * Not implemented since AttributedString is immutable
   *
   * @throws InvalidArgumentException always
   */
  public function offsetSet($offset, $value) {
    throw new \InvalidArgumentException("AttributedString is immutable");
  }
  
  /**
   * Not implemented since AttributedString is immutable
   *
   * @throws InvalidArgumentException always
   */
  public function offsetUnset($offset) {
    throw new \InvalidArgumentException("AttributedString is immutable");
  }
}
