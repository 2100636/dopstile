<?php


class menuMenuSaveController extends waJsonController
{
    public function execute()
    {
        if($data = waRequest::post('menu')) {
            $mim = new menuItemModel();
            if (ifempty($data['id']) === 'new') {
                unset($data['id']);
                $id = $mim->add($data);
            } else {
                $mim->updateById($data['id'], $data);
                $id = $data['id'];
            }

            /**
             * @var menuConfig $config
             */
            $config = wa('menu')->getConfig();
            $config->clearMenuCache($id);
            $this->response = $mim->getById($id);
        } else {
            $this->setError(_w('Empty request.'));
        }
    }

}