<?php
$model          = new waModel();
$settings_model = new waAppSettingsModel();

$sync_id        = array( 'shop' , 'yml' );

$profile_name   = wa()->getConfig()->getGeneralSettings('name');
if ( !$profile_name ){
    $profile_name = 'Профиль 1';
}

$data = array(
    'name'                    => $profile_name,
    'price_up'                => 0,
    'category'                => 3,
    'images'                  => 3,
    'convert_prices'          => 1,
    'skip_another_profiles'   => 0,
    'import_categories'       => 1,
    'automatization'          => 1,
    'reset_stocks'            => 1,
    'searchbyname'            => 0,
    'limit_stream'            => 0,
    'stream_value'            => 40,
    'weight_unit'             => 'g',
    'hide_excluded'           => 1,
    'bind_to_profile'         => 0,
    'product_names'           => 1,
    'duplicate_as_child'      => 0,
    'no_update_products'      => 0,
    'no_new_products'         => 0,
    'no_new_categs'           => 0,
    'no_update_categs'        => 0,
    'enforce_protocol'        => 0,
    'restore'                 => 1,
    'enforce_protocol_option' => 2,
    'round'                   => 0,
    'source_type'             => 'remote',
    'allow_categ_map'         => 0
);

foreach ( $data as $key => $value ){
    $settings_model->set( $sync_id , $key , $value );
}

foreach ( array('shop_product', 'shop_category') as $table ) {
    foreach ( array('yml_id', 'yml_profile_id', 'yml_updated') as $field ){
        try {
            $model->query("SELECT `{$field}` FROM `{$table}` WHERE 0");
        } catch(waDbException $e) {
            $model->exec("ALTER TABLE `{$table}` ADD `{$field}` " . ($field == 'yml_id' ? "VARCHAR(255)" : "INT NULL") .  " DEFAULT NULL");
        }
    }
}

try {
    $model->query("SELECT `yml_up` FROM `shop_product_skus` WHERE 0");
} catch(waDbException $e) {
    $model->exec("ALTER TABLE `shop_product_skus` ADD `yml_up` INT NULL DEFAULT 0");
}