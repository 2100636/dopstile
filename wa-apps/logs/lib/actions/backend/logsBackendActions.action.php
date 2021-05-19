<?php

class logsBackendActionsAction extends logsViewAction
{
    public function execute()
    {
        $item_list = new logsItems(logsItems::MODE_ACTIONS);
        $this->view->assign('items', $item_list->get());
    }
}
