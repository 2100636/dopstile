<?php

class shopAdvancedparamsPluginBackendFieldNameValidController extends waJsonController {
    
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
            $this->response = 'ok';
        } else {
            $this->errors[] = 'Неправильный запрос!';
        }
    }
}