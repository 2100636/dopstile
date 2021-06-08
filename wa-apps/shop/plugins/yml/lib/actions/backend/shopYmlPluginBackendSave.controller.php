<?php
class shopYmlPluginBackendSaveController extends waJsonController{
    public function execute(){
        $profile_id = waRequest::post('profile_id', 0, waRequest::TYPE_INT);
        $settings   = waRequest::post('settings', array() , waRequest::TYPE_ARRAY);
        
        $checkboxes = array(
            'no_update_products',
            'http_auth',
            'no_new_products',
            'no_new_categs',
            'allow_categ_map',
            'restore',
            'limit_stream',
            'no_update_categs',
            'searchbyname',
            'reset_stocks',
            'searchbysku',
            'duplicate_as_child',
            'import_categories',
            'hide_excluded',
            'automatization',
            'bind_to_profile',
            'automatization',
            'enforce_protocol'
        );
        
        foreach ( $checkboxes as $field ){
            $settings[$field] = ifempty($settings[$field], 0);
        }
        
        if ( empty($profile_id) ){
            $model      = new waAppSettingsModel();
            $token      = array('shop', 'yml');
            foreach ( $settings as $key => $value ) {
                $model->set($token, $key , $value );
            }
        } else {
            $profiler  = new shopImportexportHelper('yml');
            $profiler->setConfig($settings,$profile_id);
            
            if ( !empty($settings['name']) ){
                $importexport_model = new shopImportexportModel();
                $data = array( 'name' => $settings['name'] );
                
                $search_by = array(
                    'id'     => $profile_id,
                    'plugin' => 'yml',
                );
                
                $importexport_model->updateByField($search_by, $data );
            }
        }
        
        if ( ($settings['markup_type'] == '2') && ($markup = waRequest::post('stepped_markup', array(),waRequest::TYPE_ARRAY)) ){
            $path = wa()->getDataPath('plugins/yml/' . $profile_id . '/', true, 'shop') . '/markup.php';

            $m = array(
                'steps'   => $markup,
                'default' => waRequest::post('stepped_default', array(), waRequest::TYPE_ARRAY)
            );

            waUtils::varExportToFile($m,$path);
        }
    }
}