<?php

class shopAdvancedparamsPluginBackendFieldValueDeleteController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {
            // Принимаем ид значения
            $id = waRequest::post('id',0, waRequest::TYPE_INT);
            $field_values_model = new shopAdvancedparamsFieldValuesModel();
            // Если передан положительный ID, то удаляем
            if(!empty($id)) {
                if($field_values_model->deleteById($id) ) {
                    $this->response = 'ok';
                } else {
                    $this->errors[] = 'Ошибка при удалении значения!';
                }
            // Выводим ошибку
            } else {
                $this->errors[] = 'Неправильный идентификатор значения поля!';
            }
        }
    }
}