<?php

class shopAdvancedparamsPluginBackendFieldValueSaveController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {
            $field_model = new shopAdvancedparamsFieldModel();
            $field_values_model = new shopAdvancedparamsFieldValuesModel();

            $id = waRequest::post('id', 0, waRequest::TYPE_INT);
            $field_id = waRequest::post('field_id', 0, waRequest::TYPE_INT);
            $value =  waRequest::post('value');
            $default =  waRequest::post('default');
            // Если передан иентификатор возможного значения и value, меняем значение
            if(!empty($id) && !is_null($value)) {
                $data = array(
                    'value' => $value,
                );
                if($field_values_model->updateById($id, $data)) {
                    $this->response = $field_values_model->getById($id);
                } else {
                    $this->errors[] = 'Ошибка при изменении значения!';
                }
            // Если передан флаг по умолчанию, то ставим флаг у возможного значения
            } elseif(!empty($id) && !is_null($default)) {
                if(!empty($default)) {
                    $field_values_model->setDefaultValue($id);
                    $this->response = $field_values_model->getById($id);
                } else {
                    $this->errors[] = 'Вы не можете удалить значение по умолчанию!';
                }
             // Если не передан ID возможного значения, но передан ID поля,то добавляем новое возможное значение поля
            } elseif(!empty($field_id) && $field_model->field_exists($field_id)) {
                $count_values  = $field_values_model->countByFieldId($field_id);
                $default = 0;
                // Если у поля нет еще возможных значений, ставим новое значение по умолчанию
                if($count_values < 1) {
                    $default = 1;
                }
                // Сохраняем новое значение
                $data = array(
                    'field_id' =>$field_id,
                    'value' => '',
                    'default' => $default
                );
                $id = $field_values_model->insert($data);
                if($id) {
                    $this->response = $field_values_model->getById($id);
                } else {
                    $this->errors[] = 'Ошибка при добавлении значения!';
                }
            } else {
                $this->errors[] = 'Неправильный идентификатор поля!';
            }
        } else {
            $this->errors[] = 'Неправильный запрос!';
        }
    }
}