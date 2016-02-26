<?php

namespace apemsel\AttributedString;

class TokenizedAttributedString extends AttributedString
{
  protected $tokens;
  
  public function __construct($string, $tokenizer = "whitespace") {
    $this->tokens = self::tokenize($string, $tokenizer);
    parent::__construct($string);
  }
  
  public function getTokens() {
    return $this->tokens;
  }
  
  protected static function tokenize($string, $tokenizer)
  {
    if ($tokenizer == "whitespace")
    {
      // Remove excessive whitespace and convert to single space
      $string = trim(preg_replace('/[\s\n\r]+/u', ' ', $string));

      return explode(" ", $string);
    }
  }
}
