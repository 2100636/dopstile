<?php

/**
 * Class shopAdvancedparamsCategoryParamsModel
 */
class shopAdvancedparamsCategoryParamsModel extends shopCategoryParamsModel
{
    /**
     * @var string
     */
    protected $action_id_field = 'category_id';

    /**
     * Возвращает колонку идентификатора экшена
     * @return string
     */
    public function getActionIdField() {
        return $this->action_id_field;
    }
}