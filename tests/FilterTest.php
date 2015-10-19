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

    public function testAppendEndEventCanBeBufferedOnClose()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk = null) {
            if ($chunk === null) {
                // this signals the end event
                return '!';
            }
            return $chunk . ' ';
        }, STREAM_FILTER_WRITE);

        $buffered = '';
        StreamFilter\append($stream, function ($chunk) use (&$buffered) {
            $buffered .= $chunk;
            return '';
        });

        fwrite($stream, 'hello');
        fwrite($stream, 'world');

        fclose($stream);

        $this->assertEquals('hello world !', $buffered);
    }

    public function testAppendEndEventWillBeCalledOnRemove()
    {
        $stream = $this->createStream();

        $ended = false;
        $filter = StreamFilter\append($stream, function ($chunk = null) use (&$ended) {
            if ($chunk === null) {
                $ended = true;
            }
            return $chunk;
        }, STREAM_FILTER_WRITE);

        $this->assertEquals(0, $ended);
        StreamFilter\remove($filter);
        $this->assertEquals(1, $ended);
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

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage test
     */
    public function testAppendThrows()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk) {
            throw new \DomainException($chunk);
        });

        fwrite($stream, 'test');
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage end
     */
    public function testAppendThrowsDuringEnd()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk = null) {
            if ($chunk === null) {
                throw new \DomainException('end');
            }
            return $chunk;
        });

        fclose($stream);
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage test
     */
    public function testAppendThrowsShouldTriggerEnd()
    {
        $stream = $this->createStream();

        $ended = false;
        StreamFilter\append($stream, function ($chunk = null) use (&$ended) {
            if ($chunk === null) {
                $ended = true;
                return '';
            }
            throw new \DomainException($chunk);
        });

        try {
            $this->assertEquals(false, $ended);
            fwrite($stream, 'test');
        } catch (DomainException $e) {
            $this->assertEquals(true, $ended);
            throw $e;
        }
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage test
     */
    public function testAppendThrowsShouldTriggerEndButIgnoreExceptionDuringEnd()
    {
        $stream = $this->createStream();

        StreamFilter\append($stream, function ($chunk = null) {
            if ($chunk === null) {
                $chunk = 'end';
            }
            throw new \DomainException($chunk);
        });

        fwrite($stream, 'test');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAppendInvalidStreamIsRuntimeError()
    {
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
        StreamFilter\append(false, function () { });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testPrependInvalidStreamIsRuntimeError()
    {
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
        StreamFilter\prepend(false, function () { });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRemoveInvalidFilterIsRuntimeError()
    {
        if (defined('HHVM_VERSION')) $this->markTestSkipped('Not supported on HHVM');
        StreamFilter\remove(false);
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
