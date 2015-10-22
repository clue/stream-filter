<?php

namespace Clue\StreamFilter;

use php_user_filter;
use InvalidArgumentException;
use Exception;

/**
 *
 * @internal
 * @see append()
 * @see prepend()
 */
class CallbackFilter extends php_user_filter
{
    private $callback;
    private $closed = true;

    public function onCreate()
    {
        $this->closed = false;

        if (!is_callable($this->params)) {
            throw new InvalidArgumentException('No valid callback parameter given to stream_filter_(append|prepend)');
        }
        $this->callback = $this->params;

        return true;
    }

    public function onClose()
    {
        $this->closed = true;
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

        // skip processing callback that already ended
        if ($this->closed) {
            return PSFS_FEED_ME;
        }

        // only invoke filter function if buffer is not empty
        // this may skip flushing a closing filter
        if ($data !== '') {
            try {
                $data = call_user_func($this->callback, $data);
            } catch (Exception $e) {
                // exception should mark filter as closed
                $this->closed = true;
                trigger_error('Error invoking filter: ' . $e->getMessage(), E_USER_WARNING);
                return PSFS_ERR_FATAL;
            }
        }

        // mark filter as closed after processing closing chunk
        if ($closing) {
            $this->closed = true;
        }

        if ($data !== '') {
            // create a new bucket for writing the resulting buffer to the output brigade
            // reusing an existing bucket turned out to be bugged in some environments (ancient PHP versions and HHVM)
            stream_bucket_append($out, stream_bucket_new($this->stream, $data));
        }

        return PSFS_PASS_ON;
    }
}
