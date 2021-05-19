<?php


class menuItemExpandAction extends waViewAction
{
    /**
     * Name prefix for item expand/collapse flag
     *
     * @var string
     */
    private static $prefix = 'expanded_menu_';


    public function execute()
    {
        $id = waRequest::get('id', 0, waRequest::TYPE_INT);

        $collapsed = waRequest::get('collapsed', 0, waRequest::TYPE_INT);
        if ($collapsed) {
            $this->setCollapsed($id, !!waRequest::get('recurse'));
            return;
        }

        $this->setExpanded($id, !!waRequest::get('recurse'));

        if (waRequest::get('tree')) {
            $mi = new menuItemModel();
            $items = $mi->getMenuItems($id, false);

            $this->view->assign('menu', $items['menu']);
            $this->view->assign('items', $items['items']);
        }
    }


    public static function setExpanded($item_id, $recurse=false)
    {
        $map = self::getItemsExpandedStatesMap();
        if (isset($map[0]) && count($map) == 1) {
            self::getSettingsModel()->set(
                wa('menu')->getUser()->getId(),
                'menu',
                self::$prefix . '0', // @todo вместо "0", наверное, ID меню
                1
            );
        }

        $item_id = (array) $item_id;
        if ($recurse) {
            $ids = array_fill_keys($item_id, 1);
            foreach ($item_id as $id) {
                $mi = new menuItemModel();
                $items = $mi->getTree($id, false);
                foreach($items as $item) {
                    $ids[$item['id']] = 1;
                }
            }
            $item_id = array_keys($ids);
        }

        foreach($item_id as $c_id) {
            self::getSettingsModel()->set(
                wa('menu')->getUser()->getId(),
                'menu',
                self::$prefix . (int)$c_id,
                1
            );
        }
    }


    protected function setCollapsed($item_id = null, $recurse=false)
    {
        $contact_id = wa('menu')->getUser()->getId();

        if ($item_id === null) {
            $item_id = array_keys(self::getItemsExpandedStatesMap());
        } else {
            $item_id = (array) $item_id;
            if ($recurse) {
                $ids = array_fill_keys($item_id, 1);
                foreach ($item_id as $id) {

                    $mi = new menuItemModel();
                    $items = $mi->getTree($id, false);
                    foreach($items as $c) {
                        $ids[$c['id']] = 1;
                    }
                }
                $item_id = array_keys($ids);
            }
        }

        foreach ($item_id as $id) {
            self::getSettingsModel()->delete(
                $contact_id,
                'menu',
                self::$prefix . (int) $id
            );
        }
    }


    public static function getItemsExpandedStatesMap()
    {
        $settings_model = self::getSettingsModel();
        $contact_id = wa('menu')->getUser()->getId();

        $settings = array();
        foreach ($settings_model->get($contact_id, 'menu') as $name => $item) {
            $k = strpos($name, self::$prefix);
            if ($k === false) {
                continue;
            }
            $item_id = str_replace(self::$prefix, '', $name);
            $settings[$item_id] = true;
        }

        return $settings;
    }


    /**
     * @return waContactSettingsModel
     */
    private static function getSettingsModel()
    {
        static $settings_model;
        if ($settings_model === null) {
            $settings_model = new waContactSettingsModel();
        }
        return $settings_model;
    }


}