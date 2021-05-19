<?php

class installerLicensesList
{
    const FILTER_TYPE = 'product_type';
    const FILTER_NAME = 'product_name';
    const FILTER_ORDER_ID = 'order_id';

    const FIELD_IS_INSTALLED = 'is_installed';
    const FIELD_REQUIREMENTS_WARNINGS = 'requirements_warnings';
    const FIELD_IS_BIND_AVAILABLE = 'is_bind_available';
    const FIELD_ALL = '*';  // all extra fields possible

    protected $count_per_page = 30;
    protected $count;
    protected $contact_id;
    protected $filters = [];
    protected $list;
    protected $product_types = [];
    protected $error;

    public function __construct(array $options = [])
    {
        $this->count_per_page = isset($options['count_per_page']) ? $this->getIntVal('count_per_page', $options, $this->count_per_page) : $this->count_per_page;

        $this->contact_id = isset($options['contact_id']) ? $this->getIntVal('count_id', $options, 0) : 0;
        if ($this->contact_id <= 0) {
            $this->contact_id = wa()->getUser()->getId();
        }
    }

    /**
     * @param string $field
     * @param string $op
     *      $=  - search as suffix
     *      ^=  - search as prefix
     *      *=  - search as substring
     *      ==  - same as '=' (equal)
     *      !=  - negation for '=' (not equal)
     *      >=  - greater or equal
     *      <=  - less or equal
     *      =   - equal
     *      >   - greater
     *      <   - less
     *      @=  - if $val is array than any value of array equal. SQL analog is operator IN()
     *      @$= - not supported yet - each value applied with $=
     *      @^= - not supported yet - each value applied with ^=
     *      @*= - not supported yet - each value applied with ^=
     * @param string $val
     */
    public function addFilter($field, $op, $val)
    {
        $this->filters[] = [
            'field' => $field,
            'op' => $op,
            'val' => $val
        ];
    }

    protected function applyFilters()
    {
        if (!is_array($this->list)) {
            return;
        }

        // apply filters
        foreach ($this->filters as $filter) {
            switch ($filter['field']) {
                case self::FILTER_TYPE:
                    foreach ($this->list as $index => $license) {
                        $product_type = $license['type'];
                        $type = $filter['val'];
                        // support only '=' for this filter
                        if ($type && $type !== $product_type) {
                            unset($this->list[$index]);
                        }
                    }
                    break;
                case self::FILTER_NAME:
                    foreach ($this->list as $index => $license) {
                        $name = $license['name'];
                        $query = $filter['val'];
                        switch ($filter['op']) {
                            case '^=':
                                // prefix
                                if (mb_stripos($name, $query) !== 0) {
                                    unset($this->list[$index]);
                                }
                                break;
                            case '*=':
                                // substring
                                if (mb_stripos($name, $query) === false) {
                                    unset($this->list[$index]);
                                }
                                break;
                            default:
                                // equality
                                if ($name !== $query) {
                                    unset($this->list[$index]);
                                }
                                break;
                        }
                    }
                case self::FILTER_ORDER_ID:
                    foreach ($this->list as $index => $license) {
                        $order_id = intval($license['order_id']);
                        $query = intval($filter['val']);
                        // support only '=' for this filter
                        if ($query > 0 && $query !== $order_id) {
                            unset($this->list[$index]);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * @param string $id
     * @param array|string $extend_by - list of FIELD_* constants, extended fields for each item in list OR === FIELD_ALL (special case)
     * @return array $result
     *      array|null      $result[0] - license
     *      string          $result[1] - error
     * @throws waException
     */
    public function getOne($id, $extend_by = [])
    {
        $api = new installerWebasystIDApi();
        $result = $api->getLicenses($this->contact_id, [
            'params' => [
                'id' => $id
            ]
        ]);

        if (!$result['status']) {
            $error = $this->formatFailApiResponse($result);
            return [
                null,
                $error,
            ];
        }

        $list = [];
        foreach ($result['details']['licenses'] as $license) {
            $list[$license['license_id']] = $license;
        }

        if ($extend_by) {
            $this->extend($list, $extend_by);
        }

        $one = reset($list);
        return [
            $one,
            '',
        ];
    }

    /**
     * Get list of entities as array
     * @param array $options
     *      int $options['page'] [optional]
     *          Get concrete page entities list
     *          If pass this option than offset/limit options will be ignored
     *
     *      int $options['offset'] [optional]
     *          If pagination isn't what is need, you can use classic limit-offset notation
     *          Offset of list ot get
     *
     *      int $options['limit'] [optional]
     *          If pagination isn't what is need, you can use classic limit-offset notation
     *          Limit is size chunk of list need to get
     *
     *      array  $options['extend_by'] [optional] - list of FIELD_* constants, extended fields for each item in list
     *      string $options['extend_by'] [optional] - === FIELD_ALL - special case
     *
     *      array $options['order_by'] [optional]
     *          List of order_by_item:
     *          - $options['order_by'][<index>][0] - field
     *          - $options['order_by'][<index>][1] [optional] - ASC|DESC
     *
     * @return array $result
     *      array   $result[0] - list
     *      string  $result[1] - error
     * @throws waException
     */
    public function getList(array $options = [])
    {
        if (is_array($this->list)) {
            return [
                $this->list,
                strval($this->error),
            ];
        }

        $api = new installerWebasystIDApi();
        $result = $api->getLicenses($this->contact_id);

        if (!$result['status']) {
            $this->list = [];
            $this->error = $this->formatFailApiResponse($result);
            return [
                $this->list,
                $this->error
            ];
        }

        $this->list = [];
        foreach ($result['details']['licenses'] as $license) {
            $this->list[$license['license_id']] = $license;
        }

        $types = waUtils::getFieldValues($this->list, 'type');
        $this->product_types = $types;

        if ($this->filters) {
            $this->applyFilters();
        }

        $this->count = count($this->list);

        // pagination
        if ($this->hasIntVal('page', $options)) {
            $limit_offset = $this->calcLimitOffsetByPage($options['page']);
            $this->list = array_slice($this->list, $limit_offset['offset'], $limit_offset['limit'], true);
        }

        // extending
        if ($this->hasArrayVal('extend_by', $options) || ($this->hasKey('extend_by', $options) && $options['extend_by'] === self::FIELD_ALL)) {
            $this->extend($this->list, $options['extend_by']);
        }

        return [
            $this->list,
            '',
        ];
    }

    public function getProductTypes()
    {
        $names = [
            'APP' => _w('Applications'),
            'THEME' => _w('Themes'),
            'PLUGIN' => _w('Plugins'),
            'WIDGET' => _w('Widgets')
        ];
        return waUtils::extractValuesByKeys($names, $this->product_types);
    }

    protected function formatFailApiResponse(array $response)
    {
        if (!$response['status']) {
            switch ($response['details']['error']) {
                case 'system_error':
                    $error = _w('System error');
                    break;
                default:
                    $error = $response['details']['error'] ? $response['details']['error'] : _w('Unknown error while trying to get licenses.');
                    break;
            }
            return $error;
        }
        return '';
    }


    /**
     * @throws waException
     */
    public function count()
    {
        if ($this->count === null) {
            $this->getList();
        }
        return $this->count;
    }

    /**
     * Calculate count of pages and returns
     * @param int $total_count
     * @return int
     */
    public function calcPagesCount($total_count)
    {
        return intval(ceil($total_count / $this->count_per_page));
    }

    protected function calcLimitOffsetByPage($page)
    {
        $offset = 0;
        $limit = null;

        if ($this->count_per_page !== null) {
            $limit = $this->count_per_page;
            if ($page < 1) {
                $page = 1;
            }
            $offset = ($page - 1) * $limit;
        }

        return [
            'offset' => $offset,
            'limit' => $limit
        ];
    }

    protected function extend(&$licenses, $extend_by)
    {
        if (!$licenses || !$extend_by) {
            return;
        }

        if ($extend_by === self::FIELD_ALL) {
            $extend_by = [
                self::FIELD_REQUIREMENTS_WARNINGS,
                self::FIELD_IS_INSTALLED,
                self::FIELD_IS_BIND_AVAILABLE,
            ];
        }

        foreach ($extend_by as $extend) {
            switch ($extend) {
                case self::FIELD_IS_INSTALLED:
                    $this->extendByInstalledFlags($licenses);
                    break;
                case self::FIELD_REQUIREMENTS_WARNINGS:
                    $this->extendByCheckedRequirements($licenses);
                    break;
                case self::FIELD_IS_BIND_AVAILABLE:
                    $this->extendByBindAvailableFlag($licenses);
                    break;
            }
        }
    }

    /**
     * Extend each license item with 'is_bind_available'
     * @param $licenses
     */
    protected function extendByBindAvailableFlag(&$licenses)
    {
        if (!$licenses) {
            return;
        }

        // test first item for check is list already extended
        $first = reset($licenses);
        if ($this->hasKey('is_bind_available', $first)) {
            return;
        }

        $now_time = time();

        foreach ($licenses as &$license) {
            $time = $license['bind_available_date'] ? strtotime($license['bind_available_date']) : 0;
            $license['is_bind_available'] = $time < $now_time;
        }
        unset($license);
    }

    /**
     * Extend each license item with 'is_installed' flag
     * @param $licenses
     * @throws waException
     */
    protected function extendByInstalledFlags(&$licenses)
    {
        if (!$licenses) {
            return;
        }

        // test first item for check is list already extended
        $first = reset($licenses);
        if ($this->hasKey('is_installed', $first)) {
            return;
        }

        foreach ($licenses as &$license) {
            $license['is_installed'] = false;
        }
        unset($license);

        $apps = [];
        $types = [];
        foreach ($licenses as $license) {
            $allow_to_rebind = !$license['bind_available_date'] || !$license['bound_to'];
            if ($allow_to_rebind) {
                $types[] = $license['type'];
                $apps[] = $license['app_id'];
            }
        }

        if (!$apps) {
            return;
        }

        $apps = array_unique($apps);

        // Installed apps (+ wa-plugins/payment, wa-plugins/shipping, wa-plugins/sms)
        $assets = installerHelper::getInstaller()->getApps([
            'installed' => true,
            'system' => true
        ]);

        // Get extras only required types
        $types = array_unique($types);
        $types = waUtils::extractValuesByKeys(['PLUGIN' => 'plugins', 'WIDGET' => 'widgets', 'THEME' => 'themes'], $types);

        $options = array(
            'local' => true,
            'status' => false,
            'installed' => true,
            'system' => true,   // system plugins
        );

        foreach ($types as $type) {
            $extras = installerHelper::getInstaller()->getExtras($apps, $type, $options);
            foreach ($extras as $app_id => $extras_item) {
                if (isset($assets[$app_id]) && !empty($extras_item[$type])) {
                    unset($assets[$app_id][$type]);
                    $assets[$app_id] += $extras_item;
                }
            }
        }

        foreach ($licenses as &$license) {
            $app_id = $license['app_id'];
            $ext_id = $license['ext_id'];

            $is_installed = false;
            if ($license['type'] === 'APP') {
                $is_installed = isset($assets[$app_id]);
            } elseif ($license['type'] === 'PLUGIN') {
                $is_installed = isset($assets[$app_id]['plugins'][$ext_id]);
            } elseif ($license['type'] === 'WIDGET') {
                $is_installed = isset($assets[$app_id]['widgets'][$ext_id]);
            } elseif ($license['type'] === 'THEME') {
                $is_installed = isset($assets[$app_id]['themes'][$ext_id]);
            }

            $license['is_installed'] = $is_installed;
        }
        unset($license);
    }

    /**
     * Extend license item with 'requirements_warnings' of type string[]
     * @param $licenses
     */
    protected function extendByCheckedRequirements(&$licenses)
    {
        if (!$licenses) {
            return;
        }

        // test first item for check is list already extended
        $first = reset($licenses);
        if ($this->hasKey('requirements_warnings', $first)) {
            return;
        }

        $product_requirements = [];
        foreach ($licenses as $license) {
            $requirements = $license['requirements'];
            $product_id = $license['product_id'];
            $product_requirements[$product_id] = [
                'product_id' => $product_id,
                'requirements' => $requirements
            ];
        }

        $product_requirements = array_values($product_requirements);

        $checker = new installerRequirementsChecker($product_requirements);
        $warnings = $checker->check();

        foreach ($licenses as &$license) {
            $product_id = $license['product_id'];
            $license['requirements_warnings'] = [];
            if (isset($warnings[$product_id])) {
                $license['requirements_warnings'] = $warnings[$product_id];
            }
        }
        unset($license);
    }

    /**
     * Get integer value by key $name from associative array $array
     * @param string $name
     * @param array $array
     * @param int $default
     * @return int
     */
    protected function getIntVal($name, array $array, $default = 0) {
        if (isset($array[$name]) && wa_is_int($array[$name])) {
            return intval($array[$name]);
        }
        return $default;
    }

    /**
     * Has value that is integer by key $name in associative array $array
     * @param string $name
     * @param array $array
     * @return bool
     */
    protected function hasIntVal($name, array $array)
    {
        return isset($array[$name]) && wa_is_int($array[$name]);
    }

    protected function hasArrayVal($name, array $array)
    {
        return isset($array[$name]) && is_array($array[$name]);
    }

    protected function hasKey($name, array $array)
    {
        return array_key_exists($name, $array);
    }
}
