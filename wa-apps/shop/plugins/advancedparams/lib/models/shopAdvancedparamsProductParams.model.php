<?php

class shopAdvancedparamsProductParamsModel extends shopProductParamsModel
{
    /**
     * Колонка идентификатора экшена
     * @var string
     */
    protected $action_id_field = 'product_id';
    /**
     * Возвращает колонку идентификатора экшена
     * @return string
     */
    public function getActionIdField() {
        return $this->action_id_field;
    }
}