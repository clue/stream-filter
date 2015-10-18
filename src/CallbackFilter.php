<?php

namespace Clue\StreamFilter;

use php_user_filter;
use InvalidArgumentException;

/**
 *
 * @internal
 * @see append()
 * @see prepend()
 */
class CallbackFilter extends php_user_filter
{
    private $callback;

    public function onCreate()
    {
        if (!is_callable($this->params)) {
            throw new InvalidArgumentException('No valid callback parameter given to stream_filter_(append|prepend)');
        }
        $this->callback = $this->params;
    }

    public function onClose()
    {
        $this->callback = null;
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;

            $bucket->data = call_user_func($this->callback, $bucket->data, $closing);

            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
