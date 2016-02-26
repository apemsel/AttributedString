<?php
use apemsel\AttributedString\AttributedString;

class AttributedStringTest extends PHPUnit_Framework_TestCase
{
  public function testConstructAndToString() {
    $as = new AttributedString("foo");
    $this->assertEquals("foo", $as);
    
    $as2 = new AttributedString($as);
    $this->assertEquals("foo", $as2);
    
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
  
  public function testSetPatternAndIs() {
    $as = new AttributedString("äöü foo bar baz");
    $as->setPattern("/b[a-z]{2,2}/", "bold"); // set bar and baz to bold
    $this->assertEquals(true, $as->is("bold", 9));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(false, $as->is("bold", 11));

    // try again with byte2Char cache
    $as = new AttributedString("äöü foo bar baz");
    $as->enableByte2CharCache();
    $as->setPattern("/b[a-z]{2,2}/", "bold"); // set bar and baz to bold
    $this->assertEquals(true, $as->is("bold", 9));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(false, $as->is("bold", 11));
  }
  
  public function testSetSubstring() {
    $as = new AttributedString("foo bar baz bar");
    $as->setSubstring("bar", "bold"); // all instances of bar
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(true, $as->is("bold", 12));
    
    $as->setSubstring("bar", "underlined", false); // first instance of bar only
    $this->assertEquals(true, $as->is("underlined", 5));
    $this->assertEquals(false, $as->is("underlined", 3));
    $this->assertEquals(false, $as->is("underlined", 7));
    $this->assertEquals(false, $as->is("underlined", 12));
    
    $as = new AttributedString("foo BaR baz bar");
    $as->setSubstring("BAR", "bold", true, false); // case independet
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(true, $as->is("bold", 12));
    
  }
  
  public function testAttributesAt() {
    $as = new AttributedString("foo bar baz");
    $as->setLength(4, 3, "bold");
    $as->setLength(4, 7, "underlined");

    $this->assertEquals(["underlined"], $as->attributesAt(7));
    $this->assertEquals(["bold", "underlined"], $as->attributesAt(5));
    $this->assertEquals([], $as->attributesAt(3));
  }
  
  public function testSearchAttribute() {
    $as = new AttributedString("foo bar baz");
    $as->setLength(4, 3, "bold");

    $this->assertEquals(4, $as->searchAttribute("bold"));
    $this->assertEquals([4, 3], $as->searchAttribute("bold", 0, true));
    $this->assertEquals([0, 4], $as->searchAttribute("bold", 0, true, false), "search for false state of attribute");
    $this->assertEquals([0, 4], $as->searchAttribute("bold", 0, true, 0, false), "search for 0 state of attribute, non-strict");
    $this->assertEquals(false, $as->searchAttribute("bold", 0, true, 0, true), "search for 0 state of attribute, strict");
    $this->assertEquals(false, $as->searchAttribute("underlined"));
    $this->assertEquals(false, $as->searchAttribute("bold", 7));
    $this->assertEquals(false, $as->searchAttribute("underlined", 0, true));
  }

  public function testCombineAttributes() {
    $as = new AttributedString("foo bar baz");
    $as->setLength(4, 3, "bold");
    
    $this->assertEquals("foo <span class=\"bold\">bar</span> baz", $as->toHtml());
    $this->assertEquals("foo <div class=\"bold\">bar</div> baz", $as->toHtml("div"));
    $this->assertEquals("foo <span class=\"prefix-bold\">bar</span> baz", $as->toHtml("span", "prefix-"));
  }
  
  public function testToHtml() {
    $as = new AttributedString("foo bar baz");
    $as->setLength(4, 3, "bold");
    $as->setLength(0, 5, "underlined");
    $as->combineAttributes("or", "bold", "underlined", "either");
    $this->assertEquals([0, 7], $as->searchAttribute("either", 0, true));
    $as->combineAttributes("xor", "bold", "underlined", "eithernotboth");
    $this->assertEquals([0, 4], $as->searchAttribute("eithernotboth", 0, true));
    $as->combineAttributes("and", "bold", "underlined", "both");
    $this->assertEquals([4, 1], $as->searchAttribute("both", 0, true));
    $as->combineAttributes("not", "underlined", false, "notunderlined");
    $this->assertEquals([5, 6], $as->searchAttribute("notunderlined", 0, true));
    $this->setExpectedException('InvalidArgumentException');
    $as->combineAttributes("non-op", "underlined", "bold", "non-op");
  }
  
  public function testCount() {
    $as = new AttributedString("foo bar baz");
    
    $this->assertEquals(11, count($as));
  }
}
