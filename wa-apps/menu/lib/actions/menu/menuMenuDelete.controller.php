<?php


class menuMenuDeleteController extends waJsonController
{
    public function execute()
    {
        if($id = waRequest::get('id')) {
            $mim = new menuItemModel();
            $mim->deleteBranch($id);

            /**
             * @var menuConfig $config
             */
            $config = wa('menu')->getConfig();
            $config->clearMenuCache($id);
        } else {
            $this->setError(_w('Empty request.'));
        }
    }

}