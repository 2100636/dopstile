<?php


class menuBackendInfoAction extends waViewAction
{

    public function execute()
    {
        $mim = new menuItemModel();
        $menus = $mim->getByField('parent_id', 0, 1);
        $this->view->assign('menus', $menus);
    }
}