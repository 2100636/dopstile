<?php

class shopAdvancedparamsPluginBackendProductSaveController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {
            $id = waRequest::post('action_id',0,waRequest::TYPE_INT);
            if(!empty($id)) {
                $plugin = new shopAdvancedparamsPlugin(array());
                $plugin->saveParams('product', $id);
                $this->response = 'ok';
            } else {
                $this->errors[] = 'Неправильный ID продукта!';
            }

        } else {
            $this->errors[] = 'Неправильный запрос!';
        }
    }

}