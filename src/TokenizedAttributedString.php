<?php

namespace apemsel\AttributedString;

class TokenizedAttributedString extends AttributedString
{
  protected $tokens;
  protected $tokenOffsets;
  
  public function __construct($string, $tokenizer = "whitespace") {
    $tokenizerFunction = "tokenizeOn".ucfirst($tokenizer);

    if ($tokenizer[0] == "/") {
      list($this->tokens, $this->tokenOffsets) = self::tokenizeOnRegex($string, $tokenizer);
    } else {
      if (!method_exists("apemsel\AttributedString\TokenizedAttributedString", $tokenizerFunction)) {
        throw new \InvalidArgumentException("Unknown tokenizer $tokenizer");
      }
      list($this->tokens, $this->tokenOffsets) = self::$tokenizerFunction($string);
    }
    
    parent::__construct($string);
  }
  
  public function getTokens() {
    return $this->tokens;
  }
  
  public function getTokenOffsets() {
    return $this->tokenOffsets;
  }

  public function getToken($i) {
    return $this->tokens[$i];
  }
  
  public function getTokenOffset($i) {
    return $this->tokenOffsets[$i];
  }
    
  public function setTokenAttribute($i, $attribute, $state = true) {
    $token = $this->tokens[$i];
    $offset = $this->tokenOffsets[$i];
    $length = strlen($token);
    
    return $this->setLength($offset, $length, $attribute, $state);
  }
  
  public function setTokenRangeAttribute($from, $to, $attribute, $state = true) {
    $fromOffset = $this->tokenOffsets[$from];
    $toOffset = $this->tokenOffsets[$to] + strlen($this->tokens[$to]);
    
    return $this->setRange($fromOffset, $toOffset, $attribute, $state);
  }
  
  public function attributesAtToken($i) {
    return $this->attributesAt($this->tokenOffsets[$i]);
  }
  
  protected static function tokenizeOnWhitespace($string) {
    // Matches pontential whitespace in front of the token and the token itself.
    // Matching the whitespace could be omitted, but that results in slower execution ;-)
    return self::tokenizeOnRegex($string, '/[\s\n\r]*([^\s\n\r]+)/u');
  }
  
  protected static function tokenizeOnRegex($string, $pattern)
  {
    // Fastest way to get both tokens and their offsets, but not easy to understand.
    preg_match_all($pattern, $string, $matches, PREG_OFFSET_CAPTURE);

    // $matches[1] contains an array of all matched subexpressions (= tokens)
    // with their offset in column 1 and the matched token in column 0
    $tokens = array_column($matches[1], 0);
    $tokenOffsets = array_column($matches[1], 1);
    
    return [$tokens, $tokenOffsets];
  }
}
