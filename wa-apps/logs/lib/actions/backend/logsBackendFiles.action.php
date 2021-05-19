<?php

class logsBackendFilesAction extends logsViewAction
{
    public function execute()
    {
        $mode = waRequest::get('mode');

        if (in_array($mode, logsItems::getItemListModes(), true)) {
            $item_list = new logsItems($mode);
            $items = $item_list->get();
            $this->view->assign('items', $items);
        } else {
            logsHelper::redirect();
        }
    }
}
