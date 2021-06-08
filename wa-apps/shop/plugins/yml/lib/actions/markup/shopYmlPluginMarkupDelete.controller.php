<?php
class shopYmlPluginMarkupDeleteController extends waJsonController {
    public function execute(){
        $profile_id = waRequest::post('profile_id', 0, waRequest::TYPE_INT);
        $row_id     = waRequest::post('row_id', 0, waRequest::TYPE_INT);
        
        $path = wa()->getDataPath('plugins/yml/' . $profile_id . '/', true, 'shop') . 'markup.php';
        
        if ( file_exists($path) ){
            $markups = include($path);
            if ( $markups && isset($markups['steps'][$row_id]) ){
                unset($markups['steps'][$row_id]);
                waUtils::varExportToFile($markups, $path);
            }
        }
    }
}