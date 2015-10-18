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
        // concatenate whole buffer from input brigade
        $data = '';
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            $data .= $bucket->data;
        }

        // only invoke filter function if buffer is not empty
        // this may skip flushing a closing filter
        if ($data !== '') {
            $data = call_user_func($this->callback, $data);

            // create a new bucket for writing the resulting buffer to the output brigade
            // reusing an existing bucket turned out to be bugged in some environments (ancient PHP versions and HHVM)
            stream_bucket_append($out, stream_bucket_new($this->stream, $data));
        }

        return PSFS_PASS_ON;
    }
}
