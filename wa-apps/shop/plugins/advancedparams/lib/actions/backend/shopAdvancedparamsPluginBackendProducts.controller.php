<?php

class shopAdvancedparamsPluginBackendProductsController extends waJsonController {
    
    public function execute() {
        if(waRequest::method()=='post') {

           $ids = waRequest::post('ids');
            if(!empty($ids) && is_array($ids)) {
                 $actions = array();
                foreach($ids as $id) {
                    $fieldsClass = new shopAdvancedparamsPluginFields('product');
                    $paramsModel = new shopAdvancedparamsParamsModel('product');
                    // Получаем все доп. параметры
                    $params = $paramsModel->get($id);
                    // Получаем все поля в виде массива с HTML кодом
                    $fields_array  = $fieldsClass->getFields($id, $params);
                    $fields_html = '';
                    // Объединяем все поля
                    foreach ($fields_array as $v) {
                        $fields_html .= $v;
                    }
                    // Готовим окончательный макет для вывода полей
                    $html = '<form action="?plugin=advancedparams&action=ProductSave" class="advancedparams_plugin-action-form">
                    <input type="hidden" name="action_id" value="'.$id.'">
                    <div class="field-group advancedparams_plugin-field-group">
                     <div class="advancedparams_plugin-fields">
                    <div class="field-group">';
                        if(!empty($fields_html)) {
                            $html .= $fields_html;
                        } else {
                            $html .= '<p>Добавьте необходимые поля в <a href="/'.wa()->getConfig()->getBackendUrl().'/'.shopAdvancedparamsPlugin::APP.'/?action=plugins#/advancedparams/">настройках плагина</a>!</p>';
                        }
                    $html .= '<div class="field advancedparams_plugin_add_field">
                                <a href="#" class="advancedparams_plugin-add-param"><i class="icon16 add"></i>Добавить параметр</a>
                              </div>
                     </div>
                      
                        <div class="field advancedparams-action-save" style="display: none;">
                            <div class="value submit">
                                <a href="#" class="button green">Сохранить</a>
                            </div>
                        </div>
                        </div>
                         <div class="clear-both"></div>
                        </div></form>';
                    $actions[$id] = $html;
                }
                $fields_model = new shopAdvancedparamsFieldModel(); // Модель полей
                $fields = $fields_model->getActionFields('product');

                $this->response['fields'] = $fields;
                $this->response['actions'] = $actions;
            }
        } else {
            $this->errors[] = 'Неправильный запрос!';
        }
    }

}