<?php

class logsFrontendPhpinfoController extends logsFrontendPublishedItemController
{
    public function __construct()
    {
        $this->hash = waRequest::param('hash');
        $this->path = '//phpinfo//';
        $this->action = 'phpinfo';
        $this->not_published_warning = _w('PHP configuration page is not published.');
    }

    protected function check()
    {
        parent::check();

        if (!function_exists('phpinfo')) {
            throw new Exception(_w('Function phpinfo() is not available on your server.'));
        }
    }

    protected function getData()
    {
        phpinfo();
    }
}
