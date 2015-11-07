<?php

use Clue\StreamFilter;

class BuiltInZlibTest extends PHPUnit_Framework_TestCase
{
    public function testFunZlibDeflateEmpty()
    {
        $deflate = StreamFilter\fun('zlib.deflate');

        //$data = gzdeflate('');
        $data = $deflate();

        $this->assertEquals("\x03\x00", $data);
    }

    public function testFunZlibDeflateBig()
    {
        $deflate = StreamFilter\fun('zlib.deflate');

        $n = 1000;
        $expected = str_repeat('hello', $n);

        $bytes = '';
        for ($i = 0; $i < $n; ++$i) {
            $bytes .= $deflate('hello');
        }
        $bytes .= $deflate();

        $this->assertEquals($expected, gzinflate($bytes));
    }

    public function testFunZlibInflateBig()
    {
        $inflate = StreamFilter\fun('zlib.inflate');

        $expected = str_repeat('hello', 10);
        $bytes = gzdeflate($expected);

        $ret = '';
        foreach (str_split($bytes, 2) as $chunk) {
            $ret .= $inflate($chunk);
        }
        $ret .= $inflate();

        $this->assertEquals($expected, $ret);
    }
}
