<?php

use Clue\StreamFilter as Filter;

class FunTest extends PHPUnit_Framework_TestCase
{
    public function testFunInRot13()
    {
        $rot = Filter\fun('string.rot13');

        $this->assertEquals('grfg', $rot('test'));
        $this->assertEquals('test', $rot($rot('test')));
        $this->assertEquals(null, $rot());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFunWriteAfterCloseRot13()
    {
        $rot = Filter\fun('string.rot13');

        $this->assertEquals(null, $rot());
        $rot('test');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFunInvalid()
    {
        Filter\fun('unknown');
    }

    public function testFunInBase64()
    {
        $encode = Filter\fun('convert.base64-encode', []);
        $decode = Filter\fun('convert.base64-decode', []);

        $string = 'test';
        $this->assertEquals(base64_encode($string), $encode($string));
        $this->assertEquals($string, $decode(base64_encode($string)));
        $this->assertEquals($string, $decode($encode($string)));
        $this->assertEquals(null, $encode());
    }
}
