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

}
