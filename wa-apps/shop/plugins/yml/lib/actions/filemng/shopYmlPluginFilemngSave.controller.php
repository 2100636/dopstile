<?php
class shopYmlPluginFilemngSaveController extends waJsonController {
    public function execute() {

        $profile_id = waRequest::post('profile_id', 0, waRequest::TYPE_INT);
        $path  = waRequest::post("file", '', waRequest::TYPE_STRING_TRIM);

        if ( !$profile_id ){
            $model      = new waAppSettingsModel();
            $token      = array('shop', 'yml');
            $model->set($token, 'server.file', $path);
        } else {
            $settings = shopYmlHelper::getProfileConfig($profile_id);

            $settings['server.file'] = $path;

            $profiler  = new shopImportexportHelper('yml');
            $profiler->setConfig($settings,$profile_id);
        }
    }
}