<?php

class shopAdvancedparamsPluginBackendFileDeleteController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {
            // Принимаем данные
            $action = waRequest::post('action','',waRequest::TYPE_STRING_TRIM);
            $action_id = waRequest::post('action_id',0,waRequest::TYPE_INT);
            $field_name = waRequest::post('field_name','',waRequest::TYPE_STRING_TRIM);
            // Проверяем тип экшена
            if(!shopAdvancedparamsPlugin::actionExists($action)) {
                $this->errors[] = 'Неверный тип экшена!';
                return;
            }
            // Проверяем идентификатор экшена
            if(empty($action_id)) {
                $this->errors[] = 'Неверный идентификатор экшена!';
            }
            $files_class = new shopAdvancedparamsPluginFiles($action, $action_id);
            // Удаляем файл и запись в БД
            if($files_class->deleteFileByName($field_name)) {
                $this->response = 'ok';
            } else {
                $this->errors[] = 'Не удалось удалить файл!';
            }
        }
    }
}