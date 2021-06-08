<?php
$model = new waModel();

foreach ( array( 'shop_product', 'shop_category' ) as $table ) {
    foreach (array('yml_id', 'yml_profile_id', 'yml_updated') as $field ){
        try {
            $model->exec("ALTER TABLE `{$table}` DROP COLUMN `" . $field . "`");
        } catch(waDbException $e) {

        }
    }
}

foreach ( array(true,false) as $t ){
    try {
        $path = wa()->getDataPath('plugins/yml', $t, 'shop', false);
        if ( file_exists($path) ) {
            waFiles::delete($path, true);
        }
    } catch(waException $e){

    }
}


try {
    $model->exec("DROP TABLE IF EXISTS `shop_yml_params`");
} catch (waDbException $e){
    //
}