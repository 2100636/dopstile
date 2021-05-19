<?php

class logsBackendDirectoryAction extends logsViewAction
{
    public function execute()
    {
        $path = waRequest::get('path');
        $item_list = new logsItems(logsItems::MODE_DIRECTORY);
        $items = $item_list->get(array('path' => $path));
        $this->view->assign('items', $items);
    }
}
