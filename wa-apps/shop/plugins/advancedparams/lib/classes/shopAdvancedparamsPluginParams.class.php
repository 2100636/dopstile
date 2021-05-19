<?php

/**
 * Class shopAdvancedparamsPluginParams
 */
class shopAdvancedparamsPluginParams {

    /**
     * @var string
     */
    protected $action = '';
    /**
     * @var null|shopAdvancedparamsParamsModel
     */
    protected $params_model = null;
    /**
     * @var null|shopAdvancedparamsParamValueModel
     */
    protected $param_value_model = null;
    /**
     * @var null|shopAdvancedparamsParamFileModel
     */
    protected $param_file_model = null;
    /**
     * @var int
     */
    protected $action_id = 0;

    /**
     * shopAdvancedparamsPluginParams constructor.
     * @param string $action
     */
    public function __construct($action = '') {
        if(shopAdvancedparamsPlugin::actionExists($action)) {
            $this->action = $action;
            $this->params_model = new shopAdvancedparamsParamsModel($this->action);
            $this->param_value_model = new shopAdvancedparamsParamValueModel($this->action);
            $this->param_file_model = new shopAdvancedparamsParamFileModel($this->action);
        }
    }

    /**
     * Сохраняет параметры экшена
     * @param $action_id
     * @access public
     * @param array $params
     */
    public function saveParams($action_id, $params = array(), $ignore_active = false) {
        if(!is_array($params))  {
            $params = array();
        }
        foreach($params as $k => $v) {
            if(is_array($v)) {
                if(!empty($v['name']) && !isset($params[$v['name']])){
                    $params[$v['name']] = $v['value'];
                }
                unset($params[$k]);
            }
        }
        $method = 'save'.ucfirst($this->action).'Params';
        if(method_exists($this,$method)) {
            $this->$method($action_id,$params, $ignore_active);
        }
    }

    /**
     * Удаляет параметры экшена
     * @access public
     * @param $action_id
     */
    public function deleteParams($action_data) {
        $method = 'delete'.ucfirst($this->action).'Params';
        if(method_exists($this, $method)) {
            $this->$method($action_data);
        }
    }

    /**
     * Сохраняет параметры страницы
     * @access protected
     * @param $action_id
     * @param $params
     */
    protected function savePageParams($action_id, $params = array(), $ignore_active = false){
        $page_params = waRequest::post('params');
        if(is_array($params)) {
            $params = array_merge($page_params, $params);
        } else {
            $params = $page_params;
        }
        if(is_array($params)) {
            $og = waRequest::post('og');
            foreach ($og as $k => $v) {
                if ($v) {
                    $params['og_'.$k] = $v;
                }
            }
            $this->params_model->set($action_id, $params, $ignore_active);
        }
    }

    /**
     * Сохраняет параметры продукта
     * @access protected
     * @param $action_id
     * @param $params
     */
    protected function saveProductParams($action_id, $params, $ignore_active = false){
        $this->params_model->set($action_id, $params, $ignore_active);
    }

    /**
     * Сохраняет параметры категории
     * @access protected
     * @param $action_id
     * @param $params
     */
    protected function saveCategoryParams($action_id, $params, $ignore_active = false) {
        $enable_sorting = waRequest::post('enable_sorting');
        if(!empty($enable_sorting)) {
            $params['enable_sorting'] = $enable_sorting;
        }
        $this->params_model->set($action_id, $params, $ignore_active);
    }

    /**
     * Удаляет параметры страницы
     * @access protected
     * @param $action_id
     */
    protected function deletePageParams($action_data) {
        $pages_ids = $action_data['child_ids'];
        $pages_ids[] =$action_data['page']['id'];
        foreach ($pages_ids as $page_id) {
            $this->params_model->deleteByActionId($page_id);
            $this->param_file_model->deleteByActionId($page_id);
        }
    }

    /**
     * Удаляет параметры продукта
     * @access protected
     * @param $action_id
     */
    protected function deleteProductParams($action_id) {
        $this->params_model->deleteByActionId($action_id);
        $this->param_file_model->deleteByActionId($action_id);
    }

    /**
     * Удаляет параметры категории
     * @access protected
     * @param $action_id
     */
    protected function deleteCategoryParams($action_id) {
        $this->params_model->deleteByActionId($action_id);
        $this->param_file_model->deleteByActionId($action_id);
    }
}