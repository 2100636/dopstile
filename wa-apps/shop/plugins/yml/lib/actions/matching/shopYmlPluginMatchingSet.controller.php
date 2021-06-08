<?php
class shopYmlPluginMatchingSetController extends waJsonController {
    public function execute(){
        $foreign_id = waRequest::post('foreign_id', 0, waRequest::TYPE_STRING_TRIM);
        $local_id   = waRequest::post('local_id', 0, waRequest::TYPE_INT);
        $mode       = waRequest::post('mode', 0, waRequest::TYPE_INT);
        $ids        = waRequest::post('ids', array(), waRequest::TYPE_ARRAY);
        $profile_id = waRequest::post("profile_id", 0, waRequest::TYPE_INT);
        
        $path = wa()->getDataPath('plugins/yml/' . $profile_id, false, 'shop') . '/categ_map.php';
        

        $data = array();
        if ( file_exists($path) ){
            $data = include($path);
        }
        
        if ( $mode === 0){
            if ( array_key_exists($foreign_id, $data) ){
                unset($data[$foreign_id]);
            }            
            if ( !empty($ids) && !empty($data) ){
                foreach ($ids as $id){
                    if ( array_key_exists($id, $data) ){
                        unset($data[$id]);
                    }
                }
            }
            waUtils::varExportToFile($data, $path);
            return true;
        }
        
        if ( empty($data) || !is_array($data) ){
            $data = array();
        }

        if ( $foreign_id ){
            $data[$foreign_id] = array(
                'id'   => $local_id,
                'mode' => $mode
            );

            if ( !empty($ids) ){
                foreach ( $ids as $id ){
                    $data[$id]['id']   = $local_id;
                    $data[$id]['mode'] = 2;
                }
            }
            
            waUtils::varExportToFile($data, $path);
        }
    }
}