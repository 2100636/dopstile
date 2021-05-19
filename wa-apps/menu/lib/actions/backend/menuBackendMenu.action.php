<?php


class menuBackendMenuAction extends menuBackendAction
{

    public function execute()
    {
        $id = waRequest::get('id');
        $mi = new menuItemModel();
        $menu = $mi->getMenuItems($id, false);
        if(empty($menu['menu'])) {
            throw new waException(_w('Menu not found'));
        }

        $this->view->assign($menu);
    }

}