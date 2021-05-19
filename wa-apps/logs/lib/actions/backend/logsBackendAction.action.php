<?php

class logsBackendActionAction extends logsBackendItemAction
{
    public function __construct()
    {
        $this->action = 'action';
        $this->id = 'id';
        parent::__construct();
    }

    protected function check()
    {
        return logsItemAction::check($this->value);
    }

    protected function getItem($params)
    {
        $item = new logsItemAction($this->value);
        return $item->get($params);
    }
}
