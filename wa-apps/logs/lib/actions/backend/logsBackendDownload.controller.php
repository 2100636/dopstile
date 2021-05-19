<?php

class logsBackendDownloadController extends waController
{
    public function execute()
    {
        $path = waRequest::get('path');
        $file_item = new logsItemFile($path);
        $file_item->download();
    }
}
