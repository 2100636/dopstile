<?php
return array(
    'menu_item' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'left_key' => array('int', 11, 'null' => 0, 'default' => 0),
        'right_key' => array('int', 11, 'null' => 0, 'default' => 0),
        'parent_id' => array('int', 11, 'null' => 0),
        'depth' => array('int', 11, 'null' => 0, 'default' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'type' => array('varchar', 32, 'null' => 0, 'default' => 'link'),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
            'ns_keys' => array('left_key', 'right_key'),
            'parent_id' => 'parent_id',
            'status' => 'status',
            'type' => 'type',
        ),
    ),
    'menu_item_params' => array(
        'item_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('item_id', 'name'),
            'name' => 'name',
        ),
    ),
);
