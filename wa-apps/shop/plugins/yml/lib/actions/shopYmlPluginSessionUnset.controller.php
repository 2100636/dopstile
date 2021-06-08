<?php
class shopYmlPluginSessionUnsetController extends waJsonController {
    public function execute(){
        $profile_id = waRequest::post('profile_id', null, waRequest::TYPE_INT);
        
        if ( !is_null($profile_id) && ($profile_id >= 0) ) {
            $settings = shopYmlHelper::getProfileConfig($profile_id);
            $path = shopYmlHelper::sessionPath($profile_id, $settings['source_type']);
            if ( file_exists($path) ){
                waFiles::delete($path);
            }
        }
    }
}