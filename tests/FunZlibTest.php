<?php

namespace Clue\Tests\StreamFilter;

use Clue\StreamFilter;
use PHPUnit\Framework\TestCase;

class FunZlibTest extends TestCase
{
    public function testFunZlibDeflateHelloWorld()
    {
        $deflate = StreamFilter\fun('zlib.deflate');

        $data = $deflate('hello') . $deflate(' ') . $deflate('world') . $deflate();

        $this->assertEquals(gzdeflate('hello world'), $data);
    }

    public function testFunZlibDeflateEmpty()
    {
        if (PHP_VERSION >= 7) $this->markTestSkipped('Not supported on PHP 7+ (empty string does not invoke filter)');

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

    public function testFunZlibInflateHelloWorld()
    {
        $inflate = StreamFilter\fun('zlib.inflate');

        $data = $inflate(gzdeflate('hello world')) . $inflate();

        $this->assertEquals('hello world', $data);
    }

    public function testFunGunzipHelloWorld()
    {
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM (no GZIP encoding supported)');

        $gunzip = StreamFilter\fun('zlib.inflate', array('window' => 15 | 16));

        $data = $gunzip(gzencode('hello world')) . $gunzip();

        $this->assertEquals('hello world', $data);
    }

    public function testFunZlibInflateEmpty()
    {
        $inflate = StreamFilter\fun('zlib.inflate');

        $data = $inflate("\x03\x00") . $inflate();

        $this->assertEquals('', $data);
    }

    public function testFunZlibInflateBig()
    {
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM (final chunk will not be emitted)');

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
