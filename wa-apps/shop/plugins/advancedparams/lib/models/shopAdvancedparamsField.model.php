<?php

/**
 * Class shopAdvancedparamsFieldModel
 */
class shopAdvancedparamsFieldModel extends waModel
{
    /**
     * @var string
     */
    protected $table = 'shop_advancedparams_field';
    protected static $new_fields = array();

    /**
     * Модель значений полей параметров
     * @var null|shopAdvancedparamsFieldValuesModel
     */
    protected $field_values_model = null;
    /*
     * Хранение полей для множественных запросов из фронтенда, для получения всех данных самих полей во фронтенде
     * shopAdvancedparamsPlugin::getProductParams()...
     * */
    protected static $registry_fields = array();

    /**
     * shopAdvancedparamsFieldModel constructor.
     * @param null $type
     * @param bool $writable
     */
    public function __construct($type = null, $writable = false)
    {
        $this->field_values_model = new shopAdvancedparamsFieldValuesModel();
        parent::__construct($type, $writable);
    }

    /**
     * Возвращает поля по типу экшена
     * @param string $action
     * @param string $all
     * @return array|null
     */
    public function getByAction($action = '', $all = 'name') {
        return $this->getByField('action', $action, $all);
    }
    public function getNewFields($action) {
        if(isset(self::$new_fields[$action])) {
            return self::$new_fields[$action];
        }
        return array();
    }
    /**
     * Возвращает все данные полей экшена и  их выбираемые значения
     * @param $action
     * @return array|null
     */
    public function getActionFields($action)
    {
        $params_model = new shopAdvancedparamsParamsModel($action);
        // Получаем имена реальных доп. параметров
        $action_params =  $params_model->getParamsNames();
        // Удаляем запрещенные имена
        foreach ($action_params as $k=>$b) {
            if(shopAdvancedparamsPlugin::isBannedField($action, $k)) {
                unset($action_params[$k]);
            }
        }
        // Удаляем уже существующие поля, чтобы не добавить повторно
        $types_params = $this->getByAction($action, 'name');
        foreach ($types_params as $k => $v) {
            if(array_key_exists($k, $action_params)) {
                unset($action_params[$k]);
            }
        }
        // Добавляем новые поля
        if(!empty($action_params)) {
            foreach ($action_params as $k => $v) {
                // Вместо сохранения отдадим в шаблон имена
                self::$new_fields[$action][$k] = true;
              /*  $data = array(
                    'name'=> $k,
                    'action' => $action,
                    'type' => 'input',
                    'title' => '',
                    'description' => ''
                );
                if(!$this->save($data, $errors)) {
                    // Если почему то не сохранилось пишем в лог
                    shopAdvancedparamsPlugin::log(implode(',', $errors));
                }*/
            }
        }
        // Получаем новый список полей и добавляем данные
        $fields = $this->getByAction($action, 'name');
        // Создаем ячейки для хранения полей
        if(!isset(self::$registry_fields['ids'])) {
            self::$registry_fields['ids'] = array();
        }
        if(!isset(self::$registry_fields[$action])) {
            self::$registry_fields[$action] = array();
        }
        foreach ($fields as $k => $v)
        {
            $fields[$k]['selectable'] = false;
            $fields[$k]['count_values'] = $params_model->countParamValues($k);
            if(shopAdvancedparamsPlugin::isSelectableType($fields[$k]['type'])) {
                $fields[$k]['selectable'] = true;
                $fields[$k]['values'] = $this->getValues($v['id']);
                $fields[$k]['default_value'] = $this->getDefaultValue($v['id']);
            }
            // Pасписываем по id
            self::$registry_fields['ids'][$v['id']] =  $fields[$k];
            // Записываем по имени
            self::$registry_fields[$action][$k] =$fields[$k];
        }
        return $fields;
    }

    /**
     * Возвращает полные данные одного поля
     * @param $id
     * @return array|bool|null
     */
    public function getFieldById($id) {
        // Проверяем хранилище
        if(isset(self::$registry_fields['ids']) && isset(self::$registry_fields['ids'][$id])) {
            return self::$registry_fields['ids'][$id];
        }
        $field = $this->getById((int)$id);
        if($field) {
            $params_model = new shopAdvancedparamsParamsModel($field['action']);
            $field['selectable'] = false;
            $field['count_values'] = $params_model->countParamValues($field['name']);
            if(shopAdvancedparamsPlugin::isSelectableType($field['type'])) {
                $field['selectable'] = true;
                $field['values'] = $this->getValues($field['id']);
                $field['default_value'] = $this->getDefaultValue($field['id']);
            }
            if(!isset(self::$registry_fields['ids'])) {
                self::$registry_fields['ids'] = array();
            }
            // Записываем в хранилище
            self::$registry_fields['ids'][$id] = $field;
            return self::$registry_fields['ids'][$id];
        } else {
            return false;
        }
    }

    /**
     * Возврашает все данные одного поля по его имени
     * @param $action
     * @param $name
     * @return array|bool|null
     */
    public function getFieldByName($action, $name) {
        if(isset(self::$registry_fields[$action]) && isset(self::$registry_fields[$action][$name])) {
            return self::$registry_fields[$action][$name];
        }
        $field = $this->getByField(array('action' => $action, 'name' => $name));
        if($field) {
            $params_model = new shopAdvancedparamsParamsModel($field['action']);
            $field['selectable'] = false;
            $field['count_values'] = $params_model->countParamValues($field['name']);
            if(shopAdvancedparamsPlugin::isSelectableType($field['type'])) {
                $field['selectable'] = true;
                $field['values'] = $this->getValues($field['id']);
                $field['default_value'] = $this->getDefaultValue($field['id']);
            }
            if(!isset(self::$registry_fields[$action])) {
                self::$registry_fields[$action] = array();
            }
            self::$registry_fields[$action][$name] = $field;
            return self::$registry_fields[$action][$name];
        } else {
            return false;
        }
    }

    /**
     * Вjзвращает выбираемые значения поля по его ID
     * @param $field_id
     * @return array|null
     */
    public function getValues($field_id) {
        $values =  $this->field_values_model->getByFieldId($field_id);
        // Отменил экранирование для получение чистого html значений для фронтенда, в бекенде экранируется на месте
        /*foreach ($values as $k=>&$v)
        {
            $v['value'] = htmlspecialchars($v['value']);
        }
        unset($v);*/
        return  $values;
    }

    /**
     * Возвращает выбираемое значение по умолчанию по ID поля
     * @param $field_id
     * @return string
     */
    public function getDefaultValue($field_id) {
        $value =  $this->field_values_model->getByField(array('field_id'=>$field_id, 'default'=>1));
        if($value) {
            // Отменил экранирование для получение чистого html значений для фронтенда, в бекенде экранируется на месте
            //return htmlspecialchars($value['value']);
            return $value['value'];
        } else {
            return '';
        }
    }

    /**
     * Меняет тип поля по ID поля
     * @param $id
     * @param $type
     */
    public function setFieldType($id, $type) {
        $this->updateById($id, array('type'=>$type));
    }

    /**
     * Проверяет существование поля в экшене по имени
     * @param $name
     * @param string $action
     * @return bool
     */
    public function field_exists($name, $action = '') {
        // Если передан ID поля, то проверяем по ID
        if(is_numeric($name) && empty($action)) {
            $param = $this->getById((int)$name);
            // Если передано имя поля и его экшен проверяем по имени
        } elseif(!empty($name) && shopAdvancedparamsPlugin::actionExists($action)) {
            $param = $this->getByField(array('name'=>$name, 'action'=> $action));
        }
        // Если найдено, возвращаем положительный результат
        if(!empty($param)) {
            return true;
        }
        return false;
    }

    /**
     * Сохраняет поле или обновляет его данные
     * @param $data
     * @param $errors
     * @return bool|int|mixed|resource
     */
    public function save($data, &$errors) {
        $field = array();
        $field_types = $this->getFieldTypes();
        // Если передан ID поля пытаемся его получить
        if(!empty($data['id'])) {
            $field = $this->getById($data['id']);
        }
        // Если все же не найдено ищем поле по имени и экшену
        if(empty($field) && !empty($data['name']) && !empty($data['action'])) {
            $field = $this->getByField(array('name'=>$data['name'], 'action'=>$data['action']));
        }
        // Если найдено поле, то будем обновлять его данные
        if(!empty($field)) {
            // Удаляем неизменные данные
            unset($data['id']);
            unset($data['name']);
            unset($data['action']);
            $this->updateById($field['id'], $data);
            return $field['id'];
        } elseif (!empty($data['name']) && !empty($data['action'])) {
            // Добавляем новое поле
            // Если тип поля некорректный или не установлен, будет установлен тип по умолчаню
            if(!isset($data['type']) || !array_key_exists($data['type'], $field_types)) {
                $data['type'] = 'input';
            }
            if(shopAdvancedparamsPlugin::actionExists($data['action'])) {
                return $this->insert($data);
            } else {
                $errors[] = 'Не удалось сохранить поле! Нет такого экшена "'.htmlspecialchars($data['action']).'"!';
                return false;
            }
        } else {
            // Если переданные данные некорректны
            $errors[] = 'Не удалось сохранить поле! Неверные данные поля!';
            return false;
        }
    }

    /**
     * Возвращает все типы полей
     * @return array|mixed|null
     */
    public function getFieldTypes() {
        return shopAdvancedparamsPlugin::getConfigParam(shopAdvancedparamsPlugin::CONFIG_FIELD_TYPES_KEY);
    }
}