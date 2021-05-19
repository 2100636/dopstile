<?php


class menuMenuPreviewAction extends waViewAction
{

    public function execute()
    {
        if($id = waRequest::get('id')) {
            $mim = new menuItemModel();
            $menu = $mim->getById($id);
            $this->view->assign('menu_id', $menu['id']);
        }
    }
}