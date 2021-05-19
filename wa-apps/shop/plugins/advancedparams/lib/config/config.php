<?php

return array(
    // Типы полей
    'field_types' => array(
        'input'    => 'Однострочное текстовое поле (Input)',
        'select'   => 'Выбор из вариантов (Select)',
        'radio'   => 'Выбор из вариантов (Radio кнопки)',
        'textarea' => 'Многострочное текстовое поле (Textarea)',
        'html'  => ' Поле с визуальным редактором',
        'image'    => 'Изображение',
        'file'   => 'Произвольный файл'
    ),
    // Название переменных фронтенда
    'action_variable' => array(
        'category' => '$category.params',
        'product' =>  '$product.params',
        'page' =>  '$page',
    ),
    
    // Типы экшенов
    'action_types' => array(
        'category' => 'Категории',
        'product' => 'Продукты',
        'page' => 'Страницы'
    ),
    // Типы полей с выбираемыми значениями
    'field_types_selectable' => array(
        'select' => true,
         'radio'   => true
    ),
    // Типы полей многострочные, для дополнительного сохранения исходных значений
    'field_types_persistent' => array(
        'textarea' =>true,
        'html' =>true,
    ),
    // Типы полей файлов
    'field_types_file' => array(
        'image' => true,
        'file' => true
    ),
    // Зарезервированные имена полей системой, будут запрещены для создания
    'banned_fields'=> array(
        'page' => array(
            'id'=> true,
            'parent_id'=> true,
            'domain'=> true,
            'route'=> true,
            'name'=> true,
            'title'=> true,
            'url'=> true,
            'full_url'=> true,
            'content'=> true,
            'create_datetime'=> true,
            'update_datetime'=> true,
            'create_contact_id'=> true,
            'sort'=> true,
            'status'=> true,
            'keywords' => true,
            'description' => true,
            'og_title' => true,
            'og_image' => true,
            'og_video' => true,
            'og_description' => true,
            'og_type' => true
        )
    )
);