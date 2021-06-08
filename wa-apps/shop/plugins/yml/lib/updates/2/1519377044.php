<?php
$path     = wa()->getAppPath('plugins/yml/', 'shop');
$del_path = array('lib/actions/param/', 'templates/actions/param', 'lib/models/');

foreach ( $del_path  as $p ){
    try {
        $full_path = $path . $p;
        if ( file_exists($full_path) ){
            waFiles::delete($full_path,true);
        }
    } catch(waException $e){
        waLog::log($e->getMessage(), 'yml_update.log');
    }
}

$model = new waModel();

try {
    $db = waSystem::getInstance()->getConfig()->getDatabase();
    if ( !empty($db['database']) ){
        $sql = "SELECT count(*) AS ok FROM information_schema.TABLES WHERE (TABLE_SCHEMA = 's:database') AND (TABLE_NAME = 'shop_yml_params')";
        if ( $model->query($sql,array('database' => $db['database']))->fetchField('ok') ){
            $sql = "DROP TABLE `shop_yml_params`";
            $model->exec($sql);
        }
    }
} catch(waException $e){
    waLog::log($e->getMessage(), 'yml_update.log');
}

try{
    $model->query("SELECT yml_updated FROM `shop_product` LIMIT 1");
} catch(waException $e){
    $model->exec("ALTER TABLE `shop_product` ADD `yml_updated` INT NULL DEFAULT NULL");
}