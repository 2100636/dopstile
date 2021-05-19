<?php

/**
 * Class shopAdvancedparamsParamValueModel
 */
class shopAdvancedparamsParamValueModel extends waModel
{
    /**
     * @var string
     */
    protected $table = 'shop_advancedparams_param_value';
    /**
     * Тип экшена
     * @var null|string
     */
    protected $action = '';

    /**
     * shopAdvancedparamsParamValueModel constructor.
     * @param null|string $action
     * @param null $type
     * @param bool $writable
     */
    public function __construct($action, $type = null, $writable = false)
    {
        $this->action = $action;
        parent::__construct($type, $writable);
    }

    /**
     * Возвращает исходное значение доп. параметра по ID экшена и имени доп. параметра
     * @param $action_id
     * @param $name
     * @return array|null
     */
    public function getValue($action_id, $name) {
        $value = $this->getByField(array('action' => $this->action, 'name'=> $name, 'action_id' => $action_id));
        if(!empty($value)) {
            return $value;
        }
        return null;
    }

    /**
     * Удаляет все исходные значния доп.параметров по имени доп. параметра
     * @param $name
     */
    public function deleteValuesByName($name) {
       $this->deleteByField(array('name'=> $name, 'action' => $this->action));
    }

    /**
     * Удаляет все исходные значения доп. параметров по ID экшена
     * @param $action_id
     */
    public function deleteByActionId($action_id) {
        $this->deleteByField(array('action'=> $this->action, 'action_id'=> $action_id));
    }

    /**
     * Сохраняет исходные значения доп. параметров по идентификатору экшена
     * @param $action_id
     * @param array $params
     * @return bool
     */
    public function set($action_id, $params = array()) {
        if($action_id) {
            if (is_null($params)) {
                return $this->deleteByField(array(
                    'action' => $this->action,
                    'action_id' => $action_id
                ));
            }
            $delete_params = $this->get($action_id);
            $add_params = array();
            foreach ($params as $name => $value) {
                if (isset($delete_params[$name])) {
                    $this->updateByField(array(
                        'action' => $this->action,
                        'action_id' => $action_id,
                        'name' => $name
                    ),
                        array('value' => $value)
                    );
                    unset($delete_params[$name]);
                } else {
                    $add_params[] = array(
                        'action' => $this->action,
                        'action_id' => $action_id,
                        'name' => $name,
                        'value' => $value
                    );
                }
            }
            // Не переданные параметры не удаляем, чтобы данные сохранились для активации
            // Удалять их будем при удалении экшена
            /*foreach ($delete_params as $name => $value) {
                $this->deleteByField(array(
                    'action' => $this->action,
                    'action_id' => $action_id,
                    'name' => $name
                ));
            }*/
            if ($add_params) {
                $this->multipleInsert($add_params);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает все исходные значения доп. параметров по id экшена
     * @param $action_id
     * @return array
     */
    public function get($action_id) {
        $params = array();
        foreach ($this->getByField(array('action' => $this->action, 'action_id' => $action_id), true) as $p) {
            $params[$p['name']] = $p['value'];
        }
        return $params;
    }
}