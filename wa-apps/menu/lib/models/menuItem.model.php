<?php


class menuItemModel extends waNestedSetModel
{

    protected $table = 'menu_item';

    protected $left = 'left_key';
    protected $right = 'right_key';
    protected $parent = 'parent_id';

    public function getMenuItems($parent_id, $enabled = true)
    {
        $where = $enabled ? array('status=1') : array();
        $items = $this->getTree($parent_id, null, $where);

        if(empty($items[$parent_id])) {
            return array(
                'menu' => array(),
                'items' => array()
            );
        }

        $menu = $items[$parent_id];
        unset($items[$parent_id]);

        while($menu['parent_id']) {
            $menu = $this->getById($menu['parent_id']);
        }

        $res = array();
        $params_model = new menuItemParamsModel();
        $params = $params_model->get(array_keys($items));
        foreach ($items as $item) {
            if(($item['parent_id'] == $parent_id) || !empty($res[$item['parent_id']])) {
                $res[$item['id']] = menuItem::factory($item, $params[$item['id']]);

                if(!empty($res[$item['parent_id']])) {
                    $res[$item['parent_id']]->addChild($res[$item['id']]);
                }
            }
        }

        if($enabled === false) {

            $expand = menuItemExpandAction::getItemsExpandedStatesMap();
            $unset = array();

            foreach ($res as $r) {
                if($r['parent_id'] !== $parent_id) {
                    if (empty($expand[$r['parent_id']])
                        || !empty($unset[$r['parent_id']]))
                    {
                        $unset[$r['id']] = true;
                    }
                }
            }

            foreach ($res as &$r) {
                $r['expanded'] = !empty($expand[$r['id']]);
                if (!empty($unset[$r['id']])) {
                    unset($res[$r['id']]);
                }
            }
        }

        return array(
            'menu' => $menu,
            'items' => $res
        );
    }

    /**
     * @param $id
     * @param string $force_type
     * @return menuItem
     * @throws waException
     */
    public function getItem($id, $force_type = null)
    {
        if($id === 'new') {
            $data = array(
                'id' => 'new',
                'type' => $force_type ? $force_type : 'link',
                'status' => 1,
            );
            return menuItem::factory($data, array());
        }

        if(!$data = $this->getById($id)) {
            throw new waException(_w('Item not found'));
        }

        if($force_type) {
            $data['type'] = $force_type;
        }

        $mip = new menuItemParamsModel();
        $params = $mip->get($id);

        return menuItem::factory($data, $params);
    }

    public function getNewItem($parent_id)
    {
        $data = array(
            'id' => 'new',
            'type' => 'link',
            'status' => 1,
            'parent_id' => $parent_id
        );

        return menuItem::factory($data, array());
    }

    /**
     * @param $data
     * @param $params
     * @return menuItem
     */
    public function addItem($data, $params)
    {
        unset($data['id']);
        $data['id'] = $this->add($data);
        $item = menuItem::factory($data, $params);
        $item->save();
        return $item;
    }

    public function deleteBranch($id)
    {
        if($data = $this->select('left_key,right_key')->where('id=?', $id)->fetch()) {
            $ids = $this->query('SELECT id FROM '.$this->getTableName().
                ' WHERE left_key >= ? AND right_key <= ?', $data['left_key'], $data['right_key'])->fetchAll(null, true);


            $pm = new menuItemParamsModel();
            $pm->deleteByField('item_id', $ids);


            $this->deleteById($ids);

            $this->repair();
        }
    }

    public function getMenuByChild($id)
    {
        if($item = $this->getById($id)) {

            while ($item['parent_id']) {
                $item = $this->getById($item['parent_id']);
            }
            return $item['id'];
        }

        return false;
    }

}