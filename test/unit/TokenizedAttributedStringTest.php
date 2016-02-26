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
  }
  
  public function testSetTokenAttribute() {
    $as = new TokenizedAttributedString("foo bar baz");
    $as->setTokenAttribute(1, "bold");
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
  }
  
  public function testAttributesAtToken() {
    $as = new TokenizedAttributedString("foo bar baz");
    $as->setTokenAttribute(1, "bold");
    $as->setLength(4, 7, "underlined");
    $this->assertEquals(["underlined"], $as->attributesAtToken(2));
    $this->assertEquals(["bold", "underlined"], $as->attributesAtToken(1));
    $this->assertEquals([], $as->attributesAtToken(0));
  }
}
