<?php

class menuConfig extends waAppConfig
{

    protected static $registered_controls;


    protected function registerControls()
    {
        self::$registered_controls = array(
            'link' => array(
                'name' => _w('Simple link'), 
                'class' => 'menuItemLink'
            ),
        );

        /**
         * This hook adds a new menu types.
         *
         * @event register_controls
         * @param array $data Item params.
         * @return array[string]array $plugin_params[%plugin_id%]['name'] control name $plugin_params[%plugin_id%]['class'] control class name
         */
        $plugin_controls = wa('menu')->event('register_controls');

        foreach ($plugin_controls as $plugin => $controls) {
            self::$registered_controls = array_merge(self::$registered_controls, $controls);
        }
    }

    public function getRegisteredControls()
    {
        if(self::$registered_controls === null) {
            $this->registerControls();
        }

        return self::$registered_controls;
    }

    public function getControlClass($type)
    {
        $controls = $this->getRegisteredControls();

        return isset($controls[$type]) ? $controls[$type]['class'] : false;
    }

    public function getCache($type = 'default')
    {
        if($cache = parent::getCache($type)) {
            return $cache;
        } else {
            $cache_adapter = new waFileCacheAdapter(array());
            $this->cache = new waCache($cache_adapter, $this->application);
        }
        return $this->cache;
    }

    public function clearMenuCache($menu_id)
    {
        if($cache = $this->getCache()) {
            $group = 'data/'.$menu_id;
            $cache->deleteGroup($group);
        }
    }

}