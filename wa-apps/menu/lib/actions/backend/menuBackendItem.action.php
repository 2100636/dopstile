<?php


class menuBackendItemAction extends menuBackendAction
{
    public function execute()
    {
        $mi = new menuItemModel();
        if($id = waRequest::get('id')){
            $item = $mi->getItem($id);
        } elseif($parent_id = waRequest::get('parent_id')){
            $item = $mi->getNewItem($parent_id);
            $this->view->assign('parent_id', $parent_id);
        } else {
            throw new waException(_w('Item not found'));
        }

        $types = wa('menu')->getConfig()->getRegisteredControls();

        $this->view->assign('item', $item);
        $this->view->assign('types', $types);
    }

}