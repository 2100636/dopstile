<?php
class shopYmlPluginFilemngDeleteController extends waJsonController {
    public function execute(){
        $profile_id = waRequest::post('profile_id', 0, waRequest::TYPE_INT);
        $type       = waRequest::post('type');

        if ( !$type ){
            die;
        }

        $settings = shopYmlHelper::getProfileConfig($profile_id);

        $changed = false;
        if ( $type === 'server' ){
            $settings['server.file'] = null;
            $changed = true;
        } elseif ( $type === 'local' ){
            $s = $settings['source_type'];
            $settings['source_type'] = 'local';
            $path = shopYmlHelper::ymlPath($profile_id, $settings);
            $settings['source_type'] = $s;
            waFiles::delete($path);
            $settings['local.file'] = null;
            $settings['local.file.size'] = null;
            $changed = true;
        }

        if ( $changed ){
            shopYmlHelper::saveProfileConfig($profile_id, $settings);
        }
    }
}