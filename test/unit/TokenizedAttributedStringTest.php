<?php
use apemsel\AttributedString\TokenizedAttributedString;

class TokenizedAttributedStringTest extends PHPUnit_Framework_TestCase
{
  public function testConstruct() {
    $as = new TokenizedAttributedString("foo bar baz");
    $this->assertEquals("foo bar baz", $as);
  }
  
  public function testGetTokens() {
    $as = new TokenizedAttributedString("foo bar baz");
    $this->assertEquals(["foo", "bar", "baz"], $as->getTokens());    
  }
}
