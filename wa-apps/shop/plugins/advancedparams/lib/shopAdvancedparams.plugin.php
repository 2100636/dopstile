<?php

/**
 * Class shopAdvancedparamsPlugin
 */
class shopAdvancedparamsPlugin extends shopPlugin
{
    /**
     * Идентификатор приложения
     */
    const APP = 'shop';
    /**
     * Идентификатор плагина
     */
    const PLUGIN_ID = 'advancedparams';
    /**
     * Файл конфига
     */
    const CONFIG_FILE = 'config.php';

    /**
     * ключ типов полей в конфиге
     */
    const CONFIG_FIELD_TYPES_KEY = 'field_types';
    /**
     * Префикс имен полей в бекенде прим: name="advancedparams[param1]"
     */
    const PARAM_FIELD_NAME = 'advancedparams_plugin';
    /**
     * Css Класс полей в бекенде
     */
    const PARAM_FIELD_CLASS = 'advancedparams_plugin-param';
    protected static $models = array();

    /* Формирует массив параметров там где не был сформирован, например в категории */
    public function frontendProducts($event_params) {
        if($this->getSettings('status')=='1' && $this->getSettings('frontend_products')=='1') {
            if(isset($event_params['products']) && !empty($event_params['products'])) {
                $params_model = new shopAdvancedparamsParamsModel('product');
                foreach ($event_params['products'] as &$p) {
                    $p['params'] = $params_model->get($p['id']);
                }
            }
        }
    }

    /**
     * Возвращает HTML код дополнительных полей для продукта
     * @param $product
     * @return array
     */
    public function backendProduct($product) {
        if($this->getSettings('status')=='1') {
            $product_id = 0;
            if (isset($product['id']) && is_numeric($product['id'])) {
                $product_id = intval($product['id']);
            }
            $html = $this->getFields('product', $product_id);
            return array('edit_descriptions' => $html);
        }
        return '';
    }
    /* DEMO DEMO EDMO DEMo */
    public function backendProducts() {
        // backend_products.toolbar_organize_li
        $html = ' <div class="block advancedparams_plugin-products-buttons"  style="border:5px solid #C00;">'.$this->getRedactorScripts().'<link href="'.self::getPluginUrlStatic().'css/shopAdvancedparamsPlugin.css" rel="stylesheet" type="text/css">
                    <script type="text/javascript" src="'.self::getPluginUrlStatic().'js/shopAdvancedparamsPluginBackendProducts.js"></script>
                    <ul class="menu-v with-icons">
                        <li><a id="advancedparams_plugin-edit-products" href="#"><i class="icon16 view-table"></i>Доп. параметры</a></li>
                    </ul>
                </div>';//toolbar_sectio
        //return array('toolbar_organize_li' => $html);
        return array('toolbar_section' => $html);
    }
    public function backendProducts1() {
        $html = ' <div class="block advancedparams_plugin-products-buttons">'.$this->getRedactorScripts().'<link href="'.self::getPluginUrlStatic().'css/shopAdvancedparamsPlugin.css" rel="stylesheet" type="text/css">
                    <script type="text/javascript" src="'.self::getPluginUrlStatic().'js/shopAdvancedparamsPluginBackendProducts.js"></script>
                    <ul class="menu-v with-icons">
                        <li><a id="advancedparams_plugin-edit-products" href="#"><i class="icon16 view-table"></i>Доп. параметры</a></li>
                    </ul>
                </div>';
        return array('toolbar_section' => $html);
    }

    /**
     * Сохраняет доп. параметры прожукта
     * @param $params
     */
    public function productSave($params) {
        if($this->getSettings('status')=='1') {
            $product = $params['instance'];
            $controller = $this->getController();
            if($controller == 'productsave') {
                $this->saveParams('product', $product['id']);
                // При импорте продуктов массив передаем принудительно
            } elseif($controller == 'csvproductrun' && isset($product['params'])) {
                $this->saveParams('product', $product['id'], $product['params']);
            } else {
              // Пишем в лог вызовы от неизвестных источников, например 1c, после
              //waLog::log('Вызван хук productSave для '.$product['id'].' в контроллере "'.$controller.'".','advancedparams.log');
            }
        }
    }
    protected function getController() {
        $module = waRequest::param('module');
        $action = waRequest::param('action');
        // Если не были записаны параметры запроса сами достаем их
        if ($module === null) {
            $module = waRequest::get('module');
        }
        if ($action === null) {
            $action = waRequest::get('action');
        }
        return strtolower($module.$action);
    }
    /**
     * Удаляет все сохраненные данные плагином продукта
     * @param $params
     */
    public function productDelete($params) {
        if($this->getSettings('status')=='1') {
            foreach ($params['ids'] as $v) {
                $this->deleteParams('product', $v);
            }
        }
    }

    /**
     * Возвращает HTML код дополнительных полей для категории
     * @param $params
     * @return string
     */
    public function backendCategoryDialog($category) {
        if($this->getSettings('status')=='1') {
            $category_id = 0;
            if (isset($category['id'])) {
                $category_id = intval($category['id']);
            }
            $html = $this->getFields('category', $category_id);
            return $html;
        }
        return '';
    }

    /**
     * Сохраняет доп. параметры Категории
     * @param $params
     */
    public function categorySave($params)  {
        if($this->getSettings('status')=='1') {
            if (isset($params['id']) && intval($params['id']) > 0) {
                $controller = $this->getController();
                if($controller == 'productssavelistsettings' || $controller == 'categorysave') {
                    $this->saveParams('category', $params['id']);
                }
            }
        }
    }

    /**
     * Удаляет все сохраненные данные плагином категории
     * @param $category
     */
    public function categoryDelete($category) {
        if($this->getSettings('status')=='1') {
            $this->deleteParams('category', $category['id']);
        }
    }

    /**
     * Возвращает HTML код дополнительных полей для страницы для старых версий фреймворка
     * @param $page
     * @return array
     */
    public function backendPageEdit($page) {
        $html = '';
        if($this->getSettings('status')=='1') {
            if (isset($page['id']) && intval($page['id']) > 0) {
                $html = $this->getFields('page', $page['id']);
            }
        }
        return array(
            'settings_section'=> $html,
        );
    }

    /**
     * Возвращает HTML код дополнительных полей для страницы для новых версий фреймворка
     * @param $data
     * @return string
     */
    public function PageEdit($data) {
        if($this->getSettings('status')=='1') {
            $page_id = 0;
            if (isset($data['page']) && isset($data['page']['id'])) {
                $page_id = intval($data['page']['id']);
            }
            $html = $this->getFields('page', $page_id);
            return $html;
        }
        return '';
    }

    /**
     * Сохраняет доп. параметры страницы
     * @param $page
     */
    public function pageSave($page) {
        if($this->getSettings('status') == '1') {
            if (isset($page['page']) && intval($page['page']['id']) > 0) {
                $this->saveParams('page', $page['page']['id']);
            }
        }
    }

    /**
     * Удаляет все сохраненные данные страницы
     * @param int $page
     */
    public function pageDelete($event_params) {
        if($this->getSettings('status')=='1') {
            $this->deleteParams('page', $event_params);
        }
    }

    /**
     * Возвращает готовый к показу HTML код дополнительных полей в зависимости от типа экшена и ID
     * @param $action
     * @param $action_id
     * @return string
     */
    protected function getFields($action, $action_id = 0) {
           
            $fieldsClass = new shopAdvancedparamsPluginFields($action);
            $paramsModel = new shopAdvancedparamsParamsModel($action);
            // Получаем все доп. параметры
            $params = $paramsModel->get($action_id);
            // Получаем все поля в виде массива с HTML кодом
            $fields_array  = $fieldsClass->getFields($action_id, $params);
            $fields_html = '';
            // Объединяем все поля
            foreach ($fields_array as $v) {
                $fields_html .= $v;
            }

            // Готовим окончательный макет для вывода полей
            $html = '<div class="field-group advancedparams_plugin-field-group">
<link href="'.self::getPluginUrlStatic().'css/shopAdvancedparamsPlugin.css" rel="stylesheet" type="text/css">
'.$this->getRedactorScripts().'
<script type="text/javascript" src="'.self::getPluginUrlStatic().'js/shopAdvancedparamsPlugin.js"></script>
<h2 class="advancedparams_plugin-toggle"><i class="icon16 '.(!$this->getSettings('scroll')?" rarr":" darr").'"></i>Дополнительные параметры</h2>
<div class="advancedparams_plugin-fields'.(!$this->getSettings('scroll')?" advancedparams_plugin-hide":"").'">
<div class="field-group">'.$this->getActionDescription($action).'</div>';
        if(!empty($fields_html)) {
            $html .= $fields_html;
        } else {
            $html .= '<p>Добавьте необходимые поля в <a href="/'.wa()->getConfig()->getBackendUrl().'/'.shopAdvancedparamsPlugin::APP.'/?action=plugins#/advancedparams/">настройках плагина</a>!</p>';
        }
        $html .='<div class="field advancedparams_plugin_add_field"> 
<a href="#" class="advancedparams_plugin-add-param"><i class="icon16 add"></i>Добавить параметр</a>
</div>
<div class="clear-both"></div>
</div>
</div>

<script type="text/javascript">
$(function () {
    $.shopAdvancedparamsPlugin.init("'.$action.'",'.(int)$action_id.');
});
</script>';
            return $html;
    }
    public function getRedactorScripts() {
        $app = wa();
        $version = $app->getVersion('webasyst');
        if(version_compare($version,'1.7.13.173','>')) {
            $control = '<input type="hidden" name="_csrf" value="'.waRequest::cookie('_csrf','').'" id="advancedparams_plugin_csrf"><link rel="stylesheet" href="'.self::getPluginUrlStatic().'css/redactor.css">';
            $control .= '<script type="text/javascript" src="'.self::getPluginUrlStatic().'js/redactor.plugin.source.js"></script>';
        } else {
            $wa_url = $app->getRootUrl();
            $lang = substr($app->getLocale(), 0, 2);

            $control = '<link rel="stylesheet" href="' . $wa_url . 'wa-content/js/redactor/redactor.css">';
            $control .= '<script src="' . $wa_url . 'wa-content/js/redactor/redactor.min.js"></script>';
            $control .= '<script src="' . $wa_url . 'wa-content/js/redactor/redactor.plugins.js"></script>';
            if ($lang != 'en') {
                $control .= '<script src="' . $wa_url . 'wa-content/js/redactor/' . $lang . '.js"></script>';
            }

        }

       return $control;
    }
    protected function getActionDescription($action) {
        $action_variables = shopAdvancedparamsPlugin::getConfigParam('action_variable');
        $action_variable = $action_variables[$action];
        return '<span class="hint">Все установленные параметры будут доступны в шаблонах как {'.$action_variable.'.<strong>key</strong>}</span>';
    }

    /**
     * Сохраняет все доп параметры экшена по ID
     * @param $action
     * @param $action_id
     */
    public function saveParams($action, $action_id, $params = null) {
        $paramsClass = new shopAdvancedparamsPluginParams($action);
        $ignore_active = false;
        if(is_null($params)) {
            $params = waRequest::post(shopAdvancedparamsPlugin::PARAM_FIELD_NAME);
        } else {
            $ignore_active = true;
        }
        $paramsClass->saveParams($action_id, $params, $ignore_active);
    }

    /**
     * Удаляет все доп. параметры экшена и данные сохраненные плагином
     * @param $action
     * @param $action_id
     */
    protected function deleteParams($action, $action_data) {
        $paramsClass = new shopAdvancedparamsPluginParams($action);
        $paramsClass->deleteParams($action_data);
    }
   
    private static function getModel($action, $model_name, $param1 = false, $param2 = false) {
        $model_name = 'shopAdvancedparams'.ucfirst($model_name).'Model';
        if(!isset(self::$models[$action])) {
            self::$models[$action] = array();
        }
        if(!isset(self::$models[$action][$model_name])) {
            if(class_exists($model_name)) {
                if($param1 && !$param2) {
                    self::$models[$action][$model_name] = new $model_name($param1);
                } elseif($param1 && $param2) {
                    self::$models[$action][$model_name] = new $model_name($param1,$param2);
                } else {
                    self::$models[$action][$model_name] = new $model_name();
                }
            }
        }
        if(isset(self::$models[$action][$model_name])) {
            return self::$models[$action][$model_name];
        }
        return null;
    }
  
    /**
     * Возвращает URL плагина от корня домена
     * @param bool $absolute
     * @return string
     */
    public static function getPluginUrlStatic($absolute = false) {
        return wa()->getAppStaticUrl(self::APP, $absolute).'plugins/'.self::PLUGIN_ID.'/';
    }

    /**
     * Проверяет существование типа экшена
     * @param $action
     * @return bool
     */
    public static function actionExists($action) {
        $action_types = shopAdvancedparamsPlugin::getConfigParam('action_types');
        if(array_key_exists($action, $action_types)) {
            return true;
        }
        return false;
    }

    /**
     * Проверяет является ли тип поля многострочным для дублирующего сохранения исходных значений
     * @param $type
     * @return bool
     */
    public static function isPersistentType($type) {
        $field_types_persistent = shopAdvancedparamsPlugin::getConfigParam('field_types_persistent');
        if(array_key_exists($type, $field_types_persistent)) {
            return true;
        }
        return false;
    }

    /**
     * Проверяет является ли тип поля выбираемым
     * @param $type
     * @return bool
     */
    public static function isSelectableType($type) {
        $field_types_selectable = shopAdvancedparamsPlugin::getConfigParam( 'field_types_selectable');
        if(array_key_exists($type,  $field_types_selectable)) {
            return true;
        }
        return false;
    }

    /**
     * Проверяет является ли поле файловым
     * @param $type
     * @return bool
     */
    public static function isFileType($type) {
          $field_types_file = shopAdvancedparamsPlugin::getConfigParam('field_types_file');
        if(array_key_exists($type,  $field_types_file)) {
            return true;
        }
        return false;
    }

    /**
     * Пишет в лог плагина сообщение
     * @param $message
     */
    public static function log($message) {
        waLog::log($message, 'shop/plugins/advancedparams.log');
    }

    /**
     * Проверяет является ли имя поля зарезервированным другим функционалом
     * @param string $action
     * @param string $name
     * @return bool
     */
    public static function isBannedField($action = '', $name = '') {
        $banned_fields = shopAdvancedparamsPlugin::getConfigParam('banned_fields');
        if(!empty($name)) {
            if(isset($banned_fields[$action])) {
                if(!array_key_exists($name, $banned_fields[$action])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Возвращает объект приложения
     * @return waSystem
     */
    private static function getConfig() {
        return wa(self::APP);
    }

    /**
     * Возвращает параметр конфигурации по ключу
     * @param null $param
     * @return array|mixed|null
     */
    public static function getConfigParam($param = null)
    {
        static $config = null;
        if (is_null($config)) {
            $app_config = self::getConfig();
            $files = array(
                $app_config->getAppPath('plugins/'.self::PLUGIN_ID, self::APP).'/lib/config/'.self::CONFIG_FILE, // defaults
                $app_config->getConfigPath(self::APP.'/plugins/'.self::PLUGIN_ID).'/'.self::CONFIG_FILE, // custom
            );
            $config = array();
            foreach ($files as $file_path) {
                if (file_exists($file_path)) {
                    $config = include($file_path);
                    if ($config && is_array($config)) {
                        foreach ($config as $name => $value) {
                            $config[$name] = $value;
                        }
                    }
                }
            }
        }
        return ($param === null) ? $config : (isset($config[$param]) ? $config[$param] : null);
    }


    public static function getParams($action, $action_id, $params = array()) {
        $action_id = intval($action_id);
        if(shopAdvancedparamsPlugin::actionExists($action)) {
            $params_model = new shopAdvancedparamsParamsModel($action);
            if(!empty($action_id)) {
                if(empty($params)) {
                    $params = $params_model->get($action_id);
                }
                if(!empty($params)) {
                    $fields_model = self::getModel($action,'Field');
                    foreach ($params as $k => $v) {
                        $field = $fields_model->getFieldByName($action, $k);
                        $params[$k] = new shopAdvancedparamsPluginParam($field, $v);
                    }
                }
            }
        }
        return $params;
    }
    public static function getCategoryParams($category_id, $params = null) {
        return self::getParams('category', $category_id, $params);
    }
    public static function getProductParams($product_id, $params = null) {
        return self::getParams('product',$product_id,$params);
    }
    public static function getPageParams($page_id, $params = null) {
        return self::getParams('page',$page_id,$params);
    }
    
    public static function getParam($action, $action_id, $param_name = '') {
        $action_id = intval($action_id);
        $param = null;
        if(!empty($action_id) && !empty($param_name) && shopAdvancedparamsPlugin::actionExists($action)) {
            $params_model = new shopAdvancedparamsParamsModel($action);
            $fields_model = new shopAdvancedparamsFieldModel();
            
            $real_model = $params_model->getModel();
            $value = $real_model->getByField(array('name'=>$param_name,$real_model->getActionIdField() => $action_id));
                    $field = $fields_model->getFieldByName($action, $param_name);
            if(!empty($value) && !empty($field)) {
                $param = new shopAdvancedparamsPluginParam($field, $value['value']);
            } else {
                $param = new shopAdvancedparamsPluginParam();
            }
        }
        return $param;
    }
    public static function getProductParam($id, $param_name = '') {
        return self::getParam('product', $id, $param_name);
    }
    public static function getCategoryParam($id, $param_name = '') {
        return self::getParam('category', $id, $param_name);
    }
    public static function getPageParam($id, $param_name = '') {
        return self::getParam('page', $id, $param_name);
    }
    
    // Для вывода полной ссылки на файл
    public static function getFileUrl($url) {
       return shopAdvancedparamsPluginFiles::getFileUrl($url);
    }
    
}