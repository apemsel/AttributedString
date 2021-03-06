<?php
use apemsel\AttributedString\TokenizedAttributedString;

class TokenizedAttributedStringTest extends PHPUnit_Framework_TestCase
{
  public function testConstruct() {
    $as = new TokenizedAttributedString("foo bar baz");
    $this->assertEquals("foo bar baz", $as);
  }

  public function testGetTokens() {
    $as = new TokenizedAttributedString(" one two\nthree\rfour\n\r five  ");
    $this->assertEquals(["one", "two", "three", "four", "five"], $as->getTokens());
  }
  
  public function testPatternTokenizer() {
    $as = new TokenizedAttributedString("   ?foo, !bar. \n baz", "/([\w]+)/u");
    $this->assertEquals(["foo", "bar", "baz"], $as->getTokens());
  }
  
  public function testGetTokenOffsets() {
    $as = new TokenizedAttributedString(" one two\nthree\rfour\n\r five  ");
    $this->assertEquals([1, 5, 9, 15, 22], $as->getTokenOffsets());
  }
  
  public function testGetToken() {
    $as = new TokenizedAttributedString(" one two\nthree\rfour\n\r five  ");
    $this->assertEquals("three", $as->getToken(2));
  }
  
  public function testGetTokenOffset() {
    $as = new TokenizedAttributedString(" one two\nthree\rfour\n\r five  ");
    $this->assertEquals(9, $as->getTokenOffset(2));
    
    $as = new TokenizedAttributedString("ä ö ü");
    $this->assertEquals(2, $as->getTokenOffset(1));
    
  }
  
  public function testGetTokenCount() {
    $as = new TokenizedAttributedString(" one two\nthree\rfour\n\r five  ");
    $this->assertEquals(5, $as->getTokenCount());
  }
  
  public function testSetTokenAttribute() {
    $as = new TokenizedAttributedString("foo bär baz");
    $as->setTokenAttribute(1, "bold");
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
  }
  
  public function testSetTokenRangeAttribute() {
    $as = new TokenizedAttributedString("foo bar baz");
    $as->setTokenRangeAttribute(1, 2, "bold");
    $this->assertEquals(true, $as->is("bold", 4));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(true, $as->is("bold", 10));
  }
  
  public function testSetTokenDictionaryAttribute() {
    $as = new TokenizedAttributedString("foo bar baz");
    $as->setTokenDictionaryAttribute(["baz", "bar"], "bold");
    $this->assertEquals(true, $as->is("bold", 4));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(true, $as->is("bold", 10));
  }
  
  public function testAttributesAtToken() {
    $as = new TokenizedAttributedString("foo bar baz");
    $as->setTokenAttribute(1, "bold");
    $as->setLength(4, 7, "underlined");
    $this->assertEquals(["underlined"], $as->attributesAtToken(2));
    $this->assertEquals(["bold", "underlined"], $as->attributesAtToken(1));
    $this->assertEquals([], $as->attributesAtToken(0));
  }
  
  public function testLowercaseTokens() {
    $as = new TokenizedAttributedString("FOO bar bAz");
    $as->lowercaseTokens();
    $this->assertEquals(["foo", "bar", "baz"], $as->getTokens());
  }
  
  public function testArrayAccess() {
    $as = new TokenizedAttributedString("foo bar baz");
    $this->assertEquals("bar", $as[1]);
    $this->assertEquals(true, isset($as[1]));
    $this->assertEquals(false, isset($as[3]));
  }
}
