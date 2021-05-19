<?php

class logsStreamFilterHideData extends php_user_filter
{
    public function onCreate()
    {
        $this->data = '';
        return true;
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $this->data .= $bucket->data;
            $this->bucket = $bucket;
            $consumed = 0;
        }

        if ($closing) {
            $consumed += strlen($this->data);
            $this->data = logsHelper::hideData($this->data);
            $this->bucket->data = $this->data;
            $this->bucket->datalen = strlen($this->data);
            stream_bucket_append($out, $this->bucket);

            return PSFS_PASS_ON;
        } else {
            return PSFS_FEED_ME;
        }
    }
}
