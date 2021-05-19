<?php

class installerLicensesAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setLayout(new installerBackendStoreLayout());
    }

    public function execute()
    {
        $list = new installerLicensesList([ 'contact_id' => $this->getUserId() ]);

        $page = $this->getPage();

        $type_filter = $this->getProductTypeFilter();
        if (strlen($type_filter['val']) > 0) {
            $list->addFilter(installerLicensesList::FILTER_TYPE, $type_filter['op'], $type_filter['val']);
        }

        $product_name_filter = $this->getProductNameFilter();
        if (strlen($product_name_filter['val']) > 0) {
            $list->addFilter(installerLicensesList::FILTER_NAME, $product_name_filter['op'], $product_name_filter['val']);
        }

        $order_id_filter = $this->getOrderIDFilter();
        if ($order_id_filter['val'] > 0) {
            $list->addFilter(installerLicensesList::FILTER_ORDER_ID, $order_id_filter['op'], $order_id_filter['val']);
        }

        list($licenses, $error) = $list->getList([
            'page' => $page,
            'extend_by' => installerLicensesList::FIELD_ALL
        ]);

        $total_count = $list->count();
        $product_types = $list->getProductTypes();

        $this->view->assign([
            'total_count' => $total_count,
            'pages_count' => $list->calcPagesCount($total_count),
            'product_types' => $product_types,
            'licenses' => $licenses,
            'error' => $error,
            'current_product_type' => $type_filter['val'],
            'product_name_filter' => $product_name_filter,
            'order_id_filter' => $order_id_filter,
        ]);
    }

    protected function getPage()
    {
        // prepare pagination params
        $page = $this->getRequest()->get('page', 1, waRequest::TYPE_INT);
        if ($page < 1) {
            $page = 1;
        }
        return $page;
    }


    /**
     * Get filter by product type
     * @return array $filter
     *      string $filter['val']
     *      string $filter['op'] - '='
     */
    protected function getProductTypeFilter()
    {
        $val = $this->getRequest()->get('type', '', waRequest::TYPE_STRING_TRIM);
        return [
            'val' => $val,
            'op' => '=',
        ];
    }

    /**
     * Get filter by product name
     * @return array $filter
     *      string $filter['val']
     *      string $filter['op'] - '=' | '*='
     */
    protected function getProductNameFilter()
    {
        $name = $this->getRequest()->get('name', [], waRequest::TYPE_ARRAY);
        $name = waUtils::extractValuesByKeys($name, ['val', 'op'], false, '');
        if ($name['op'] !== '*=') {
            $name['op'] = '=';
        }
        return $name;
    }

    /**
     * Get filter by order ID
     * @return array $filter
     *      int $filter['val']
     *      string $filter['op'] - '='
     */
    protected function getOrderIDFilter()
    {
        $val = $this->getRequest()->get('order_id', 0, waRequest::TYPE_INT);
        return [
            'val' => $val,
            'op' => '='
        ];
    }
}
