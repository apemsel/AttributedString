<?php

use apemsel\AttributedString\Bitmap;
use PHPUnit\Framework\TestCase;

class BitmapTest extends TestCase
{
    public function testArrayAccess()
    {
        $b = new Bitmap(10);
        $b[0] = true;
        $b[9] = true;
        $this->assertEquals(true, $b[0]);
        $this->assertEquals(true, $b[9]);

        $b[9] = false;
        $this->assertEquals(false, $b[9]);

        $this->expectException('RuntimeException');
        unset($b[0]);
    }

    public function testCountable()
    {
        $b = new Bitmap(10);
        $this->assertEquals(10, count($b));
    }
}
