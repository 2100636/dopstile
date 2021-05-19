<?php


class menuBackendLayout extends waLayout
{

    public function execute()
    {
        $mim = new menuItemModel();

        $backend_menu_sorting = wa('menu')->getConfig()->getOption('backend_menu_sorting');


        $menus = $mim->where('parent_id = 0')->order($backend_menu_sorting)->fetchAll();
        $this->view->assign('menus', $menus);
    }

}