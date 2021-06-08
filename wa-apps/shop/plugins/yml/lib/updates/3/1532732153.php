<?php
$profiler      = new shopImportexportHelper('yml');
$profiles      =  $profiler->getList();

if ( empty($profiles) ){
    $profiles = array();
}

$profiles[0] = array();
foreach ( $profiles as $p_id => $p ){
    try {
        $path = wa()->getDataPath('plugins/yml/' . $p_id . '/', true, 'shop', false) . $p_id . '.map.php';
        if ( file_exists($path) ){
            $map = include($path);

            $_map = array();
            if ( $map ){
                foreach ( $map as $tag_key => $tag_data ){
                    if ( !empty($tag_data['type']) ){
                        $_map[$tag_key] = array( 'type' => $tag_data['type'], 'up' => 1);
                    }

                    if ( !empty($tag_data['attrs']) ){
                        foreach ($tag_data['attrs'] as $attr_id => $attr_data){
                            $attr_key = $tag_key . ':a:' . $attr_id;
                            $attr_data['up'] = 1;
                            $_map[$attr_key] = $attr_data;
                        }
                    }
                }

                if ( $_map ){
                    waUtils::varExportToFile($_map, $path);
                }
            }
        }
    } catch (waException $e){

    }
}