<?php


class menuItem implements ArrayAccess
{
    protected $params;
    protected $data;

    /**
     * Фильтруем дополнительные параметры, чтобы лишнее не кэшировать.
     *
     * @var array
     */
    protected $_allowed_keys = array(
        'id', 'name', 'url', 'childs', 'target'
    );

    /**
     * @var menuItem[]
     */
    protected $children = array();

    /**
     * @param $data
     * @param $params
     * @return menuItem
     */
    public static function factory($data, $params)
    {
        $type = ifempty($data['type'], '');
        if(!$class = wa('menu')->getConfig()->getControlClass($type)) {
            $class = 'menuItem';
        }

        return new $class($data, $params);

    }

    public function __construct($data, $params)
    {
        $this->data = (array) $data;
        $this->params = (array) $params;
    }

    public function offsetExists($offset)
    {
        $method = 'get'.ucfirst($offset);

        return method_exists($this, $method) || isset($this->data[$offset]) || isset($this->params[$offset]);
    }

    public function offsetGet($offset)
    {
        $parts = explode('_', $offset);
        $parts = array_map('ucfirst', $parts);
        $method = 'get'.implode('', $parts);
        if(method_exists($this, $method)) {
            return $this->$method();
        }
        if(isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        if(isset($this->params[$offset])) {
            return $this->params[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if(array_key_exists($offset, $this->params)) {
            unset($this->params[$offset]);
        }
    }

    public function getBackendIcon()
    {
        return 'folder';
    }

    public function save($data = null, $params = null)
    {
        if(is_array($data)) {
            $this->data = $data;

        }
        if($params !== null) {
            $this->params = $params;
        }
        $this->saveData();
        $this->saveParams();
    }

    protected function saveData()
    {
        $mi = new menuItemModel();
        $mi->updateById(ifempty($this->data['id']), array(
            'name' => ifempty($this->data['name']),
            'type' => ifempty($this->data['type']),
            'status' => ifempty($this->data['status']),
        ));
    }

    public function renderParamControls()
    {
        $view = wa()->getView();

        $view->assign('params', $this->params);

        $control_settings = array(
            'id' => 'menu_item',
            'namespace' => 'params',
            'title_wrapper' => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper' => '<div class="name">%s</div><div class="value">%s %s</div>'
        );

        $controls = array();


        $item_params = $this->getAvailableParams();

        /**
         * This hook adds a new params to menu item settings.
         *
         * @event item_params
         * @param array $data Item params.
         * @return array[string]array $plugin_params[%plugin_id%] array with plugin controls
         */
        $plugin_params = wa('menu')->event('item_params', $this->data);

        foreach ($plugin_params as $plugin => $plugin_controls) {
            if(!empty($plugin_controls) && is_array($plugin_controls)) {
                $item_params = array_merge($item_params, $plugin_controls);
            }
        }

        foreach ($item_params as $name => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!empty($row['subject']) && !in_array($row['subject'], (array)$control_settings['subject'])) {
                continue;
            }
            $row = array_merge($row, $control_settings);
            if(isset($this[$name])) {
                $row['value'] = $this[$name];
            }
            if (!empty($row['control_type'])) {
                $controls[] = sprintf('<div class="field">%s</div>',
                    waHtmlControl::getControl($row['control_type'], $name, $row));
            }
        }

        return implode('', $controls);
    }


    /*************************************
     * Методы для построения дерева
     *************************************/

    /**
     * Добавляет дочерний элемент в структуру дерева при выборке menuItemModel::getMenuItems
     *
     * @param $child
     */
    public function addChild(&$child)
    {
        $this->children[] = $child;
    }

    /**
     * Подсчёт количества дочерних элементов.
     * Используется в отрисовке интерфейса бекенда.
     *
     * @return int
     */
    public function getChildrenCount()
    {
        return count($this->children);
    }


    /**
     * Используется для генерации меню во фронтенде.
     * Переопределить, если нужно подменить какие-то параметры.
     *
     * @return array
     */
    public function toArray()
    {
        $data = array_merge($this->params, $this->data);
        $data['childs'] = array();
        foreach ($this->children as $child) {
            if($c = $child->toArray()) {
                $data['childs'][] = $c;
            }
        }

        $allowed_keys = $this->getAllowedKeys();
        $data = array_intersect_key($data, array_flip($allowed_keys));

        $char_set = 'UTF-8';
        $result = array();
        foreach ($allowed_keys as $allowed_key) {
            if(isset($data[$allowed_key])) {
                $result[$allowed_key] = is_string($data[$allowed_key]) ?
                    htmlspecialchars($data[$allowed_key], ENT_QUOTES, $char_set) : $data[$allowed_key];
            } else {
                $result[$allowed_key] = '';
            }
        }

        return $result;
    }


    /*************************************
     * END Методы для построения дерева
     *************************************/


    /**
     * @see menuItemLink for more details
     * @return array
     */
    protected function getAvailableParams()
    {
        return array();
    }

    protected function saveParams()
    {
        if(!empty($this->data['id'])) {
            $mip = new menuItemParamsModel();
            $mip->set($this->data['id'], $this->params);
        }
    }

    protected function getAllowedKeys()
    {
        static $plugin_keys;
        if($plugin_keys === null) {
            $plugin_keys = array();


            /**
             * This hook adds a new allowed params to show at frontend.
             *
             * @event item_params_allowed
             * @return array[string]array $plugins[%plugin_id%] array of allowed params keys
             */
            $plugins = wa('menu')->event('item_params_allowed');

            if($plugins) {
                foreach ($plugins as $plugin => $keys) {
                    if(!empty($keys) && is_array($keys)) {
                        $plugin_keys = array_merge($plugin_keys, $keys);
                    }
                }
            }
            $plugin_keys = array_unique($plugin_keys);
        }

        return array_merge($this->_allowed_keys, $plugin_keys);
    }

}