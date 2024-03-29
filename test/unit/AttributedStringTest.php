<?php
use apemsel\AttributedString\AttributedString;
use PHPUnit\Framework\TestCase;

class AttributedStringTest extends TestCase
{
  public function testConstructAndToString() {
    $as = new AttributedString("fóò");
    $this->assertEquals("fóò", $as);

    $as2 = new AttributedString($as);
    $this->assertEquals("fóò", $as2);
    $this->expectException('InvalidArgumentException');
    $as = new AttributedString(1);

  }

  public function testBasicAttributes() {
    $as = new AttributedString("fóò");
    $as->createAttribute("attribute");

    $this->assertEquals(true, $as->hasAttribute("attribute"));
    $this->assertEquals(false, $as->hasAttribute("non-existing"));

    $as->deleteAttribute("attribute");
    $this->assertEquals(false, $as->hasAttribute("attribute"));
  }

  public function testSetRangeAndIs() {
    $as = new AttributedString("fóò bar bäz");
    $as->setRange(4, 6, "bold");
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
  }

  public function testSetLengthAndIs() {
    $as = new AttributedString("fóò bar bäz");
    $as->setLength(4, 3, "bold");
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
  }

  public function testSetPatternAndIs() {
    $as = new AttributedString("äöü fóò bar bäz");
    $as->setPattern("/b[a-z]{2,2}/", "bold"); // set bar and bäz to bold
    $this->assertEquals(true, $as->is("bold", 9));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(false, $as->is("bold", 11));

    // try again with byte2Char cache
    $as = new AttributedString("äöü fóò bar bäz");
    $as->enableByteToCharCache();
    $as->setPattern("/b[a-z]{2,2}/", "bold"); // set bar and bäz to bold
    $this->assertEquals(true, $as->is("bold", 9));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(false, $as->is("bold", 11));
  }

  public function testSetSubstring() {
    $as = new AttributedString("fóò bar bäz bar");
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

    $as = new AttributedString("fóò BaR bäz bar");
    $as->setSubstring("BAR", "bold", true, false); // case independet
    $this->assertEquals(true, $as->is("bold", 5));
    $this->assertEquals(false, $as->is("bold", 3));
    $this->assertEquals(false, $as->is("bold", 7));
    $this->assertEquals(true, $as->is("bold", 12));

  }

  public function testSubstrings() {
    $as = new AttributedString("fóò bar bäz zab rab oof");
    $as->setLength(4, 3, "bold");
    $as->setLength(8, 3, "bold");
    $this->assertEquals(["bar", "bäz"], $as->substrings("bold"));
  }

  public function testFilter() {
    $as = new AttributedString("fóò bar bäz zab rab oof");
    $as->setLength(4, 3, "bold");
    $as->setLength(8, 3, "bold");
    $this->assertEquals("barbäz", $as->filter("bold"));
    $this->assertEquals("bar,bäz", $as->filter("bold", 0, true, true, ","));
  }

  public function testAttributesAt() {
    $as = new AttributedString("fóò bar bäz");
    $as->setLength(4, 3, "bold");
    $as->setLength(4, 7, "underlined");

    $this->assertEquals(["underlined"], $as->attributesAt(7));
    $this->assertEquals(["bold", "underlined"], $as->attributesAt(5));
    $this->assertEquals([], $as->attributesAt(3));
  }

  public function testSearchAttribute() {
    $as = new AttributedString("fóò bar bäz");
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

  public function testToHtml() {
    $as = new AttributedString("fóò bar bäz");
    $as->setLength(4, 3, "bold");

    $this->assertEquals("fóò <span class=\"bold\">bar</span> bäz", $as->toHtml());
    $this->assertEquals("fóò <div class=\"bold\">bar</div> bäz", $as->toHtml("div"));
    $this->assertEquals("fóò <span class=\"prefix-bold\">bar</span> bäz", $as->toHtml("span", "prefix-"));

    // Create overlapping attribute spans
    $as = new AttributedString("fóò bar bäz");
    $as->setLength(0, 7, "a");
    $as->setLength(4, 7, "b");
    $this->assertEquals('<t class="a">fóò <t class="b">bar</t></t><t class="b"> bäz</t>', $as->toHtml("t"));

    // Create span inside span
    $as = new AttributedString("fóò bar bäz");
    $as->setLength(0, 11, "a");
    $as->setLength(4, 3, "b");
    $this->assertEquals('<t class="a">fóò <t class="b">bar</t> bäz</t>', $as->toHtml("t"));

  }

  public function testCombineAttributes() {
    $as = new AttributedString("fóò bar bäz");
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
    $this->expectException('InvalidArgumentException');
    $as->combineAttributes("non-op", "underlined", "bold", "non-op");
  }

  public function testAttributeToString() {
    $as = new AttributedString("fóò bar bäz");
    $as->setLength(4, 3, "bold");

    $this->assertEquals("    ---    ", $as->attributeToString("bold"));
    $this->assertEquals("OOOOXXXOOOO", $as->attributeToString("bold", "X", "O"));
  }

  public function testCount() {
    $as = new AttributedString("fóò bar bäz");

    $this->assertEquals(11, count($as));
  }

  public function testArrayAccess() {
    $as = new AttributedString("fóò bar bäz");

    $this->assertEquals("f", $as[0]);
    $this->assertEquals("ò", $as[2]);
    $this->expectException('RuntimeException');
    $as[0] = "z";
  }
}
