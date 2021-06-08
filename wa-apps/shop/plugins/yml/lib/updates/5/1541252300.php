<?php
$profiler      = new shopImportexportHelper('yml');
$profiles      =  $profiler->getList();

if ( empty($profiles) ){
    $profiles = array();
}

$profiles[0] = array(1);
foreach ( $profiles as $p_id => $p ){
    try {
        $p_id = (int) $p_id;

        $settings = array();
        if ( $p_id ){
            $config = $profiler->getConfig($p_id);
            $settings = !empty($config['config']) ? $config['config'] : array();
        } else {
            $token      = array('shop', 'yml');
            $model      = new waAppSettingsModel();
            $settings = $model->get($token);
        }

        if ( empty($settings) ){
            continue;
        }

       $settings['source_type'] = 'remote';

        if ( !$p_id ){
            foreach ( $settings as $k => $v ){
                $model->set($token, $k, $v);
            }
        } else {
            $profiler->setConfig($settings,$p_id);
        }

       $path = wa('shop')->getDataPath('plugins/yml/' . $p_id . '/', true, 'shop', false);
       $pr = $path . $p_id . '.map.php';
       if ( file_exists($pr) ){
           waFiles::delete($pr,true);
       }

       $pr = $path . $p_id . '.snapshot.php';
       if ( file_exists($pr) ){
           waFiles::delete($pr);
       }

       $pr = $path . $p_id . '.xml';
       if ( file_exists($pr) ){
           waFiles::move($pr, $path . 'remote/' . $p_id . '.remote.yml');
       }
    } catch (Exception $e) {

    }
}