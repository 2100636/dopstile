<?php
class shopYmlSyncFileCli extends waCliController {
    public function execute(){
        $profile_id = waRequest::param('profile', 0, waRequest::TYPE_INT);
        $settings   = array();
        
        if ( $profile_id ){
            $profiler   = new shopImportexportHelper('yml');
            $settings   = $profiler->getConfig($profile_id);
            if ( !empty($settings['config']) ){
                $settings = $settings['config'];
            }
        } else {
            $settings  = wa('shop')->getPlugin('yml')->getSettings();
        }
        
        if ( !$settings ){
            throw new waException('Given profile ID doesn\'t contain any settings');
        }
        
        if ( empty($settings['automatization']) ){
            echo 'Disabled';
            return false;
        }
        
        $_POST['profile_id'] = $profile_id;
        
        $runner = new shopYmlPluginSyncRunController();
        $result = $runner->quietExecute($profile_id);
        if (!empty($result['success'])) {
            print($result['success']);
        } elseif (!empty($result['error'])) {
            print($result['error']);
        } elseif (!empty($result['warning'])) {
            print($result['warning']);
        }
        print "\n";
    }
}