<?php

class shopAdvancedparamsPluginBackendFieldSaveController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {
            $data = waRequest::post();
            $data['name'] = trim($data['name']);
            if(empty($data['name'])) {
                $this->errors[] = 'Ключ поля не может быть пустым!';
                return;
            }
            // Проверяем корректность символов ключа поля
            if(preg_match('~[^a-z0-9_\.]~',$data['name'])) {
                $this->errors[] = 'Для ключа поля доступны только латинские символы и символ подчеркивания без пробелов!';
                return;
            }
            // Проверяем доступно ли имя поля для создания
            if(shopAdvancedparamsPlugin::isBannedField($data['action'], $data['name'])) {
                $this->errors[] = 'Поле с таким ключом не может быть создано, оно зарезервировано системой!';
                return;
            }
            $field_model = new shopAdvancedparamsFieldModel();
            // Проверяем не создано ли раньше такого же поля
            if(empty($data['id'])) {
                if($field_model->field_exists($data['name'], $data['action'])) {
                    $this->errors[] = 'Поле с таким ключом уже есть!';
                    return;
                }
            }
            // Сохраняем поле
            $id = $field_model->save($data, $this->errors);
            // Отдаем ответ
            if($id) {
                $this->response = $field_model->getById($id);
            }
        }
    }
}