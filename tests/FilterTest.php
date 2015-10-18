<?php

use Clue\StreamFilter;

class FilterTest extends PHPUnit_Framework_TestCase
{
    public function testAppendSimpleCallback()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return strtoupper($chunk);
        });

        fwrite($stream, 'hello');
        fwrite($stream, 'world');
        rewind($stream);

        $this->assertEquals('HELLOWORLD', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendNativePhpFunction()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, 'strtoupper');

        fwrite($stream, 'hello');
        fwrite($stream, 'world');
        rewind($stream);

        $this->assertEquals('HELLOWORLD', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendChangingChunkSize()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return str_replace(array('a','e','i','o','u'), '', $chunk);
        });

        fwrite($stream, 'hello');
        fwrite($stream, 'world');
        rewind($stream);

        $this->assertEquals('hllwrld', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendReturningEmptyStringWillNotPassThrough()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return '';
        });

        fwrite($stream, 'hello');
        fwrite($stream, 'world');
        rewind($stream);

        $this->assertEquals('', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendWriteOnly()
    {
        $stream = $this->createStream();

        $invoked = 0;

        StreamFilter\append($stream, function ($chunk) use (&$invoked) {
            ++$invoked;

            return $chunk;
        }, STREAM_FILTER_WRITE);

        fwrite($stream, 'a');
        fwrite($stream, 'b');
        fwrite($stream, 'c');
        rewind($stream);

        $this->assertEquals(3, $invoked);
        $this->assertEquals('abc', stream_get_contents($stream));

        fclose($stream);
    }

    public function testAppendReadOnly()
    {
        $stream = $this->createStream();

        $invoked = 0;

        StreamFilter\append($stream, function ($chunk) use (&$invoked) {
            ++$invoked;

            return $chunk;
        }, STREAM_FILTER_READ);

        fwrite($stream, 'a');
        fwrite($stream, 'b');
        fwrite($stream, 'c');
        rewind($stream);

        $this->assertEquals(0, $invoked);
        $this->assertEquals('abc', stream_get_contents($stream));
        $this->assertEquals(1, $invoked);

        fclose($stream);
    }

    public function testOrderCallingAppendAfterPrepend()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            return '[' . $chunk . ']';
        }, STREAM_FILTER_WRITE);

        StreamFilter\prepend($stream, function ($chunk) {
            return '(' . $chunk . ')';
        }, STREAM_FILTER_WRITE);

        fwrite($stream, 'hello');
        rewind($stream);

        $this->assertEquals('[(hello)]', stream_get_contents($stream));

        fclose($stream);
    }

    public function testRemoveFilter()
    {
        $stream = $this->createStream();

        $first = StreamFilter\append($stream, function ($chunk) {
            return $chunk . '?';
        }, STREAM_FILTER_WRITE);

        StreamFilter\append($stream, function ($chunk) {
            return $chunk . '!';
        }, STREAM_FILTER_WRITE);

        StreamFilter\remove($first);

        fwrite($stream, 'hello');
        rewind($stream);

        $this->assertEquals('hello!', stream_get_contents($stream));

        fclose($stream);
    }

    public function testBuiltInRot13()
    {
        $rot = StreamFilter\builtin('string.rot13');

        $this->assertEquals('grfg', $rot('test'));
        $this->assertEquals('test', $rot($rot('test')));
    }

    public function testAppendBuiltInDechunk()
    {
        try {
            $dechunk = StreamFilter\builtin('dechunk');
        } catch (Exception $e) {
            if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
            throw $e;
        }

        $stream = $this->createStream();

        StreamFilter\append($stream, $dechunk, STREAM_FILTER_WRITE);

        fwrite($stream, "2\r\nhe\r\n");
        fwrite($stream, "3\r\nllo\r\n");
        fwrite($stream, "0\r\n\r\n");
        rewind($stream);

        $this->assertEquals('hello', stream_get_contents($stream));

        fclose($stream);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBuildInInvalid()
    {
        StreamFilter\builtin('unknown');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAppendInvalidStreamIsRuntimeError()
    {
        StreamFilter\append(false, function () { });
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testPrependInvalidStreamIsRuntimeError()
    {
        StreamFilter\prepend(false, function () { });
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRemoveInvalidFilterIsRuntimeError()
    {
        StreamFilter\remove(false);
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCallbackIsInvalidArgument()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, 'a-b-c');
    }

    private function createStream()
    {
        return fopen('php://memory', 'r+');
    }
}
