<?php

namespace Clue\StreamFilter;

use RuntimeException;

/**
 * append a callback filter to the given stream
 *
 * @param resource $stream
 * @param callable $callback
 * @param int $read_write
 * @return resource filter resource which can be used for `remove()`
 * @throws Exception on error
 * @uses stream_filter_append()
 */
function append($stream, $callback, $read_write = STREAM_FILTER_ALL)
{
    $ret = @stream_filter_append($stream, register(), $read_write, $callback);

    if ($ret === false) {
        $error = error_get_last() + array('message' => '');
        throw new RuntimeException('Unable to append filter: ' . $error['message']);
    }

    return $ret;
}

/**
 * prepend a callback filter to the given stream
 *
 * @param resource $stream
 * @param callable $callback
 * @param int $read_write
 * @return resource filter resource which can be used for `remove()`
 * @throws Exception on error
 * @uses stream_filter_prepend()
 */
function prepend($stream, $callback, $read_write = STREAM_FILTER_ALL)
{
    $ret = @stream_filter_prepend($stream, register(), $read_write, $callback);

    if ($ret === false) {
        $error = error_get_last() + array('message' => '');
        throw new RuntimeException('Unable to prepend filter: ' . $error['message']);
    }

    return $ret;
}

/**
 * Creates a filter callback which uses the given built-in $filter
 *
 * @param string $filter built-in filter name, see stream_get_filters()
 * @param mixed  $params additional parameters to pass to the built-in filter
 * @return callable a filter callback which can be append()'ed or prepend()'ed
 * @throws RuntimeException on error
 * @see stream_get_filters()
 * @see append()
 */
function builtin($filter, $params = null)
{
    $fp = fopen('php://memory', 'r+');
    $ret = @stream_filter_append($fp, $filter, STREAM_FILTER_WRITE, $params);

    if ($ret === false) {
        fclose($fp);
        $error = error_get_last() + array('message' => '');
        throw new RuntimeException('Unable to access built-in filter: ' . $error['message']);
    }

    $buffer = '';
    append($fp, function ($chunk) use (&$buffer) {
        $buffer .= $chunk;
    }, STREAM_FILTER_WRITE);

    return function ($chunk) use ($fp, &$buffer) {
        $buffer = '';

        fwrite($fp, $chunk);

        return $buffer;
    };
}

/**
 * remove a callback filter from the given stream
 *
 * @param resource $filter
 * @return boolean true on success or false on error
 * @throws Exception on error
 * @uses stream_filter_remove()
 */
function remove($filter)
{
    if (@stream_filter_remove($filter) === false) {
        throw new RuntimeException('Unable to remove given filter');
    }
}

/**
 * registers the callback filter and returns the resulting filter name
 *
 * There should be little reason to call this function manually.
 *
 * @return string filter name
 * @uses CallbackFilter
 */
function register()
{
    static $registered = null;
    if ($registered === null) {
        $registered = 'stream-callback';
        stream_filter_register($registered, __NAMESPACE__ . '\CallbackFilter');
    }
    return $registered;
}
