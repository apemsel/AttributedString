<?php
namespace apemsel\AttributedString;

/**
 * Extends AttributedString to support a tokenized string.
 *
 * You can mix working with tokens with working on string ranges using the AttributedString methods.
 * The original string is preserved.
 *
 * @author Adrian Pemsel <apemsel@gmail.com>
 */
class TokenizedAttributedString extends AttributedString
{
  protected $tokens;
  protected $tokenOffsets;
  
  /**
   * @param string|AttributedString $string String to work on
   * @param string $tokenizer Tokenizer to use, either "whitespace", "word" or a custom regex
   */
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
  
  /**
   * Return all tokens
   *
   * @return string[] tokens
   */
  public function getTokens() {
    return $this->tokens;
  }
  
  /**
   * Return all tokens' offsets
   *
   * @return int[] offsets
   */
  public function getTokenOffsets() {
    return $this->tokenOffsets;
  }
  
  /**
   * Return the number of tokens
   *
   * @return int count
   */
  public function getTokenCount() {
    return count($this->tokens);
  }

  /**
   * Get indicated token
   *
   * @param int $i token index
   * @return string token
   */
  public function getToken($i) {
    return $this->tokens[$i];
  }
  
  /**
   * Get indicated token offset
   *
   * @param int $i token index
   * @return int offset
   */
  public function getTokenOffset($i) {
    return $this->tokenOffsets[$i];
  }
  
  /**
   * Set a token to a given attribute and state
   *
   * @param int $i token index
   * @param string $attribute attribute name
   * @param bool $state attribute state
   */
  public function setTokenAttribute($i, $attribute, $state = true) {
    $token = $this->tokens[$i];
    $offset = $this->tokenOffsets[$i];
    $length = mb_strlen($token, "utf-8");
    
    return $this->setLength($offset, $length, $attribute, $state);
  }
  
  /**
   * Set a range of tokens to a given attribute and state
   *
   * @param int $from token start index
   * @param int $to token end index
   * @param string $attribute attribute name
   * @param bool $state attribute state
   */
  public function setTokenRangeAttribute($from, $to, $attribute, $state = true) {
    $fromOffset = $this->tokenOffsets[$from];
    $toOffset = $this->tokenOffsets[$to] + mb_strlen($this->tokens[$to], "utf-8") - 1;
    
    return $this->setRange($fromOffset, $toOffset, $attribute, $state);
  }
  
  /**
   * Set all tokens matching given dictionary to attribute and state
   *
   * @param string[] $dictionary dictionary
   * @param string $attribute attribute name
   * @param bool $state attribute state
   */
  public function setTokenDictionaryAttribute($dictionary, $attribute, $state = true) {
    foreach($this->tokens as $i => $token) {
      if (in_array($token, $dictionary)) {
        $this->setTokenAttribute($i, $attribute, $state);
      }
    }
  }
  
  /**
   * Get all attribute of token at given index
   *
   * @param int token index
   * @return string[] attributes
   */
  public function attributesAtToken($i) {
    return $this->attributesAt($this->tokenOffsets[$i]);
  }
  
  /**
   * Convert all tokens to lower case
   */
  public function lowercaseTokens() {
    $this->tokens = array_map(function($token) {
      return mb_strtolower($token, "utf-8");
    }, $this->tokens);
  }
  
  /**
   * Tokenize a string on whitespace
   *
   * @param string $string string to be tokenized
   * @return array array of two arrays, with tokens at index 0 and their offsets at index 1
   */
  public static function tokenizeOnWhitespace($string) {
    // Matches pontential whitespace in front of the token and the token itself.
    // Matching the whitespace could be omitted, but that results in slower execution ;-)
    return self::tokenizeOnRegex($string, '/[\s\n\r]*([^\s\n\r]+)/u');
  }
  
  /**
   * Tokenize a string on words
   *
   * @param string $string string to be tokenized
   * @return array array of two arrays, with tokens at index 0 and their offsets at index 1
   */
  public static function tokenizeOnWords($string) {
    return self::tokenizeOnRegex($string, '/([\w]+)/u');
  }
  
  /**
   * Tokenize a string with a given regex
   *
   * @param string $string string to be tokenized
   * @param string $pattern regex. The token must be captured in the first subgroup.
   * @return array array of two arrays, with tokens at index 0 and their offsets at index 1
   */
  public static function tokenizeOnRegex($string, $pattern)
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
