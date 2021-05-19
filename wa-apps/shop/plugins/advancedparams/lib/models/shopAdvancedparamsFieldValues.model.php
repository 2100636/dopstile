<?php

/**
 * Class shopAdvancedparamsFieldValuesModel
 */
class shopAdvancedparamsFieldValuesModel extends waModel
{

    /**
     * @var string
     */
    protected $table = 'shop_advancedparams_field_values';

    /**
     * Возвращает все значени поля по Id
     * @param $id
     * @return array|null
     */
    public function getByFieldId($id) {
        // Получаем все значения поля
        $values =  $this->getByField('field_id',(int)$id,true);
        return $values;
    }

    /**
     * Удаляет все выбираемые значения по id поля
     * @param $field_id
     * @return bool
     */
    public function deleteByFieldId($field_id)
    {
        return $this->deleteByField('field_id', $field_id);
    }

    /**
     * Подсчитывает количество выбираемых значений поля
     * @param $field_id
     * @return int
     */
    public function countByFieldId($field_id) {
        return $this->countByField('field_id', $field_id);
    }

    /**
     * Устанавливает выбираемоезначение по умолчанию по его id
     * @param $id
     */
    public function setDefaultValue($id) {
        $value = $this->getById($id);
        if(!empty($value['id'])) {
            $data = array('default' => 0);
            $this->updateByField('field_id',$value['field_id'], $data);
            $data['default'] = 1;
            $this->updateById($value['id'],$data);
        }
    }
}