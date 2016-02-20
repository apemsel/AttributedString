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
  
  public function setPattern($pattern, $attribute, $state = true)
  {
    if ($ret = preg_match_all($pattern, $this->string, $matches, PREG_OFFSET_CAPTURE)) {
      foreach($matches[0] as $match)
      {
        $match[1] = $this->byte2charOffset($match[1]);
        $this->setRange($match[1], $match[1]+mb_strlen($match[0], "utf-8")-1, $attribute, $state);
      }

      return $ret;
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
  
  protected function byte2charOffset($boff)
  {
    if (isset($this->byte2Char[$boff])) return $this->byte2Char[$boff];
    return $this->byte2Char[$boff] = self::byte2charOffsetString($this->string, $boff);
  }

  protected function char2ByteOffset($char)
  {
    $byte = strlen(mb_substr($this->string, 0, $char, 'utf-8'));
    if (!isset($this->byte2Char[$byte])) $this->byte2Char[$byte] = $char;
    
    return $byte;
  }
  
  protected static function byte2charOffsetString($string, $boff)
  {
    $result = 0;
    
    for ($i = 0; $i < $boff; ) {
      $result++;
      $byte = $string[$i];
      $base2 = str_pad(base_convert((string) ord($byte), 10, 2), 8, "0", STR_PAD_LEFT);
      $p = strpos($base2, "0");
      if ($p == 0) $i++;
      elseif ($p <= 4) $i += $p;
      else return false;
    }
    
    return $result;
  }
  
  // For Countable interface
  public function count() {
    return $this->length;
  }
}
