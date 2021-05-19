<?php

class logsFrontendFileDownloadController extends logsFrontendPublishedFileController
{
    protected function getData()
    {
        $file_item = new logsItemFile($this->path);
        $file_item->download();
    }
}
