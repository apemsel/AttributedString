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
}
