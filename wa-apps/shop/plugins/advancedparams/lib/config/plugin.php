<?php

return array(
    'name' => 'Управление Доп. параметрами',
    'description' => 'Формирует поля из дополнительных параметров',
    'vendor' => '990614',
    'author'=>'Genasyst',
    'version' => '1.2.4',
    'img' => 'img/advancedparams.png',
    'shop_settings' => true,
    'custom_settings' => true,
    'handlers' => array(
        'frontend_products'=>'frontendProducts',
        'backend_product' => 'backendProduct',
        'product_save' => 'productSave',
        'product_delete' => 'productDelete',
        
        'backend_category_dialog' =>'backendCategoryDialog',
        'category_save' => 'categorySave',
        'category_delete'=>'categoryDelete',

        'backend_page_edit' =>'backendPageEdit',
        'page_edit' => 'PageEdit',
        'page_save' =>'pageSave',
        'page_delete' =>'pageDelete',
        
        'backend_products' => 'backendProducts'
    ),
);
//EOF