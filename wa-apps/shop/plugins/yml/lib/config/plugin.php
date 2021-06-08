<?php
return array(
    'name'          => 'Импорт товаров из YML',
    'version'       => '3.2.1',
    'importexport'  => true,
    'img'           => 'img/yml_icon.png',
    'vendor'        => '1052040',
    'handlers' => array(
        'backend_product'         => 'external_id',
        'backend_products'        => 'backendProducts',
        'backend_category_dialog' => 'category_external_id',
        'products_collection'     => 'productsCollection',
        'category_save'           => 'categorySave'
    )
);
