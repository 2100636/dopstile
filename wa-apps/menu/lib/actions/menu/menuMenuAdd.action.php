<?php


class menuMenuAddAction extends waViewAction
{

    public function execute()
    {

        if($id = waRequest::get('id')) {
            $mim = new menuItemModel();
            $menu = $mim->getById($id);
        }
        if(empty($menu)) {
            $menu = array(
                'id' => 'new',
                'name' => _w('New menu'),
                'type' => 'blog'
            );
        }

        $this->view->assign('menu', $menu);

    }
}