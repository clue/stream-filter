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
