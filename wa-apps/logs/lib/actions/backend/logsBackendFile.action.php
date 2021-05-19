<?php

class logsBackendFileAction extends logsBackendItemAction
{
    public function __construct()
    {
        $this->action = 'file';
        $this->id = 'path';
        parent::__construct();
    }

    protected function check()
    {
        return logsItemFile::check($this->value);
    }

    protected function getItem($params)
    {
        $item = new logsItemFile($this->value);
        return $item->get($params);
    }
}
