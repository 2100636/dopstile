<?php

class logsFrontendRequestPasswordAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign($this->params);
    }
}
