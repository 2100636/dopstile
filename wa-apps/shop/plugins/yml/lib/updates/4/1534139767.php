<?php
$profiler      = new shopImportexportHelper('yml');
$profiles      =  $profiler->getList();

if ( empty($profiles) ){
    $profiles = array();
}

$profiles[0] = array();
foreach ( $profiles as $p_id => $p ){
    try {
        $path = wa()->getDataPath('plugins/yml/' . $p_id . '/', true, 'shop', false) . 'markup.php';
        if ( file_exists($path) ){
            $markup = include($path);

            if ( empty($markup['steps']) ){
                $m = array(
                    'steps' => $markup,
                    'default' => array(
                        'rate' => 0,
                        'type' => 1
                    )
                );

                waUtils::varExportToFile($m, $path);
            }
        }
    } catch (waException $e){
        //
    }
}