<?php
return array(
    'shop_advancedparams_field' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 150, 'null' => 0),
        'title' => array('varchar', 255, 'null' => 0),
        'description' => array('text', 'null' => 0),
        'type' => array('varchar', 255, 'null' => 0),
        'size_type' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'width' =>  array('int', 11, 'unsigned' => 1, 'null' => 1),
        'height' =>  array('int', 11, 'unsigned' => 1, 'null' => 1),
        'action' => array('varchar', 50, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'UK_shop_adp_fld_name_action' => array('name', 'action', 'unique' => 1),
            'IDX_shop_adp_fld_action' => 'action',
        ),
    ),
    'shop_advancedparams_field_values' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'field_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'value' => array('text', 'null' => 0),
        'default' => array('tinyint', 4, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'IDX_shop_adp_fld_values_field_id' => 'field_id',
        ),
    ),
    'shop_advancedparams_param_file' => array(
        'action' => array('varchar', 50, 'null' => 0),
        'action_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 50, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('action', 'action_id', 'name'),
            'UK_shop_adp_par_file_act_act_id_name' => array('action_id', 'name', 'action', 'unique' => 1),
            'IDX_shop_adp_par_file_action_action_id' => array('action', 'action_id'),
        ),
    ),
    'shop_advancedparams_param_value' => array(
        'action' => array('varchar', 50, 'null' => 0),
        'action_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('action', 'action_id', 'name'),
            'UK_shop_adp_par_value_act_act_id_name' => array('action', 'action_id', 'name', 'unique' => 1),
            'IDX_shop_adp_par_value_act_act_id' => array('action', 'action_id'),
        ),
    ),
);
