<?php
namespace apemsel\AttributedString;

class AttributedString implements \Countable
{
  protected $string;
  protected $attributes;
  protected $length;
  protected $byte2Char;
  
  public function __construct($string) {
    if (is_string($string)) {
      $this->string = $string;
      $this->length = mb_strlen($string, "utf-8");
    }
    elseif ($string instanceof AttributedString) {
      $this->string = $string->string;
      $this->attributes = $string->attributes;
      $this->lenght = $string->length;
      $this->byte2Char = $string->byte2Char;
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
      list($from, $to) = [$to, $from];
    }
    
    // Create attribute if it does not exist
    if (!$this->hasAttribute($attribute)) {
      $this->createAttribute($attribute);
    }

    // Set attribute state for given range
    $this->attributes[$attribute] = array_replace($this->attributes[$attribute], array_fill($from, $to-$from+1, $state));
  }
  
  public function setLength($from, $length, $attribute, $state = true) {
    return $this->setRange($from, $from + $length - 1, $attribute, $state);
  }
  
  public function setPattern($pattern, $attribute, $state = true) {
    if ($ret = preg_match_all($pattern, $this->string, $matches, PREG_OFFSET_CAPTURE)) {
      foreach($matches[0] as $match)
      {
        $match[1] = $this->byte2charOffset($match[1]);
        $this->setRange($match[1], $match[1]+mb_strlen($match[0], "utf-8")-1, $attribute, $state);
      }

      return $ret;
    }
  }
  
  public function setSubstring($substring, $attribute, $all = true, $matchCase = true) {
    $offset = 0;
    $length = mb_strlen($substring, "utf-8");
    $func = $matchCase ? "mb_strpos" : "mb_stripos";
    
    while (false !== $pos = $func($this->string, $substring, $offset, "utf-8")) {
      $this->setRange($pos, $pos + $length - 1, $attribute);
      if (!$all) {
        return;
      }
      $offset = $pos + $length;
    }
  }
  
  public function searchAttribute($attribute, $offset = 0, $returnLength = false, $state = true, $strict = true) {
    if (!$this->hasAttribute($attribute)) {
      return false;
    }
    
    $a = $this->attributes[$attribute];

    if ($offset) {
      $a = array_slice($a, $offset, $this->length, true);
    }
    
    $pos = array_search($state, $a, $strict);
    
    if ($returnLength) {
      if (false === $pos) {
        return false;
      }
      
      $a = array_slice($a, $pos);
      $length = array_search(!$state, $a, $strict);
      $length = $length ? $length : $this->length - $pos;

      return [$pos, $length];
    } else {
      return $pos;
    }
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
            if ($attribute != array_pop($stack))
            {
              throw new Exception("Attributes are not properly nested for HTML conversion");
            }
            $html .= "</$tag>";
          }
        }
      }
    }

    $html .= mb_substr($this->string, $lastPos, $this->length-$lastPos, 'utf-8');

    // Close all spans that remained open
    $html .= str_repeat("</$tag>", count($stack));

    return $html;
  }
  
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
  
  public function enableByte2CharCache() {
    $this->byte2Char = [];
    $char = 0;
    for ($i = 0; $i < strlen($this->string); ) {
      $char++;
      $byte = $this->string[$i];
      $cl = self::utf8CharLen($byte);
      $i += $cl;
      
      $this->byte2Char[$i] = $char;
    }
  }
  
  protected function byte2charOffset($boff) {
    if (isset($this->byte2Char[$boff])) return $this->byte2Char[$boff];
    
    return $this->byte2Char[$boff] = self::byte2charOffsetString($this->string, $boff);
  }

  protected function char2ByteOffset($char) {
    $byte = strlen(mb_substr($this->string, 0, $char, "utf-8"));
    if (!isset($this->byte2Char[$byte])) $this->byte2Char[$byte] = $char;
    
    return $byte;
  }
  
  protected static function byte2charOffsetString($string, $boff) {
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
  
  // For Countable interface
  public function count() {
    return $this->length;
  }
}
