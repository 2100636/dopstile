<?php

/**
 * Class shopAdvancedparamsParamsModel
 */
class shopAdvancedparamsParamsModel {

    /**
     * Модель Доп. параметров зависит от типа экшена
     * @var null|shopAdvancedparamsPageParamsModel|shopAdvancedparamsProductParamsModel|shopAdvancedparamsCategoryParamsModel
     */
    protected $model = null;
    /**
     * @var null|shopAdvancedparamsFieldModel
     */
    protected $fieldModel = null;
    /**
     * @var null|shopAdvancedparamsFieldValuesModel
     */
    protected $fieldValuesModel  = null;
    /**
     * @var null|shopAdvancedparamsParamValueModel
     */
    protected $paramValueModel = null;
    /**
     * @var string
     */
    protected $action = '';

    /**
     * shopAdvancedparamsParamsModel constructor.
     * @param $action
     * @param null $type
     * @param bool $writable
     */
    public function __construct($action, $type = null, $writable = false)
    {
        // Проверяем тип экшена, затем создаем объект модели доп. параметров
        if(shopAdvancedparamsPlugin::actionExists($action)) {
            $class_name = 'shopAdvancedparams'.ucfirst($action).'ParamsModel';
            if(class_exists($class_name)) {
                $this->model = new $class_name();
            }
            $this->action = $action;
        }
        // Дополнительные модели
        $this->fieldModel = new shopAdvancedparamsFieldModel();
        $this->fieldValuesModel = new shopAdvancedparamsFieldValuesModel();
        $this->paramValueModel = new shopAdvancedparamsParamValueModel($this->action);
        $this->paramFileModel = new shopAdvancedparamsParamFileModel($this->action);
    }

    /**
     * Возвращает все доп. параметры по ID экшена
     * @param int $action_id
     * @return mixed
     */
    public function get($action_id = 0) {
        // Получаем параметры из исходной таблицы
        $params = $this->model->get($action_id);
        if($this->action=='category') {
            unset($params['enable_sorting']);
        }
        return $params;
    }

    /**
     * Сохраняет доп. параметры по ID экшена
     * @param int $action_id
     * @param array $params
     */
    public function set($action_id = 0, $params = array(), $ignore_active = false) {
        if(is_array($params)) {
            $active_params = waRequest::post(shopAdvancedparamsPlugin::PARAM_FIELD_NAME.'_active',array(),waRequest::TYPE_ARRAY);
            $real_param_values = array();
            $filesClass = new shopAdvancedparamsPluginFiles($this->action, $action_id);
            foreach ($params as $name => $value) {
                $field = $this->fieldModel->getFieldByName($this->action, $name);
                // Если поле существует
                if($field) {
                    // Если тип поля не является файловым проверяем изменилось ли значение
                    if(!shopAdvancedparamsPlugin::isFileType($field['type'])) {
                        $file = $this->paramFileModel->getByActionIdName($action_id, $name);
                        // Если в таблице файлов есть запись и знаачение поля стало не ссылкой на файл
                        if($file && trim($value) != trim($file['value'])) {
                            // Удаляем файл и запись из бд
                            $filesClass->deleteFileByName($name);
                        } 
                        // Если тип поля многострочный
                        //if(shopAdvancedparamsPlugin::isPersistentType($field['type'])) {
                        $real_param_values[$name] = $value;
                        // }
                    } 
                    if(isset($active_params[$field['id']]) || $ignore_active) {
                        // Делаем значение параметра одночтрочным, чтобы в реальном поле доп. параметров ничего не нарушилось
                        $params[$name] = preg_replace("[/\r\n/]"," ", $value);
                    } else {
                        unset($params[$name]);
                    }
                }
            }
            // Сохраняем исходные значения
            $this->paramValueModel->set($action_id, $real_param_values);
            // Сохраняем параметры в таблицу доп параметров
            $this->model->set($action_id, $params);
        }
    }

    /**
     * Возвращает объект модели доп. параметров
     * @return null
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Возвращает колонку идентификатора экшена реальной модели доп. параметров
     * @return mixed
     */
    public function getActionIdField() {
        return $this->model->getActionIdField();
    }

    /**
     * Возвращает уникальные имена доп. параметров из таблицы доп. параметров экшена
     * @return mixed
     */
    public function getParamsNames() {
        return  $this->model->query('SELECT name FROM '.$this->model->getTableName().' GROUP BY name')->fetchAll('name');
    }

    /**
     * Возвращает уникальные значения доп. параметров из таблицы доп. параметров экшена для построения выбираемых значений поля
     * @param string $name
     * @return array
     */
    public function getParamValues($name = '') {
        $return = array();
        $values = $this->model->query('SELECT value FROM '.$this->model->getTableName()." WHERE name=s:name GROUP BY value",array('name'=> $name))->fetchAll();
        foreach ($values as $k=>$v)
        {
            $return[] = $v['value'];
        }
        return $return;
    }

    /**
     * Возвращает количество установленных значений доп. параметров экшена
     * @param string $name
     * @return int
     */
    public function countParamValues($name = '') {
        if(!empty($name)) {
            return $this->model->countByField('name', $name);
        }
        return 0;
    }

    /**
     * Удаляет все установленные значения доп. параметров по имени в таблице доп. параметров экшена
     * @param string $name
     */
    public function deleteByName($name = '') {
         $this->model->deleteByField('name', $name);
    }

    /**
     * Удаляет все установленые значения доп. параметров по идентификатору экшена и сохраненные исходные значения
     * @param int $action_idshop_advancedparams_param_value
     */
    public function deleteByActionId($action_id = 0) {
        $this->model->deleteByField($this->getActionIdField(),$action_id);
        $this->paramValueModel->deleteByActionId($action_id);
    }
}