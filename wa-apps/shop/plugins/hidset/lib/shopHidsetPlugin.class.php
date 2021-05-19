<?php

class shopHidsetPlugin extends shopPlugin
{

    public $hsets;
    public $plugins;

    public function __construct($info)
    {
        parent::__construct($info);

        $this->hsets = array(
            'products_per_page' => array(
                'type' => 'int',
                'desc' => 'Количество товаров на одной странице/за одну загрузку lazyloading в бэкенде'
            ),
            'reviews_per_page_total' => array(
                'type' => 'int',
                'desc' => 'Количество отзывов на одной странице/за одну загрузку lazyloading в бэкенде в разделе Товары - Отзывы'
            ),
            'reviews_per_page_product' => array(
                'type' => 'int',
                'desc' => 'Количество отображаемых отзывов о продукте на витрине магазина'
            ),
            'review_highlight_time' => array(
                'type' => 'int',
                'desc' => 'Время "подсветки" отзыва о товаре как нового. В секундах'
            ),
            'products_default_view' => array(
                'type' => 'select',
                'desc' => 'Вид списков товаров по умолчанию для нового пользователя бекэнда. Если пользователь уже заходил в бекэнд и переключал режимы просмотра товаров, данная опция игнорируется',
                'value' => array(
                    'thumbs',
                    'table',
                    'skus'
                )
            ),
            'product_orders_per_page' => array(
                'type' => 'int',
                'desc' => 'Количество заказов отображаемых по умолчанию на вкладке Последние заказы в режиме просмотра товара в бекэнде Магазина'
            ),
            'types_per_page' => array(
                'type' => 'int',
                'desc' => ''
            ),
            'features_per_page' => array(
                'type' => 'int',
                'desc' => ''
            ),
            'features_values_per_page' => array(
                'type' => 'int',
                'desc' => ''
            ),
            'statrows_per_page' => array(
                'type' => 'int',
                'desc' => 'Количество строк отображаемых по умолчанию и подгружаемых по ссылке Показать еще в разделе Отчеты'
            ),
            'orders_update_list' => array(
                'type' => 'int',
                'desc' => 'Частота обновления списка заказов в бекэнде (в милисекундах)'
            ),
            'stocks_log_items_per_page' => array(
                'type' => 'int',
                'desc' => 'Количество строк отображаемых на вкладке товара Журнал изменения остатков за одну загрузку lazyloading в бэкенде'
            ),
            'marketing_expenses_per_page' => array(
                'type' => 'int',
                'desc' => 'Количество строк отображаемых по умолчанию в отчете Затраты на маркетинг'
            ),
            'customers_per_page' => array(
                'type' => 'int',
                'desc' => 'Количество отображаемых покупателей на одной странице/за одну загрузку lazyloading в бэкенде'
            ),
            'can_use_smarty' => array(
                'type' => 'select',
                'desc' => 'Возможность использовать Smarty в описаниях товаров и на информационных страницах. TRUE - включено. FALSE - выключено',
                'value' => array(
                    'true',
                    'false'
                )
            ),
            'orders_per_page' => array(
                'type' => 'array',
                'desc' => 'Количество заказов отображаемых в разделе Заказы магазина при различных режимах просмотра / за одну загрузку lazyloading',
                'value' => array(
                    array('name' => 'split', 'type' => 'int'),
                    array('name' => 'table', 'type' => 'int')
                )
            ),
            'filters_features' => array(
                'type' => 'select',
                'desc' => 'Метод наложения фильтров. Изменять только в случае если при использовании join сильно нагружается сервер',
                'value' => array(
                    'join',
                    'exists'
                )
            ),
        );
        $this->plugins = [];
        $plugins = ['yandexmarket'];
        $shop_plugins = wa()->getConfig()->getPlugins();
        foreach ($plugins as $plugin_id) {
            $data = null;
            if (!isset($shop_plugins[$plugin_id])) continue;
            switch ($plugin_id) {
                case 'yandexmarket':
                    $data = array(
                        'products_per_request' => array(
                            'type' => 'int',
                            'desc' => 'Количество обрабатываемых товаров за один "шаг" экспорта'
                        )
                    );
                    break;
            }
            if ($data) {
                $this->plugins[$plugin_id] = $data;
            }
        }
    }

    public function getPluginConfig($plugin_id)
    {
        $config = null;
        $files = array(
            wa('shop')->getAppPath('plugins/' . $plugin_id, 'shop') . '/lib/config/config.php', // defaults
            wa('shop')->getConfigPath('shop/plugins/' . $plugin_id) . '/config.php', // custom
        );
        $config = array();
        foreach ($files as $file_path) {
            if (file_exists($file_path)) {
                $config = include($file_path);
                if ($config && is_array($config)) {
                    foreach ($config as $name => $value) {
                        $config[$name] = $value;
                    }
                }
            }
        }
        return $config;
    }
}
