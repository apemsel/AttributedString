<?php
use apemsel\AttributedString\AttributedString;

class AttributedStringTest extends PHPUnit_Framework_TestCase
{
  public function testConstructAndToString() {
    $as = new AttributedString("foo");
    $this->assertEquals("foo", $as);
    
    $this->setExpectedException('InvalidArgumentException');
    $as = new AttributedString(1);
  }
  
  public function testBasicAttributes() {
    $as = new AttributedString("foo");
    $as->createAttribute("attribute");
    
    $this->assertEquals(true, $as->hasAttribute("attribute"));
    $this->assertEquals(false, $as->hasAttribute("non-existing"));
    
    $as->deleteAttribute("attribute");
    $this->assertEquals(false, $as->hasAttribute("attribute"));
  }
  
  public function testSetRangeAndIs() {
    $as = new AttributedString("foo bar baz");
    $as->setRange(4, 6, "bold");
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
  }
  
  public function testSetLengthAndIs() {
    $as = new AttributedString("foo bar baz");
    $as->setLength(4, 3, "bold");
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
  }
  
  public function testAttributesAt() {
    $as = new AttributedString("foo bar baz");
    $as->setLength(4, 3, "bold");
    $as->setLength(4, 7, "underlined");

    $this->assertEquals(["underlined"], $as->attributesAt(7));
    $this->assertEquals(["bold", "underlined"], $as->attributesAt(5));
    $this->assertEquals([], $as->attributesAt(3));
  }
}
