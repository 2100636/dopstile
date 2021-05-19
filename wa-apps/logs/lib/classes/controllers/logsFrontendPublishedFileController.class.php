<?php

abstract class logsFrontendPublishedFileController extends logsFrontendPublishedItemController
{
    public function __construct()
    {
        $this->hash = waRequest::param('hash');
        $this->path = waRequest::param('path');
        $this->action = 'file';
        $this->not_published_warning = sprintf(_w('File %s is not published.'), $this->path);
    }

    protected function check()
    {
        parent::check();

        if (!strlen($this->path)) {
            throw new Exception();
        }

        $full_path = logsHelper::getFullPath($this->path);

        if (!is_readable($full_path)) {
            throw new Exception(sprintf(_w('File %s does not exist or is not accessible.'), $this->path));
        }
    }
}
