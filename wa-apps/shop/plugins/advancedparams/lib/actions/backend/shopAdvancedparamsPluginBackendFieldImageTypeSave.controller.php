<?php

class shopAdvancedparamsPluginBackendFieldImageTypeSaveController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {

            $field_id = waRequest::post('field_id', 0, waRequest::TYPE_INT);
            $size_type = waRequest::post('size_type','none',waRequest::TYPE_STRING_TRIM);
            $width = waRequest::post('width', 0, waRequest::TYPE_INT);
            $height = waRequest::post('height', 0, waRequest::TYPE_INT);

            $field_model = new shopAdvancedparamsFieldModel();
            
            if(!empty($field_id) && $field_model->getById($field_id)) {
                $data = $this->getSizeData($size_type, $width, $height);
                if($data) {
                    $field_model->updateById($field_id,$data);
                    $this->response = $data;
                } else {
                    return;
                }

            }
        } else {
            $this->errors[] = 'Неправильный запрос!';
        }
    }
    protected function getSizeData($size_type = '', $width = '', $height = '') {
        $data = array(
            'size_type' => 'none',
            'width'=>null,
            'height'=> null
        );
        switch ($size_type) {
            case 'none':
                break;
            case 'max':
                if($width < 1) {
                    $this->errors[] = 'Укажите правильный размер изображения!';
                    return false;
                }
                $data['size_type'] = 'max';
                $data['width'] =  $width;
                break;
            case 'width':
                if($width < 1) {
                    $this->errors[] = 'Укажите корректную ширину изображения!';
                    return false;
                }
                $data['size_type'] = 'width';
                $data['width'] =  $width;
                break;
            case 'height':
                if($height < 1) {
                    $this->errors[] = 'Укажите корректную высоту изображения!';
                    return false;
                }
                $data['size_type'] = 'height';
                $data['height'] =  $height;
                break;
            case 'crop':
                if($width < 1) {
                    $this->errors[] = 'Укажите правильный размер изображения!';
                    return false;
                }
                $data['size_type'] = 'crop';
                $data['width'] =  $width;
                break;
            case 'rectangle':
                if($width < 1 || $height < 1) {
                    $this->errors[] = 'Укажите правильные размеры изображения!';
                    return false;
                }
                $data['size_type'] = 'rectangle';
                $data['width'] =  $width;
                $data['height'] =  $height;
                break;
        }
        return $data;
    }
}