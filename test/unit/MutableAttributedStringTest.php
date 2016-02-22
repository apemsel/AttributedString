<?php
use apemsel\AttributedString\MutableAttributedString;

class MutableAttributedStringTest extends PHPUnit_Framework_TestCase
{
  public function testInsert() {
    $as = new MutableAttributedString("foo baz");

    $as->setLength(0, 7, "bold");
    $as->insert(4, "bar ");
    $this->assertEquals("foo bar baz", $as);
    $this->assertEquals(true, $as->is("bold", 0), "start should still be bold");
    $this->assertEquals(true, $as->is("bold", 10), "end should still be bold");
    $this->assertEquals(true, $as->is("bold", 5), "bar should be bold since it was inserted in a span of bold");
    
    $as = new MutableAttributedString("foo bar");

    $as->setLength(0, 3, "bold");
    $as->setLength(0, 7, "underlined");
    $as->insert(7, " baz"); // append
    $this->assertEquals("foo bar baz", $as);
    $this->assertEquals(true, $as->is("bold", 0), "start should still be bold");
    $this->assertEquals(false, $as->is("bold", 10), "end should not be bold");
    $this->assertEquals(true, $as->is("bold", 0), "start should still be underlined");
    $this->assertEquals(false, $as->is("bold", 10), "end should not be underlined");
  }
}
