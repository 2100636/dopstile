<?php

/**
 * Class shopAdvancedparamsParamFileModel
 */
class shopAdvancedparamsParamFileModel extends waModel
{
    /**
     * @var string
     */
    protected $table = 'shop_advancedparams_param_file';
    /**
     * Тип экшена
     * @var null|string
     */
    protected $action = '';

    /**
     * shopAdvancedparamsParamFileModel constructor.
     * @param null|string $action
     * @param null $type
     * @param bool $writable
     */
    public function __construct($action = '', $type = null, $writable = false)
    {
        $this->action = $action;
        parent::__construct($type, $writable);
    }

    /**
     * Удаляет все файлы по имени поля, используется при удалении поля
     * @param $field_name
     * @throws waException
     */
    public function deleteByName($field_name) {
        $action_ids = $this->query('SELECT action_id FROM '.$this->getTableName().' WHERE '.$this->getWhereByField('name', $field_name).' GROUP BY action_id')->fetchAll('action_id');
        foreach ($action_ids as $action) {
            $filesClass = new shopAdvancedparamsPluginFiles($this->action, $action['action_id']);
            $filesClass->deleteFileByName($field_name);
        }
    }

    /**
     * Возвращает файл по имени поля и идентификатору экшена
     * @param $action_id
     * @param $name
     * @return array|null
     */
    public function getByActionIdName($action_id, $name) {
        $value = $this->getByField(array('action' => $this->action, 'name'=> $name, 'action_id' => $action_id));
        if(!empty($value)) {
            return $value;
        }
        return null;
    }

    /**
     * Удаляет файл по имени поля и идентификатору экшена
     * @param $action_id
     * @param $name
     * @return bool
     */
    public function deleteByActionIdName($action_id, $name) {
        return $this->deleteByField(array('action' => $this->action, 'action_id' => $action_id, 'name'=> $name));
    }

    /**
     * Возвращает все файлы экшена по его идентификатору
     * @param $action_id
     * @return array|null
     */
    public function getByActionId($action_id) {
        return $this->getByField(array('action' =>$this->action, 'action_id'=>$action_id),true);
    }

    /**
     * Удаляет все файлы экшена по его id
     * @param $action_id
     */
    public function deleteByActionId($action_id) {
        $filesClass = new shopAdvancedparamsPluginFiles($this->action, $action_id);
        $filesClass->deleteAll();
    }

    /**
     * Возвращает ссылку на файл от корня сайта
     * @param $action_id
     * @param $name
     * @return string|null
     */
    public function getFileLink($action_id, $name) {
        $file = $this->getByActionIdName($action_id, $name);
        if($file) {
            return $file['value'];
        }
        return null;
    }

    /**
     * Сохраняет данные файла в бд или обновляет ссылку
     * @param $data
     */
    public function save($data) {
        if(!empty($data['name']) && !empty($data['action_id'])) {
            $file = $this->getByActionIdName($data['action_id'], $data['name']);
            if($file) {
                $this->updateByField(
                    array('action' => $file['action'], 'action_id' => $file['action_id'], 'name' => $file['action_id']),
                    array('value' => $data['value'])
                );
            } else {
                $data['action'] = $this->action;
                $this->insert($data);
            }
        }
    }
}