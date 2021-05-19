<?php

class shopAdvancedparamsPluginBackendFileUploadController extends waJsonController {
    public function execute() {
        if(waRequest::method()=='post') {
            // Принимаем данные
            $action = waRequest::post('action','', waRequest::TYPE_STRING_TRIM);
            $action_id = waRequest::post('action_id',0, waRequest::TYPE_INT);
            $field_name = waRequest::post('field_name','', waRequest::TYPE_STRING_TRIM);
            // Проверяем существование типа экшена
            if(!shopAdvancedparamsPlugin::actionExists($action)) {
                $this->errors[] = 'Неверный тип экшена!';
                return;
            }
            // Проверяем идентификатор экшена
            if(empty($action_id)) {
                $this->errors[] = 'Неверный идентификатор экшена!';
                return;
            }
            $field_model = new shopAdvancedparamsFieldModel();
            $field = $field_model->getFieldByName($action, $field_name);
            // Проверяем существование поля у экшена
            if(!$field || !shopAdvancedparamsPlugin::isFileType($field['type'])) {
                $this->errors[] = 'Поле не существует!';
                return;
            }
            // Если все проверки успешны, добавляем файл
            $url = waRequest::post('advancedparams_url' ,'',waRequest::TYPE_STRING_TRIM);
            $file = waRequest::file('advancedparams_file');
            if(!empty($url) || $file->uploaded()) {
                $size = array();
                if ($field['type'] == 'image') {
                    $size['type'] = waRequest::post('size_type', 'none', waRequest::TYPE_STRING_TRIM);
                    $size['width'] = waRequest::post('width', 0, waRequest::TYPE_INT);
                    $size['height'] = waRequest::post('height', 0, waRequest::TYPE_INT);
                }
                if (!empty($url)) {
                    try {
                        $download_file = new shopAdvancedparamsPluginDownloadFile();
                        $file_data = $download_file->downloadFile($url);
                        $file = new waRequestFile($file_data, true);
                    } catch (waException $e) {
                        $this->errors[] = $e->getMessage();
                        return;
                    }
                }
                $files_class = new shopAdvancedparamsPluginFiles($action, $action_id);
                $data = $files_class->saveFile($file, $field['type'], $field['name'], $this->errors, $size);
                if (!$data) {
                    return;
                } else {
                    $this->response['file_link'] = $data['file_link'];
                    $this->response['value'] = $data['value'];
                    $this->response['field_name'] = $field_name;
                    $this->response['type'] = $field['type'];
                    $this->response['file_name'] = basename($data['file_link']);
                    $this->response['action_id'] = $action_id;
                }
            } else {
                $this->errors[] = 'Файл не был загружен!';
            }
        } else {
            $this->errors[] = 'Файл не был загружен!';
        }
    }
}