<?php


class menuViewHelper extends waAppViewHelper
{

    public function get($menu_id)
    {
        $routing = wa()->getRouting();
        if(($route = $routing->getRoute()) && !empty($route['url'])) {
            $url = trim($route['url'], '/*');
        } else {
            $url = '';
        }
        $key = $routing->getDomain().'/'.$url;
        $key = trim($key,'/');
        $group = 'data/'.$menu_id;
        $lifetime = wa('menu')->getConfig()->getOption('cache_lifetime');

        if(($cache = wa('menu')->getCache()) && ($lifetime > 0)) {
            if($data = $cache->get($key, $group)) {
                return $data;
            }
        }

        $mim = new menuItemModel();
        $menu = $mim->getMenuItems($menu_id);

        $items = array();
        foreach ($menu['items'] as $item) {
            if(($item['depth'] == '1') && ($arr = $item->toArray())) {
                $items[] = $arr;
            }
        }

        /**
         * @event frontend_menu
         * @param array $items Menu items array before caching.
         * @return void
         */
        wa('menu')->event('frontend_menu', $items);

        if(($lifetime > 0) && $cache) {
            $cache->set($key, $items, $lifetime, $group);
        }

        return $items;
    }

};