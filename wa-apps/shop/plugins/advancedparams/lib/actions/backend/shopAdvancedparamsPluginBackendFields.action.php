<?php

class shopAdvancedparamsPluginBackendFieldsAction extends waViewAction {
    
    public function execute() {
        $action = waRequest::get('type','',waRequest::TYPE_STRING_TRIM); // Тип экшена
        if(empty($action)) {
            $action = 'category';
        }
        // Если экшен существует
        if(shopAdvancedparamsPlugin::actionExists($action)) {
            $field_model = new shopAdvancedparamsFieldModel();
            // Передаем поля
            $this->view->assign('fields' ,  $field_model->getActionFields($action));
            $this->view->assign('new_fields' ,$field_model->getNewFields($action));
            
            // Передаем типы полей
            $this->view->assign('field_types', $field_model->getFieldTypes());
            // Передаем экшен
            $this->view->assign('action', $action);
            // Передаем типы экшенов
            $this->view->assign('action_types', shopAdvancedparamsPlugin::getConfigParam('action_types'));
        } else {
            $this->view->assign('error','Экшена с типом '.htmlspecialchars($action).' не существует!');
        }
        
    }
} 