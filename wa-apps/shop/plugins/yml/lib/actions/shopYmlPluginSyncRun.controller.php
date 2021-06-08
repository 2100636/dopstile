<?php
class shopYmlPluginSyncRunController extends waLongActionController {

    /**
     * @var shopProductModel
     */
    private $product_model;

    /**
     * @var shopCategoryModel
     */
    private $category_model;

    /**
     * @var shopProductImagesModel
     */
    private $productImagesModel;

    /**
     * @var shopFeatureValuesModel
     */
    private $values_model;

    private $markup = array();

    /**
     * @var XMLReader
     */
    public $reader;

    private $cli = false;

    const STAGE_PRODUCT         = 'offers';
    const STAGE_CATEGORY        = 'categories';
    const DEFAULT_TYPE_ID       = 1;

    private $stage_labels = array(
        self::STAGE_CATEGORY => 'Обновление категорий',
        self::STAGE_PRODUCT  => 'Синхронизация товаров',
    );

    static $nodes = array(
        self::STAGE_CATEGORY => 'category',
        self::STAGE_PRODUCT  => 'offer'
    );

    public static $selectable = array(
        'Цвет','Размер'
    );

    static $types = array(
        XMLReader::CDATA       => 1, XMLReader::TEXT        => 1,
        XMLReader::ELEMENT     => 1, XMLReader::END_ELEMENT => 1
    );

    static $text_types = array(
        XMLReader::CDATA      => 1,
        XMLReader::TEXT       => 1
    );

    static $tPvm = array('typePrefix' => 1, 'vendor' => 1, 'model' => 1);

    static $depth = 3;

    private $_cache = array();

    static $stocks = array();

    const IMAGES_UPLOAD_DELOLD      = '1';
    const IMAGES_UPLOAD_NEWOLD      = '2';
    const IMAGES_UPLOAD_NEWPROD     = '3';
    const IMAGES_UPLOAD_NONE        = '4';

    const SET_CATEGORY_ALL      = 1;
    const SET_CATEGORY_APPEND   = 2;
    const SET_CATEGORY_NEW      = 3;
    const CATEGORY_NONE         = 4;

    const TYPE_ARBITRARY = 2;
    const TYPE_SIMPLE    = 1;

    public function execute(){
        try {
            parent::execute();
        } catch  ( waException $Ex ) {
            $message = $Ex->getMessage();
            waLog::log($message, 'yml.log');
            if ( $Ex->getCode() == '302' ) {
                echo json_encode( array('warning' => $message) );
            } else {
                echo json_encode( array('error' => $message) );
            }
        }
    }

    public function putCategMap(){
        if ( !empty($this->data['settings']['allow_categ_map']) ){
            $cache_path = wa()->getDataPath('plugins/yml/' . $this->data['profile_id'], false, 'shop') . '/categ_map.php';
            $result = array();

            if ( file_exists($cache_path) ){
                $result = include($cache_path);

                if ( empty($result) ){
                    $result = array();
                }
            }

            $this->_cache['categs'] = $result;
        }
    }

    public function setCategMap(){
        if ( !empty($this->_cache['categs']) ){
            $cache_path = wa()->getDataPath('plugins/yml/' . $this->data['profile_id'], false, 'shop') . '/categ_map.php';
            waUtils::varExportToFile($this->_cache['categs'], $cache_path);
        }
    }

    protected function restore() {
        $this->initHelpers();
        $this->initReader();

        $this->gc_collect = function_exists('gc_collect_cycles');
        foreach (array('features', 'f_names', 'categories', 'categories_map', 'values') as $k ){
            $this->_cache[$k] = array();
        }

        $this->putCategMap();

        $stock_model  = new shopStockModel();
        $stocks       = $stock_model->select('id')->fetchAll();

        if ( $stocks ){
            foreach ( $stocks as $s ){
                self::$stocks[] = $s['id'];
            }
            unset($stocks);
        }

        if ( $this->data['settings']['markup_type'] == '2' ){
            $path = wa()->getDataPath('plugins/yml/' . $this->data['profile_id'] . '/', true, 'shop') . 'markup.php';
            if ( file_exists($path) ){
                $this->markup = include($path);
                if ( !empty($this->markup['steps']) ){
                    foreach ( $this->markup['steps'] as &$m ){
                        $m['limit'] = (double) str_replace(',', '.', $m['limit']);
                        $m['rate']  = (double) str_replace(',', '.', $m['rate']);
                    }
                    unset($m);

                    if ( !empty($this->markup['default']) ){
                        $this->markup['default']['rate'] = (double) str_replace(',', '.', $this->markup['default']['rate']);
                    }
                }
            }
        }
    }

    private function initHelpers(){
        $this->product_model       = new shopProductModel();
        $this->feature_model       = new shopFeatureModel();
        $this->category_model      = new shopCategoryModel();
        $this->productImagesModel  = new shopProductImagesModel();
        $this->cat_prod_model      = new shopCategoryProductsModel();
    }

    protected function preExecute(){
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();
    }

    /**
     * @throws waException
     */
    protected function init() {
        if (!extension_loaded('xmlreader')) {
            throw new waException('PHP extension XMLReader required');
        }

        if (!extension_loaded('simplexml')) {
            throw new waException('PHP extension SimpleXML required');
        }

        $this->initHelpers();

        $profile_id = waRequest::post('profile_id', 0, waRequest::TYPE_INT);
        $settings   = shopYmlHelper::getProfileConfig($profile_id);

        if ( empty($settings) ){
            throw new waException('Не найдена конфигурaция для профиля ' . ($profile_id ? '#' . $profile_id : ' по умолчанию'));
        }

        if (  empty($settings['source_type']) ){
            $settings['source_type'] = 'remote';
        }

        $save_path  = shopYmlHelper::ymlPath($profile_id, $settings);

        $map_path = shopYmlHelper::mapPath($profile_id, $settings['source_type']);
        if ( !file_exists($map_path) ){
            throw new waException('Необходимо указать соответствия');
        }

        $map = include($map_path);
        if ( !empty($map) ){
            foreach ( $map as $key => &$data ){
                if ( !empty($data['type']) && (strpos($data['type'], '#') !== false) ){
                    $data['type'] = explode('#', $data['type']);
                }
            }
            unset($data);
        }

        $this->data['map'] = $map;

        if ( empty($this->data['map']) ){
            throw new waException('Необходимо указать соответствия');
        }

        $this->gc_collect = function_exists('gc_collect_cycles');

        $this->data['profile_id'] = $profile_id;

        $file_url = '';
        if ( $settings['source_type'] === 'remote' ){
            $file_url = ifempty($settings['url']);

            if (empty($file_url)) {
                throw new waException('Не корректная ссылка к xml файлу');
            }
        }

        if ( !empty($settings['limit_stream']) && !empty($settings['stream_value']) ){
            $stream = (int) $settings['stream_value'];

            if ( $stream >= 100 ){
                $stream = 1000;
            } elseif ($stream > 0) {
                $stream = $stream * 10;
            } else {
                $stream = null;
            }

            if ( !empty($stream) ){
                $this->data['limit_stream'] = $stream;
            }
        }

        $settings['round']        = (int) $settings['round'];

        $settings['price_up']     = (double) str_replace(',', '.', ifset($settings['price_up'],0));
        $this->data['settings']   = $settings;
        $this->data['filepath']   = $save_path;
        $this->data['img_k']      = 0;
        $this->data['timestamp']  = time();

        $this->data['d'] = 3;
        $this->data['t'] = 'offer';
        $this->data['path'] = array('0:yml_catalog' , '1:shop' , '2:offers');
        $this->data['prev'] = '';

        foreach (array('undefined_parents','images') as $kk){
            $this->data[$kk] = array();
        }

        $this->data['currency']    = wa()->getSetting('currency', 'RUB', 'shop');

        $this->data['memory']      = memory_get_peak_usage();
        $this->data['memory_peak'] = memory_get_usage();

        $stages = array();
        $this->data['nodes'] = array('offer' => self::STAGE_PRODUCT);
        if ( !empty($this->data['settings']['import_categories']) ){
            $stages[] = self::STAGE_CATEGORY;
            $this->data['nodes']['category'] = self::STAGE_CATEGORY;
        }

        $stages[] = self::STAGE_PRODUCT;

        if (!empty($this->data['settings']['duplicate_as_child'])){
            if ( !empty($this->data['settings']['parent_id']) ){
                $this->data['move'] = array();
            }
        }

        $restore      = !empty($settings['restore']);
        $restore_path = shopYmlHelper::sessionPath($profile_id, $settings['source_type']);
        $init         = true;

        if ( $restore && file_exists($restore_path) ){
            $session = include($restore_path);
            if ( !empty($session) ){
                foreach ( $session as $k => $v ){
                    $this->data[$k] = $v;
                }
                $init = false;
            }
        }

        if ( $init ){
            if ( $settings['source_type'] === 'remote' ){
                $this->download($file_url, $save_path, 1, true);
            }

            if ( $zip_path = shopYmlHelper::extract($profile_id, $save_path, $settings) ){
                $this->data['filepath']   = $zip_path;
            }

            $this->data['stage']        = reset($stages);
            $this->data['stages']       = $stages;

            foreach ( $stages as $stage ){
                $this->data['count'][$stage] = 0;
                $this->data['total_count'][$stage] = 0;
                $this->data['report'][$stage] = array(
                    'new'         => 0,
                    'update'      => 0
                );
                $this->data['offset'][$stage]  = 0;
            }

            $total_count = $this->count();

            if ( !$total_count ){
                if ( !empty($zip_path) ){
                    waFiles::delete($zip_path);
                }

                throw new waException('Данные для импорта не найдены');
            }

            if ( !empty($settings['link_separator']) ){
                switch ($settings['link_separator']){
                    case '1':
                        $this->data['separator'] = ',';
                        break;

                    case '2':
                        $this->data['separator'] = ';';
                        break;

                    case '3':
                        $this->data['separator'] = ' ';
                        break;
                }
            }

            $model = new waModel();

            $sql   = "UPDATE `shop_product` SET yml_updated = 0 WHERE `yml_profile_id` = i:profile_id";
            $model->exec($sql, array('profile_id' => $profile_id));

            $sql = "UPDATE `shop_product_skus` AS sps 
                      JOIN `shop_product` AS sp 
                        ON sps.`product_id` = sp.`id`
                    SET sps.`yml_up` = 0
                    WHERE (sp.`sku_type` = 1) AND (sps.`virtual` = 1) AND (sp.`yml_profile_id` = i:prof_id)";
            $model->exec($sql, array('prof_id' => $profile_id));
        }
    }

    public function count(){
        $total_count = 0;
        foreach ( $this->data['nodes'] as $node  => $stage ){
            if ( ($stage === self::STAGE_CATEGORY) && !empty($this->data['settings']['allow_categ_map']) ){
                $this->data['total_count'][$stage] = 1;
                continue;
            }

            $this->initReader(false);
            while ($this->reader->read() && !($this->reader->name === $node && $this->reader->depth === self::$depth));
            while ( $this->reader->name === $node ){
                ++$this->data['total_count'][$stage];
                if ( !$this->reader->next($node) ){
                    break;
                }
            }

            $total_count += $this->data['total_count'][$stage];
        }

        $this->reader->close();
        return $total_count;
    }

    public function initReader($move = true){
        $filename = $this->data['filepath'];

        if ( $this->reader ){
            $this->reader->close();
        } else {
            $this->reader = new XMLReader();
        }

        if ( !file_exists($filename) ){
            throw new waException('XML file missed');
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$this->reader->open($filename, null, LIBXML_NONET)) {
            $this->error( 'Error while open XML ' . $filename );
            throw new waException( 'Ошибка открытия XML файла - ' . $filename );
        }

        if ( $move && isset($this->data['stage']) ){
            $step       = $this->data['stage'];
            $step_node  = self::$nodes[$step];

            while ( $this->reader->read() && !(($this->reader->name === $step_node) && ($this->reader->depth === self::$depth)) );
            $current    = 0;
            while (($current<$this->data['offset'][$step]) && $this->reader->next($step_node)){$current++;}
        }
    }

    protected function finish($filename) {
        $this->info();
        if ($this->getRequest()->post('cleanup')) {
            if ( $this->reader ){
                $this->reader->close();
            }

            return true;
        }
        return false;
    }

    public function resetCount(){
        $product_model = new shopProductModel();
        if ( isset($this->data['count']['offers']) && $this->data['count']['offers'] > 0 ){
            $sql = "UPDATE `shop_product` AS sp
                        JOIN `shop_product_skus` AS sps
                            ON sp.id = sps.product_id
                    SET sp.count = 0, sps.count = 0, sps.available = 0
                    WHERE sp.yml_profile_id = i:profile_id AND sp.yml_updated = 0";
            $product_model->exec($sql, array('profile_id' => $this->data['profile_id']));
        }
    }

    protected function info(){
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }

        $offset = array_sum($this->data['offset']);
        $total  = array_sum($this->data['total_count']);
        $stage  = $this->data['stage'];

        $response = array(
            'time'        => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId'   => $this->processId,
            'progress'    => 0,
            'ready'       => $this->isDone(),
            'offset'      => $offset,
            'total'       => $total,
            'memory'      => sprintf('%0.2fMByte', $this->data['memory'] / 1048576),
            'memory_peak' => sprintf('%0.2fMByte', $this->data['memory_peak'] / 1048576),
        );

        if ( $response['ready'] ){
            $this->removeSession();
        }

        $response['progress']   = ($offset / $total) * 100;
        if ( $response['progress'] > 100 ){
            $response['progress'] = 100;
        }
        $response['progress']   =  sprintf('%0.3f%%', $response['progress']);
        $response['stage']      = $stage;
        $response['stageLabel'] = ifempty($this->stage_labels[$stage], 'Выполняется обработка данных');

        if ($this->getRequest()->post('cleanup')) {
            $response['report'] = $this->report();
            if ( !empty($this->data['settings']['hide_excluded']) ){
                $this->resetCount();
            }

            if ( $this->data['offset'][self::STAGE_PRODUCT] >= $this->data['total_count'][self::STAGE_PRODUCT] ){
                $model = new waModel();

                $sql = "DELETE  sps,spf,pfs 
                          FROM `shop_product_skus` AS sps 
                        JOIN `shop_product` AS sp
                            ON sps.`product_id` = sp.`id`
                        JOIN `shop_product_features` AS spf
                           ON sps.`id` = spf.`sku_id`
                        JOIN `shop_product_features_selectable` AS pfs
                           ON spf.`product_id` = pfs.`product_id` AND spf.feature_id = pfs.feature_id AND spf.feature_value_id = pfs.value_id
                        WHERE (sp.`sku_type` = 1)
                          AND (sps.`virtual` = 1)
                            AND (sps.`yml_up` = 0) 
                              AND sp.`yml_profile_id` = i:prof_id";

                $model->exec($sql, array('prof_id' => $this->data['profile_id']));
            }
        }

        echo json_encode($response);
    }

    protected function report() {
        $report = '<div class="successmsg"><i class="icon16 yes"></i> Синхронизация завершена успешно</div>';
        $report .= '<div class="sync-report">';

        if ( !empty($this->data['settings']['import_categories']) ) {
            $report .= '
                <div class="categories">
                    <span class="stage-title">Категории</span>
                    
                    <ul class="menu-v">
                        <li>
                            <strong>Всего обработано: </strong>' . $this->data['count'][self::STAGE_CATEGORY] . '
                        </li>
                        
                        <li>
                            <strong>Добавлено: </strong> ' . $this->data['report'][self::STAGE_CATEGORY]['new'] . '
                        </li>
                        
                        <li>
                            <strong>Обновлено: </strong> ' . $this->data['report'][self::STAGE_CATEGORY]['update'] . '
                        </li>
                    </ul>
                </div>
            ';
        }

        if ( isset($this->data['count'][self::STAGE_PRODUCT]) ){
            $report .= '<div class="products">
                            <span class="stage-title">Товары:</span>

                            <ul class="menu-v">
                                <li><strong>Всего обработано: </strong> '  . $this->data['count'][self::STAGE_PRODUCT]            . '</li>
                                <li><strong>Добавлено: </strong> '         . $this->data['report'][self::STAGE_PRODUCT]['new']    . '</li>
                                <li><strong>Обновлено: </strong> '         . $this->data['report'][self::STAGE_PRODUCT]['update'] . '</li>
                            </ul>
                        </div>
                    </div>';
        }

        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
            $interval = sprintf(_w('%02d hr %02d min %02d sec'), floor($interval / 3600), floor($interval / 60) % 60, $interval % 60);
            $report   .= ' '.sprintf(_w('(total time: %s)'), $interval);
        }

        return $report;
    }

    private function error($message) {
        waLog::log($message, '/shop/plugins/yml.log');
    }

    private function saveSession(){
        if ( !empty($this->data['settings']['restore']) ){
            $session = array(
                'stages'      => $this->data['stages'],
                'stage'       => $this->data['stage'],
                'count'       => $this->data['count'],
                'offset'      => $this->data['offset'],
                'report'      => $this->data['report'],
                'total_count' => $this->data['total_count'],
                'time_start'  => $this->data['timestamp'],
                'time_end'    => time()
            );

            $path = shopYmlHelper::sessionPath($this->data['profile_id'], $this->data['settings']['source_type']);
            waUtils::varExportToFile($session,$path);
            unset($session);
        }
    }

    private function removeSession(){
        $path = shopYmlHelper::sessionPath($this->data['profile_id'], $this->data['settings']['source_type']);
        if ( file_exists($path) ){
            waFiles::delete($path);
        }
    }

    protected function step(){
        $result = true;

        if ( empty($this->data['images']) ){
            $step      = $this->data['stage'];
            $step_node = self::$nodes[$step];

            if ($this->reader->name === $step_node){
                $method = 'step' . ucfirst($step);
                $this->$method();

                if ( $step === self::STAGE_PRODUCT ){
                    ++$this->data['count'][$step];
                }
            }

            ++$this->data['offset'][$step];

            $this->saveSession();
            if ( $this->isDoneStage() && !$this->isLastStage() ){
                $this->nextStage();
            }

            if ( $this->gc_collect && (($this->data['count'][$step] % 10) === 0) ){
               gc_collect_cycles();
            }

            if ( !empty($this->data['limit_stream']) && (($this->data['offset'][$step] % $this->data['limit_stream']) === 0) ){
                $result = false;
            }
        } else {
            $result = $this->importImage();
        }

        return $result && !$this->isDone();
    }

    protected function stepCategories(){
        if ( !empty($this->data['settings']['allow_categ_map']) ){
            $this->stepCategoriesRecursive();
        } else {
            $this->stepCategoriesStream();
            ++$this->data['count'][self::STAGE_CATEGORY];
        }
    }

    protected function stepCategoriesRecursive(){
        if ( !empty($this->_cache['categs']) ){
            $list = array();

            while ( $this->reader->name === 'category' ){
                $category = simplexml_load_string($this->reader->readOuterXml());

                $parent_id = !empty($category['parentId']) ? (string) $category['parentId'] : 0;
                $id = trim((string) $category['id']);

                if ( !$id ){
                    $id = 0;
                }

                $list[$parent_id][] = array(
                    'id'        => $id,
                    'name'      => (string) $category
                );

                if ( !$this->reader->next('category') ){
                    break;
                }
            }

            if ( !empty($list) ){
                $this->createTree($list, $list[0]);
                unset($list);
            }
        }
    }

    public function createTree(&$list, $parent, $parent_id = null){
        if ( !empty($parent) ){
            foreach ($parent as $k=>$l){
                ++$this->data['count'][self::STAGE_CATEGORY];
                $_parent_id  = $parent_id;
                $category_id = null;

                if ( $_parent_id ){
                    if ( empty($this->_cache['categs'][$l['id']]) || ($this->_cache['categs'][$l['id']]['id'] == $_parent_id) ){
                        $query   = "SELECT id FROM shop_category WHERE `yml_id` = s:yml_id" . (!empty($this->data['settings']['bind_to_profile']) ? " AND `yml_profile_id` = i:profile_id" : "");
                        $holders = array('yml_id' => $l['id']);

                        if ( !empty($this->data['settings']['bind_to_profile']) ) {
                            $holders['profile_id'] = $this->data['profile_id'];
                        }

                        $category_id  = $this->category_model->query($query, $holders)->fetchField('id');
                        if ( !$category_id ){
                            $url    = shopHelper::transliterate($l['name']);
                            $categ  = array(
                                'name'           => html_entity_decode($l['name']),
                                'yml_id'         => $l['id'],
                                'parent_id'      => $_parent_id,
                                'yml_profile_id' => $this->data['profile_id'],
                                'include_sub_categories' => 1
                            );

                            $url = $this->category_model->suggestUniqueUrl($url);
                            if ( $url ){
                                $categ['url'] = $url;
                            }

                            $category_id = $this->category_model->add($categ, $_parent_id);
                            ++$this->data['report'][self::STAGE_CATEGORY]['new'];
                            if ( $category_id ){
                                $categ = $this->category_model->getById($category_id);
                                wa()->event('category_save', $categ);
                            }
                        }

                        if ( $category_id ){
                            $this->_cache['categs'][$l['id']]['id'] = $category_id;
                            $this->_cache['categs'][$l['id']]['mode'] = ifempty($this->_cache['categs'][$l['id']]['mode'],2);
                            $this->setCategMap();
                        }
                    } else {
                        $category_id = $this->_cache['categs'][$l['id']]['id'];
                    }
                }

                if (isset($list[$l['id']])){
                    if (!empty($category_id)){
                        $_parent_id = $category_id;
                    } elseif ( isset($this->_cache['categs'][$l['id']]) ){
                        $c = $this->_cache['categs'][$l['id']];
                        if ( ifset($c['mode']) === 1 ){
                            $_parent_id = $c['id'];
                        } else {
                            $_parent_id = null;
                        }
                        unset($c);
                    }

                    $this->createTree($list, $list[$l['id']], $_parent_id);
                }
            }
        }
    }

    protected function stepCategoriesStream(){
        $category    = simplexml_load_string($this->reader->readOuterXml());
        $result      = false;
        $profile_id  = $this->data['profile_id'];
        $bind        = (int) $this->data['settings']['bind_to_profile'];
        $shop_parent_id = 0;

        $id          = (string) $category['id'];

        $parent_id   = (string) $category['parentId'];
        $value       = (string) $category;

        $holders = array('yml_id' => $id);
        if ( $bind ){
            $holders['profile_id'] = $profile_id;
        }

        $query   = "SELECT id FROM shop_category WHERE `yml_id` = s:yml_id" . ($bind ? " AND `yml_profile_id` = i:profile_id" : "");
        $item    = $this->category_model->query($query, $holders)->fetchAssoc();

        if ( $item && !empty($this->data['settings']['no_update_categs']) ){
            return $this->reader->next('category');
        } elseif (!$item && !empty($this->data['settings']['no_new_categs'])) {
            return $this->reader->next('category');
        }

        if ( $parent_id ){
            $query          = "SELECT id, depth, full_url FROM shop_category WHERE `yml_id` = s:yml_id " . ($bind ? "AND `yml_profile_id` = i:profile_id" : "");
            $holders        = array( 'yml_id' => $parent_id);

            if ( $bind ){
                $holders['profile_id'] = $profile_id;
            }

            $parent         = $this->category_model->query($query, $holders)->fetchAssoc();
            $shop_parent_id = ifempty($parent['id']);
            $parent_url     = !empty($parent['full_url']) ? $parent['full_url'] . '/' : '';
        } else {
            $parent_depth   = 0;
            $parent_url     = '';
        }

        $url    = shopHelper::transliterate($value);
        $categ  = array(
            'name'           => $value,
            'yml_id'         => $id,
            'url'            => $url,
            'yml_profile_id' => $profile_id
        );

        if ( $item ){
            $categ = array_merge($item, $categ);
        }

        $category_id = ifset($categ['id']);
        $new = false;

        $url = $this->category_model->suggestUniqueUrl($url, $category_id, $shop_parent_id);
        if ( $url ){
            $categ['url'] = $url;
        }

        if ( $category_id ){
            $this->data['report']['categories']['update']++;
            $this->category_model->update($category_id, $categ);
        } else {
            $categ['include_sub_categories'] = 1;
            $category_id = $this->category_model->add($categ, $shop_parent_id);
            $this->data['report']['categories']['new']++;
            $new = true;
        }

        if ( $new ){
            $categ = $this->category_model->getById($category_id);
        }

        wa()->event('category_save', $categ);

        if ( $category_id && $parent_id && !$shop_parent_id ){
            if ( empty($this->data['undefined_parents'][$parent_id]) ){
                $this->data['undefined_parents'][$parent_id] = array();
            }
            $this->data['undefined_parents'][$parent_id][] = $category_id;
        }

        if ( !empty($this->data['undefined_parents'][$id]) ){
            foreach ( $this->data['undefined_parents'][$id] as $c_id ){
                $this->category_model->move($c_id, null, $category_id);
                if ( !empty($this->data['move'][$c_id]) ){
                    unset($this->data['move'][$c_id]);
                }
            }
            unset($this->data['undefined_parents'][$id]);
        }

        if ( !$shop_parent_id && !empty($this->data['settings']['duplicate_as_child']) && !empty($this->data['settings']['parent_id'])){
            if ( !$parent_id ){
                $this->category_model->move($category_id, null, (int) $this->data['settings']['parent_id']);
            } else {
                $this->data['move'][$category_id] = $url;
            }
        } else {
            $this->category_model->move($category_id, null, $shop_parent_id);
        }

        return $this->reader->next('category');
    }

    protected function stepOffers( $is_child = false, $tdepth = null, $tname = null ){
        $data = array();
        $type = null;
        $name_mode = $this->data['settings']['product_names'] === '2';

        if ( $is_child ){
            $this->reader->read();
        }

        do {
            $type = $this->reader->nodeType;
            if ( !isset(self::$types[$type]) ){
                continue;
            }

            if ( $type === XMLReader::ELEMENT ){
                $depth = $this->reader->depth;

                if ( $depth > $this->data['d'] ){
                    $this->data['path'][$this->data['d']] = $this->data['d'] . ':' . $this->data['t'];
                } elseif ( $depth < $this->data['d'] ){
                    end($this->data['path']);
                    while ( !empty($this->data['path']) && key($this->data['path']) >= $depth ){
                        array_pop($this->data['path']);
                        end($this->data['path']);
                    }
                }

                $this->data['d'] = $this->reader->depth;
                $this->data['t'] = $this->reader->localName;
                
                $name = $this->reader->localName;
                if ( $name === 'param' ){
                    $attr_name = $this->reader->getAttribute('name');
                    $attr_name = str_replace(array('"', "'"), '_', $attr_name);
                    if ( $attr_name ){
                        $name .= '::' . $attr_name;
                    }
                }

                $key = implode("\\", $this->data['path']) . (!empty($this->data['path']) ? "\\" : '') . $this->reader->depth . ':' . $name;

                if ( $name_mode && isset(self::$tPvm[$name]) ){
                    $save = $name;
                }

                $read_attrs = true;
                if ( isset($this->data['map'][$key]) ){
                    $data_types = isset($this->data['map'][$key]['type']) ? $this->data['map'][$key]['type'] : array();

                    if ( !is_array($data_types) && ($data_types === 'product:sku') ){
                        $sku = $this->stepOffers(true, $this->reader->depth, $this->reader->localName);
                        if ( $sku ){
                            if ( empty($data['skus']) ){
                                $data['skus'] = array();
                            }

                            $data['skus'][] = $sku;
                        }
                        continue;
                    }

                    if ( !is_array($data_types) ){
                        $data_types = array($data_types);
                    }

                    $update    = !empty($this->data['map'][$key]['up']);
                    foreach ( $data_types as &$data_type ){
                        if ( !$update ){
                            $this->_cache['no_update'][$data_type] = 1;
                        }

                        if ( ($this->reader->localName === 'param') && ($data_type === 'feature:auto')){
                            if ( $name = $this->reader->getAttribute('name') ){
                                if ($feature_id = $this->getFeature($name)){
                                    $data_type = 'feature:exists:' . $feature_id;
                                    $read_attrs = false;
                                }
                            }
                        }
                    }

                    unset($data_type);
                }

                if ( $read_attrs && $this->reader->hasAttributes ){
                    while ( $this->reader->moveToNextAttribute() ){
                        $attr_key = $key . ':a:' . $this->reader->localName;

                        if ( !empty($this->data['map'][$attr_key]['type']) ){
                            $types = $this->data['map'][$attr_key]['type'];
                            $v    = trim(html_entity_decode((string) $this->reader->value));

                            if ( !is_array($types) ){
                                $types = array($types);
                            }

                            foreach ( $types as $type ){
                                if ( !isset($data[$type]) ){
                                    $data[$type] = $v;
                                } else {
                                    if ( !is_array($data[$type]) ){
                                        $data[$type] = array($data[$type]);
                                    }
                                    $data[$type][] = $v;
                                }
                            }
                        }
                    }

                    $this->reader->moveToElement();
                    $key = null;
                }

                if ( $this->reader->isEmptyElement && $is_child && ($this->reader->depth === $tdepth) && ($this->reader->localName === $tname) ){
                    break;
                }

                continue;
            } elseif ( isset($data_types) && isset(self::$text_types[$type]) ){

                foreach ( $data_types as $data_type ){
                    if ( empty($data[$data_type]) ){
                        $data[$data_type] = $this->reader->value;
                    } else {
                        if ( !is_array($data[$data_type]) ){
                            $data[$data_type] = array($data[$data_type]);
                        }
                        $data[$data_type][] = $this->reader->value;
                    }
                }

                if ( isset($save) ){
                    $data[$save] = $this->reader->value;
                    unset($save);
                }
                $data_types = null;
                continue;

            } elseif(isset($save)){
                $data[$save] = $this->reader->value;
                unset($save);
            }elseif ( $type === XMLReader::END_ELEMENT ){

                if ( $this->reader->depth < $this->data['d'] ){
                    end($this->data['path']);
                    while ( !empty($this->data['path']) && (key($this->data['path']) >= $this->reader->depth) ){
                        array_pop($this->data['path']);
                        end($this->data['path']);
                    }

                    $this->data['d'] = $this->reader->depth;
                    $this->data['t'] = $this->reader->localName;
                }

                if ( !$is_child ){
                    if (($this->reader->depth === self::$depth) && ($this->reader->localName === 'offer') ){
                        while($this->reader->read() && ($this->reader->localName !== 'offer'));
                        break;
                    }

                } elseif ( ($this->reader->depth === $tdepth) && ($this->reader->localName === $tname)) {
                    break;
                }
            }

        } while ($this->reader->read());

        if ( $data ) {
            if ( !$is_child ){
                if ( empty($data['product:yml_id']) ){
                    waLog::log('Не найден yml_id', 'yml.log');
                    return false;
                }

                $this->productUp($data);
            } else {
                return $data;
            }
        }
    }

   protected function productUp($data){
        if (empty($data['product:group_id']) ){
            $xml_id    = $data['product:yml_id'];
            $sku_type  = shopProductModel::SKU_TYPE_FLAT;
        } else {
            $xml_id    = $data['product:group_id'];
            unset($data['product:group_id']);
            $sku_type  = shopProductModel::SKU_TYPE_SELECTABLE;
            $data['product:yml_id'] = $xml_id;
            $values_map = array();
            $stock  = array('stock_info' => array());
        }

        $category_id = array();
        if ( isset($data['product:category_id']) ){
            if ( !is_array($data['product:category_id']) ){
                $category_id[] = $data['product:category_id'];
            } else {
                $category_id = $data['product:category_id'];
            }
            unset($data['product:category_id']);
        }

        if ( !empty($this->data['settings']['allow_categ_map']) ) {
            $found = false;
            if ( !empty($category_id) ){
                 foreach ($category_id as $cid ){
                    if (!empty($this->_cache['categs'][$cid])){
                        $found = true;
                        break;
                    }
                 }
            }

            if (!$found){
                return true;
            }
        }

        $bind         = (int) $this->data['settings']['bind_to_profile'];
        $profile_id   = $this->data['profile_id'];
        $sku_code     = !empty($data['sku:sku']) ? $data['sku:sku'] : null;

        if ( $this->data['settings']['product_names'] === '2' ){
            $_product_name = '';
            foreach ( self::$tPvm as $name_key => $_v ){
                if ( isset($data[$name_key]) ){
                    $_product_name .= ($_product_name ? ' ' : '') . trim($data[$name_key]);
                    unset($data[$name_key]);
                }
            }

            if ( $_product_name ){
                $data['product:name'] = $_product_name;
            }
        }

        $product_name = null;
        if ( array_key_exists('product:name', $data) ){
            if ( !empty($data['product:name']) ){
                $data['product:name'] = html_entity_decode($data['product:name']);
                $product_name = $data['product:name'];
            } else {
                unset($data['product:name']);
            }
        }

        $product_id   = null;

        if ( ($sku_type === shopProductModel::SKU_TYPE_SELECTABLE) && !empty($this->_cache['pids'][$xml_id]) ){
            $product_id = $this->_cache['pids'][$xml_id];
        }

        if (!$product_id = $this->getProductId('yml_id', $xml_id, $bind ? $profile_id : null)) {
             if (!empty($this->data['settings']['searchbysku']) && $sku_code){
                $product_id = $this->getProductIdBySku($sku_code, $bind ? $profile_id : null);
             }

             if ( !$product_id && !empty($this->data['settings']['searchbyname']) && $product_name ){
                $product_id = $this->getProductId('name', $product_name, $bind ? $profile_id : null);
             }
        }

        $new = false;
        if ( !$product_id ){
            if ( !empty($this->data['settings']['no_new_products']) ){
                return true;
            }
            $this->data['report']['offers']['new']++;
            $new = true;
        } elseif ( !empty($this->data['settings']['no_update_products']) ){
            $this->product_model->updateById($product_id, array('yml_updated' => 1));
            return true;
        } else {
            if ( !$new && !empty($this->_cache['no_update']) ){
                foreach ($this->_cache['no_update'] as $d_id => $d_v){
                    unset($data[$d_id]);
                }
            }

            if ( $sku_type === shopProductModel::SKU_TYPE_SELECTABLE ){
                $this->_cache['pids'][$xml_id] = $product_id;
            }

            ++$this->data['report']['offers']['update'];
        }

        $shop_product = new shopProduct($product_id);
        if ( !$shop_product->url && empty($data['product:url']) ){
            $_url = $product_name ? shopHelper::transliterate($product_name) : $xml_id;
            $shop_product->url  = self::suggestUniqueUrl($_url);
        }

        if ( empty($data['product:currency']) ){
            $data['product:currency']  = $shop_product->currency ? $shop_product->currency : $this->data['currency'];
        }

        if ($data['product:currency'] === 'RUR') {
            $data['product:currency'] = 'RUB';
        }

       if ( array_key_exists('product:type_id', $data) ){
           $data['product:type_id'] = $this->typeId($data['product:type_id']);
           $shop_product->type_id = $data['product:type_id'];
       }

        $shop_product->yml_updated    = 1;
        $shop_product->yml_id         = $xml_id;

        if ( !$shop_product->yml_profile_id ){
            $shop_product->yml_profile_id = $profile_id;
        }

        $shop_product->sku_type       = $sku_type;

        $update_prices = array();
        foreach (array('sku:rrc', 'sku:price', 'sku:compare_price', 'sku:purchase_price') as $price_key){
            if ( array_key_exists($price_key, $data) ){
                $data[$price_key] = (double) str_replace(',', '.', $data[$price_key]);

                if ( !empty($this->data['settings']['convert_prices']) && !empty($data['product:currency']) && ($data['product:currency'] !== $this->data['currency']) ){
                    $data[$price_key] = (double) shop_currency($data[$price_key], $data['product:currency'], $this->data['currency'], false);
                }

                if ( $price_key !== 'sku:purchase_price' ){

                    if ( $price_key !== 'sku:rrc' ){
                        if ( !empty($this->data['settings']['markup_type']) ){
                            if ( $this->data['settings']['markup_type'] === '1') {
                                if ( $this->data['settings']['type_of_markup'] === '1' ){
                                    $data[$price_key] += ($data[$price_key] / 100) * $this->data['settings']['price_up'];
                                } else {
                                    $data[$price_key] += (double) $this->data['settings']['price_up'];
                                }
                            } elseif ($this->data['settings']['markup_type'] === '2') {
                                $data[$price_key] = $this->markUp($data[$price_key]);
                            }
                        }
                    }

                    if ( !empty($this->data['settings']['round']) ){
                        switch ($this->data['settings']['round']){
                            case 1:
                                $data[$price_key] = ceil($data[$price_key]);
                                break;

                            case 5:
                            case 10:
                            case 100:
                                $data[$price_key] = ceil($data[$price_key]/$this->data['settings']['round']) * $this->data['settings']['round'];
                                break;
                        }
                    }
                }

                $update_prices[$price_key] = $data[$price_key];
                if ( ($sku_type === shopProductModel::SKU_TYPE_SELECTABLE) && !$shop_product->price ){
                    unset($data[$price_key]);
                }
            }

            if ( !empty($data['skus']) ){
                foreach ( $data['skus'] as &$psku ){
                    if ( isset($psku[$price_key]) ){
                        $psku[$price_key] = (double) str_replace(',', '.', $psku[$price_key]);

                        if ( !empty($this->data['settings']['convert_prices']) && !empty($data['product:currency']) && ($data['product:currency'] !== $this->data['currency']) ){
                            $psku[$price_key] = (double) shop_currency($psku[$price_key], $data['product:currency'], $this->data['currency'], false);
                        }

                        if ( $price_key !== 'sku:purchase_price' ){
                            if ( $price_key !== 'sku:rrc' ){
                                if ( !empty($this->data['settings']['markup_type']) ){
                                    if ( $this->data['settings']['markup_type'] === '1') {
                                        if ( $this->data['settings']['type_of_markup'] === '1' ){
                                            $psku[$price_key] += ($psku[$price_key] / 100) * $this->data['settings']['price_up'];
                                        } else {
                                            $psku[$price_key] += (double) $this->data['settings']['price_up'];
                                        }
                                    } elseif ($this->data['settings']['markup_type'] === '2') {
                                        $psku[$price_key] = $this->markUp($psku[$price_key]);
                                    }
                                }
                            }

                            if ( !empty($this->data['settings']['round']) ){
                                switch ($this->data['settings']['round']){
                                    case 1:
                                        $psku[$price_key] = ceil($psku[$price_key]);
                                        break;

                                    case 5:
                                    case 10:
                                    case 100:
                                        $psku[$price_key] = ceil($psku[$price_key]/$this->data['settings']['round']) * $this->data['settings']['round'];
                                        break;
                                }
                            }
                        }
                    }
                }
                unset($psku);
            }
        }

        if ( !empty($update_prices['sku:rrc']) ){
            $update_prices['sku:price'] = $update_prices['sku:rrc'];
            unset($update_prices['sku:rrc']);
        }

       if ( empty($data['product:currency']) || !empty($this->data['settings']['convert_prices']) ) {
           $data['product:currency'] = $this->data['currency'];
       }

       $features_selectable = array();

        static $features_model;
        if ( $product_id && !isset($features_model) ){
            $features_model = new shopProductFeaturesModel();
        }

        $features = !$product_id ? array() : $features_model->getValues($product_id);
        $sku_id   = $shop_product->sku_id ? $shop_product->sku_id : -1;

        static $sku_model;
        if ( $sku_id && !isset($sku_model) ){
            $sku_model = new shopProductSkusModel();
        }

        $sku = ($sku_id > 0) && ($sku_type !== shopProductModel::SKU_TYPE_SELECTABLE) ? $sku_model->getSku($sku_id) : array();
        if ( !is_array($sku) ){
            $sku = array();
        }

        $images = array();
        if ( !empty($data['product:images']) ){
            if ( $this->data['settings']['images'] !== self::IMAGES_UPLOAD_NONE ){
                $images = $data['product:images'];
            }
            unset($data['product:images']);
        }

        $skus = array(); $default_sku_data = array();
        if ( !empty($data['skus']) ){
            $skus = $data['skus'];
            unset($data['skus']);
        }

        $stocks = self::$stocks;
        $available = 1;
        foreach ( $data as $key => $value ){
            $data_type = explode(':', $key);
            $item_type = $data_type[0];

            if ( $item_type === 'product' ){
                    if ( is_array($value) ){
                        foreach ( $value as $_v ){
                            if ( $_v ){
                                $value = $_v;
                                break;
                            }
                        }

                        if ( is_array($value) || !$value ){
                            continue;
                        }
                    }

                    $prd_field = $data_type[1];
                    $shop_product->$prd_field = $value;
            } elseif ( $item_type === 'sku' ){

                if ( ($data_type[1] === 'stock') ){
                    if ( is_array($value) ){
                        $value = array_sum(array_map('intval',$value));
                    }

                    $value = ($value === 'true') ? null : ($value === 'false' ? 0 : $value);

                    if ( isset($data_type[2]) ){
                        $data_type[2] = (int) $data_type[2];

                        if ( !empty($this->data['settings']['reset_stocks']) ) {
                            foreach ($stocks as $sk_id => $s_id) {
                                $sku['stock'][$s_id] = 0;
                            }

                            $current_s_id = array_search($data_type[2], $stocks);
                            if ( $current_s_id !== false ){
                                unset($stocks[$current_s_id]);
                            }
                        }

                        $sku['stock'][$data_type[2]] = $value;

                        if ( $sku_type === shopProductModel::SKU_TYPE_SELECTABLE ){
                            $stock['stock_info'][$data_type[2]] = $value;
                        }
                    } else {
                        $sku['count'] = $value;
                        if ( $sku_type === shopProductModel::SKU_TYPE_SELECTABLE ){
                            $stock['count'] = $value;
                        }
                    }


                } elseif ($data_type[1] === 'available'){
                    $value = !$value || ($value == 'false') ? 0 : 1;
                    if ( $sku_type === shopProductModel::SKU_TYPE_SELECTABLE ){
                        $stock['available'] = $value;
                    }

                    $sku[$data_type[1]] = $value;
                    $available = null;
                } else {
                    if ( is_array($value) ){
                        $value = reset($value);
                    }
                    $sku[$data_type[1]] = $value;
                }

                if ( $skus ){
                    $default_sku_data[$key] = $value;
                }

            } elseif ( $item_type === 'feature' ){
                if (($data_type[1] === 'exists') && ($feature = $this->featureById($data_type[2], $shop_product->type_id) )){
                    $feature_id = $feature['id'];

                    if ( (!empty($data_type[3]) && ($data_type[3] === '+')) && $feature['multiple'] && $feature['selectable'] ){
                        $_value = !is_array($value) ? array($value) : $value;

                        foreach ( $_value as $v ){
                            $v = str_replace(array("'",'"'),'', $v);

                            if ( empty($features_selectable[$feature['code']]) ){
                                $features_selectable[$feature['code']] = array('values' => array());
                            }

                            $values_map[$feature_id] = self::valueId($feature,$v);
                            $features_selectable[$feature['code']]['values'][$values_map[$feature_id]] = shopYmlHelper::fetchSelectable($v, null, $values_map[$feature_id]);
                        }
                    } else {
                        if ( is_array($value) ){
                            if ( $feature['multiple'] ){
                                $features[$feature['code']] = $value;
                            } else {
                                $features[$feature['code']] = implode('; ', $value);
                            }
                        } else {
                            if ( $feature['type'] === 'dimension.weight' ){
                                $features[$feature['code']] = array(
                                    'value' => $value,
                                    'unit' => $this->data['settings']['weight_unit']
                                );
                            } else {
                                $features[$feature['code']] = $value;
                            }
                        }
                    }
                }
            }
        }

        if ( $skus ){
            $_skus = array();
            $psku_default_id = -1;

            foreach ( $skus as $psku ){
                $shop_sku = $product_id && !empty($psku['sku:sku']) ? $this->getSku($product_id, $psku['sku:sku']) : array();

                $shop_sku_id = ifempty($shop_sku['id'], $psku_default_id);
                unset($shop_sku['id']);

                if ( !$shop_sku ){
                    $shop_sku = array(
                        'sku_type'  => shopProductModel::SKU_TYPE_FLAT,
                        'available' => 1,
                        'price' => 0
                    );
                }

                foreach ( $psku as $psku_field => $psku_val ){
                    $psf = explode(':', $psku_field);

                    if ( ($psf[1] === 'stock') && isset($psf[2]) ){
                        if ( !isset($shop_sku['stock']) ){
                            $shop_sku['stock'] = array();
                        }

                        $shop_sku['stock'][$psf[2]] = $psku_val;
                    } else {
                        $shop_sku[$psf[1]] = $psku_val;
                    }
                }

                if ( $default_sku_data ){
                    foreach ( $default_sku_data as $skey => $sval ){
                        if ( !array_key_exists($skey, $psku) ){
                            $eskey = explode(':', $skey);

                            if ( ($eskey[1] === 'stock') && !empty($eskey[2]) ){
                                if ( empty($shop_sku['stock']) ){
                                    $shop_sku['stock'] = array();
                                }

                                $shop_sku['stock'][$eskey[2]] = $sval;
                            } else {
                                $shop_sku[$eskey[1]] = $sval;
                            }
                        }
                    }
                }


                if ( !array_key_exists('price',$shop_sku) ){
                    $sku['price'] = 0;
                }

                if ( !array_key_exists('available', $shop_sku) ){
                    $sku['available'] = 1;
                }

                if ( !array_key_exists('count', $shop_sku)  ){
                    $shop_sku['count'] = null;
                }

                $_skus[$shop_sku_id] = $shop_sku;

                if ( $shop_sku_id < 0 ){
                    --$psku_default_id;
                }
            }

            if ( $_skus ){
                $shop_product->skus = $_skus;
            }
        }

       if ( !$shop_product->type_id && !empty($this->data['settings']['product_type']) ) {
           $shop_product->type_id = $this->data['settings']['product_type'];
       }

       $up_prices = false;
       if ( !empty($features_selectable) && !$sku_type ){
            $values_map = array();
            $up_prices  = true;
            $sku_type   = shopProductModel::SKU_TYPE_SELECTABLE;
            $shop_product->sku_type = $sku_type;
        } elseif ($sku_type && empty($features_selectable) ){
           $values_map = array();
           $sku_type = shopProductModel::SKU_TYPE_FLAT;
           $shop_product->sku_type = $sku_type;
       }

        if ( !empty($this->data['settings']['mark_feature']) && !empty($this->data['settings']['mark_value']) ){
            $features[$this->data['settings']['mark_feature']] = $this->data['settings']['mark_value'];
        }

        if ( $features ) {
            $shop_product->features = $features;
            unset($features);
        }

        if ( !empty($this->data['settings']['import_categories']) || ($new && !empty($this->data['settings']['new_parent_id'])) ){
            $set = false;
            $product_categories = self::productCategories($shop_product);

            if (!empty($this->data['settings']['import_categories'])){
                switch( ifempty($this->data['settings']['category']) ){
                    case self::SET_CATEGORY_NEW:
                        if ( $new ){
                            $set = true;
                        }
                        break;
                    case self::SET_CATEGORY_ALL:
                        $set = true; $product_categories = array();
                        break;
                    case self::SET_CATEGORY_APPEND:
                        $set = true;
                        break;
                }

                if ( $set ){
                    foreach( $category_id as $c_id ){
                        if ( !empty($this->data['settings']['allow_categ_map']) ) {
                            if ( !empty($this->_cache['categs'][$c_id]) ){
                                $product_categories[] = $this->_cache['categs'][$c_id]['id'];
                            }
                        } else {
                            $product_categories[] = $this->getCategoryId($c_id);
                        }
                    }
                    unset($category_id);
                }
            }

            if ($new && !empty($this->data['settings']['new_parent_id'])){
                $product_categories[] = $this->data['settings']['new_parent_id'];
                $set = true;
            }

            if ( $set ){
                $shop_product->categories = array_unique($product_categories);
            }
        }

        if ( $sku_type === shopProductModel::SKU_TYPE_SELECTABLE ){
            foreach ( array('price', 'compare_price', 'purchase_price') as $pkey ){
                $ppkey = 'sku:' . $pkey;
                $pkeyp = ($pkey == 'price' ? 'base_price' : $pkey) . '_selectable';
                if ( array_key_exists($ppkey, $update_prices) ){
                    $shop_product->$pkeyp = $update_prices[$ppkey];
                }
            }

            if ( !$shop_product->price && array_key_exists('sku:price', $update_prices) ){
                $shop_product->price = $update_prices['sku:price'];
            }

            if ( $features_selectable && empty($values_map) ){
                $shop_product->features_selectable = $features_selectable;
            }

            $shop_product->save();

            $product_id = $shop_product->getId();
            if ( !empty($values_map) ){
                static $product_features_m;
                static $skus_model;
                if ( !isset($product_features_m) ){
                    $product_features_m = new shopProductFeaturesModel();
                }

                if ( !isset($skus_model) ){
                    $skus_model = new shopProductSkusModel();
                }

                static $selectable_model;
                if ( !$selectable_model ){
                    $selectable_model = new shopProductFeaturesSelectableModel();
                }

                if ( !($sku_id = $product_features_m->getSkuByFeatures($product_id, $values_map)) ){
                    $sku_data = array(
                        'name'      => '',
                        'sku'       => '',
                        'virtual'   => 1,
                        'product_id' => $product_id,
                        'available' => 1,
                        'count'     => null,
                        'price'          => $shop_product->base_price_selectable,
                        'compare_price'  => $shop_product->compare_price_selectable,
                        'purchase_price' => $shop_product->purchase_price_selectable
                    );

                    $sku_name = array();

                    if ( $features_selectable ){
                        foreach ( $features_selectable as $fcode => $fdata ){
                            foreach ( $fdata['values'] as $vid => $v ){
                                $sku_name[] = ifempty($v['value'],'');
                            }
                        }
                    }

                    if ( !empty($sku_name) ) {
                        $sku_data['name'] = implode(', ', $sku_name);
                    }


                    if ( $new ){
                        $sku_id = $shop_product->sku_id;
                    } else {
                        $sku_data = $skus_model->add($sku_data);
                        $sku_id = $sku_data['id'];
                    }

                    $new = true;
                } else {
                    $sku_data = $skus_model->getSku($sku_id);
                }

                if ( !empty($update_prices) ){
                    foreach ( $update_prices as $price_key => $price_value){
                        $sv = explode(':',$price_key);
                        $sku_data[$sv[1]] = $price_value;
                    }
                }

                if ( !empty($stock) ){
                    if ( isset($stock['stock_info']) ){
                        $sku_data['stock'] = array();

                        if ( !empty($this->data['settings']['reset_stocks']) ){
                            foreach ( self::$stocks as $s_id ){
                                $sku_data['stock'][$s_id] = 0;
                            }
                        }

                        foreach ( $stock['stock_info'] as $stock_id => $in_stock ){
                            $sku_data['stock'][$stock_id] = $in_stock;
                        }
                        unset($stock['stock_info']);
                    } elseif ( array_key_exists('stock', $sku_data) && array_key_exists('count', $stock) ) {
                        unset($sku_data['stock']);
                    }

                    if ( !empty($stock) ){
                        foreach ( $stock as $s_key => $s_val ){
                            $sku_data[$s_key] = $s_val;
                        }
                    }
                }

                $sku_data['yml_up'] = 1;

                if ( !empty($data['sku:sku']) ){
                    $sku_data['sku'] = is_array($data['sku:sku']) ? reset($data['sku:sku']) : $data['sku:sku'];
                }

                $sku_data['product_id'] = $product_id;

                $sku_data['virtual'] = 1;

                $skus_model->update($sku_id, $sku_data);

                foreach ( $values_map as $f_id => $v_id ){
                    $ins = array(
                        'product_id' => $product_id,
                        'feature_id' => $f_id,
                        'value_id'   => $v_id
                    );

                    $selectable_model->insert($ins,2);

                    unset($ins['value_id']);
                    $ins['feature_value_id'] = $v_id;

                    $ins['sku_id'] = $sku_id;
                    $product_features_m->insert($ins, 1);
                }

            } elseif ( $up_prices ){
                $skus = $shop_product->skus;
                foreach ( $skus as &$s ){
                    $s['yml_up'] = 1;
                    foreach ( array('price', 'compare_price', 'purchase_price') as $pkey ){
                        $ppkey = 'sku:' . $pkey;
                        if ( array_key_exists($ppkey, $update_prices) ){
                            $s[$pkey] = $update_prices[$ppkey];
                        }
                    }
                }
                unset($s);
                $shop_product->skus = $skus;
                $shop_product->save();
            }

        } else {
           if ( empty($skus) ){
                foreach ( array('price', 'compare_price', 'purchase_price') as $pkey ){
                    $ppkey = 'sku:' . $pkey;
                    if ( array_key_exists($ppkey, $update_prices) ){
                        $shop_product->$pkey = $update_prices[$ppkey];
                        $sku[$pkey] = $update_prices[$ppkey];
                    }
                }

                if ( !is_null($available) ){
                    $sku['available'] = $available;
                }

                if ( !array_key_exists('price',$sku) ){
                    $sku['price'] = 0;
                }

                if ( !array_key_exists('available', $sku) ){
                    $sku['available'] = 1;
                }

                if (!$product_id){
                    $shop_product->skus = array($sku_id => $sku);
                    $shop_product->save();
                } else {
                    $shop_product->save();
                    $sku['product_id'] = $shop_product->getId();
                    $sku_model->update($shop_product->sku_id, $sku);
                }
           } else {
               $shop_product->save();
           }
        }


       $product_id = $shop_product->getId();

       if ( !empty($images) && !(($this->data['settings']['images'] === self::IMAGES_UPLOAD_NEWPROD) && !$new) ) {
           if ($this->data['settings']['images'] === self::IMAGES_UPLOAD_DELOLD) {
               $this->productImagesModel->deleteByProducts(array($product_id), true);
           }

           if ( !is_array($images) ){
               $images = array((string) $images);
           }

           $img_index = 0; $max_imgs = (int) ifempty($this->data['settings']['max_img'],0);
           $separate  = array_key_exists('separator', $this->data);
           foreach ($images as $picture){
               if ( $separate ){
                   $picture = explode($this->data['separator'], $picture);
                   foreach ( $picture as $pic ){
                       if ( $max_imgs && ($img_index >= $max_imgs) ){
                           break;
                       }

                       $this->data['images'][] = $img_index . '{x}' . $product_id . '{x}' . trim($pic) . (($sku_type === shopProductModel::SKU_TYPE_SELECTABLE) && $sku_id ? '{x}' . $sku_id : '');
                       ++$img_index;
                   }
               } else {
                   if ( $max_imgs && ($img_index >= $max_imgs) ){
                       break;
                   }

                   $this->data['images'][] = $img_index . '{x}' . $product_id . '{x}' . trim($picture) . (($sku_type === shopProductModel::SKU_TYPE_SELECTABLE) && $sku_id ? '{x}' . $sku_id : '');
                   ++$img_index;
               }
           }
       }

        unset($images);

        return true;
    }

    public function getSku($product_id, $sku_code){
        $sql    = "SELECT `id` FROM `shop_product_skus` WHERE `product_id` = i:pid AND `sku` = s:sku LIMIT 1";
        $sku_id = $this->product_model->query($sql, array('pid' => $product_id, 'sku' => $sku_code))->fetchField('id');

        static $sku_model;
        if ( !isset($sku_model) ){
            $sku_model = new shopProductSkusModel();
        }

        return $sku_id ? $sku_model->getSku($sku_id) : array();
    }

    public function typeId($type_name){

        if ( empty($this->_cache['ptypes'][$type_name]) ){
            static $type_model;

            if ( !isset($type_model) ){
                $type_model = new shopTypeModel();
            }


            if ( !($type = $type_model->getByField('name', $type_name)) ){
                $data = array(
                    'name' => $type_name,
                    'icon' => 'box',
                    'cross_selling' => 'alsobought'
                );

                $this->_cache['ptypes'][$type_name] = $type_model->insert($data);
            } else {
                $this->_cache['ptypes'][$type_name] = $type['id'];
            }
        }

        return $this->_cache['ptypes'][$type_name];
    }

    public function markUp($price){
        if ( !empty($this->markup['steps']) ){
            $set = false;
            foreach ( $this->markup['steps'] as $m ){
                if ( $price <= $m['limit'] ){
                    $set = true;
                    if ( $m['type'] === '0' ){
                        $price += $m['rate'];
                        break;
                    } else {
                        $price += ($price/100) * $m['rate'];
                        break;
                    }
                }
            }

            if ( !$set && !empty($this->markup['default']['rate']) ){
                if ( $this->markup['default']['type'] === '0' ){
                    $price += $this->markup['default']['rate'];
                } else {
                    $price += ($price/100) * $this->markup['default']['rate'];
                }
            }

        }
        return $price;
    }

    public function featureById($id, $type_id  = null){
        if ( !isset($this->_cache['features'][$id]) ){
            $this->_cache['features'][$id] = $this->feature_model->select('id,code,multiple,selectable,type')->where('id = ' . $id)->fetchAssoc();

            if ( $type_id && !empty($this->_cache['features'][$id]) ){
                $this->addToType($this->_cache['features'][$id]['id'], $type_id);
            }
        }
        return $this->_cache['features'][$id];
    }

    public static function suggestUniqueUrl($url, $try_steps = null){
        $in_use = '';
        $try_steps = !$try_steps ? 20 : $try_steps;
        for ($try = 0; $try <= $try_steps; ++$try) {
            $new_url = $url . ($try > 0 ? ('_' . $try) : '');
            $in_use = self::isProductUrlInUse(array('url' => $new_url, 'id' => 0));
            if (!$in_use) {
                break;
            }
        }

        if ($try <= $try_steps) {
            return $new_url;
        } else {
            return $url;
        }
    }

    public static function isProductUrlInUse($product){
        $col = new shopProductsCollection("search/url={$product['url']}&id!={$product['id']}");
        $count = $col->count();
        if ($count <= 0) {
            return '';
        }
        $found_products = $col->getProducts('id,name', 0, 1);
        $found_product = reset($found_products);
        $template = _w('The URL <strong>:url</strong> is already in use by another product (<a href="?action=products#/product/:another_product_id/" target="_blank" class="bold">:another_product_name</a>). You may still save this product with the same URL, but the storefront will display only one (any) product by this URL.');
        return str_replace(
            array(':url', ':another_product_id', ':another_product_name'),
            array($product['url'], $found_product['id'], htmlspecialchars($found_product['name'])),
            $template
        );
    }

    public function importImage(){
        $result = true;
        if ( $link = reset($this->data['images']) ){
            $parts = explode('{x}', $link);

            if ( !empty($this->data['settings']['enforce_protocol']) && !empty($this->data['settings']['enforce_protocol_option']) ){
                if ( $this->data['settings']['enforce_protocol_option'] === '1' ){
                    $parts[2] = str_replace('http://', 'https://', $parts[2]);
                } elseif ($this->data['settings']['enforce_protocol_option'] === '2') {
                    $parts[2] = str_replace('https://', 'http://', $parts[2]);
                }
            }

            $result = $this->setImage($parts[0], $parts[1], $parts[2], ifempty($this->data['settings']['images']) == 2, ifset($parts[3]));
            array_shift($this->data['images']);
        }

        return $result;
    }

    public function getCategoryId( $external_id ){
        if ( empty($this->_cache['categories_map'][$external_id]) ){
            $sql     = "SELECT `id` FROM `shop_category` WHERE `yml_id` = s:external_id LIMIT 1";
            $this->_cache['categories_map'][$external_id] = $this->cat_prod_model->query($sql,array('external_id' => $external_id))->fetchField('id');
        }

        return $this->_cache['categories_map'][$external_id];
    }

    public static function productCategories($product){
        static $model;
        if ( !isset($model) ){
            $model = new waModel();
        }

        $result = array();
        if ( $product->getId() ){
            $sql = "SELECT c.id FROM `shop_category_products` cp JOIN shop_category c ON cp.category_id = c.id
            WHERE cp.product_id = i:id ORDER BY c.left_key";
            $data = $model->query($sql, array('id' => $product['id']))->fetchAll('id');

            if ($data) {
                foreach ( $data as $d ){
                    $result[$d['id']] = $d['id'];
                }

                if ($product['category_id'] && !empty($result[$product['category_id']]) ){
                    $first = $result[$product['category_id']];
                    unset($result[$product['category_id']]);
                    $result = array($first => $first) + $result;
                }
            }
        }

        return !empty($result) ? $result : array();
    }


    public function valueId($feature, $value){
        if ( !$value ) return false;

        $value_id = null;
        if ( empty($this->_cache['values'][$value]) ){
            $this->_cache['values'][$value] = shopFeatureModel::getValuesModel($feature['type'])->getId($feature['id'], $value, $feature['type']);
        }

        return $this->_cache['values'][$value];
    }

    public function getFeature( $name, $multiple = 0, $selectable = 0 ){
        $name = ucfirst(trim(strtolower($name)));

        if ( !isset($this->_cache['f_names'][$name]) ){
            $feature = $this->feature_model->getByField('name' , $name);
            if ( !$feature ){
                $feature = array(
                    'name' =>  $name,
                    'code' =>  strtolower(shopHelper::transliterate($name)),
                    'type' =>  shopFeatureModel::TYPE_VARCHAR,
                    'selectable' => $selectable,
                    'multiple' => $multiple
                );

                $feature_id = $this->feature_model->save($feature);
                $feature['id'] = $feature_id;

                if ($feature_id){
                    $this->addToType($feature_id);
                }
            }

            $this->_cache['f_names'][$name] = $feature['id'];
            unset($feature);
        }

        return $this->_cache['f_names'][$name];
    }

    public function addToType($feature_id, $type_id = null){
        if ( empty($type_id) ){
            $type_id = ifset($this->data['settings']['product_type']);
        }

		if ( $feature_id && $type_id ){
            static $model;
        	if ( !isset($model) ){
				$model = new shopTypeFeaturesModel();
			}
            $type_data = array('type_id' => $type_id,'feature_id' => $feature_id);
			$model->insert($type_data, 2);
		}
	}

	public static function makeName($file, $ext='', $index, $is_url = false) {
        if ($is_url){
            $_file = explode('?', $file);
            $file  = reset($_file);
        }

		$name = preg_replace('@[^a-zA-Zа-яА-Я0-9\._\-]+@', '', basename(urldecode($file)));
		$name_parts = explode(".", $name);
		$ext  = end($name_parts);
		if (empty($ext) || !in_array(strtolower($ext), array('jpeg', 'jpg', 'png', 'gif'))) {
			$img_type = exif_imagetype($file);
			switch ($img_type){
				case IMAGETYPE_JPEG:
					$ext = 'jpg';
					break;

				case IMAGETYPE_PNG:
					$ext = 'png';
					break;

				case IMAGETYPE_GIF:
					$ext = 'gif';
					break;

				default:
					$ext = 'jpeg';
			}
			$name .= '.' . $ext;
		}

		$name = $index . $name;

		return $name;
	}

    public static function secureUrl($url){
        $d = parse_url(urldecode($url));
        return ifempty($d['scheme'], 'http') . '://' . $d['host'] . implode('/', array_map('rawurlencode', explode('/', $d['path']))) . (!empty($d['query']) ? '?' . $d['query'] : '');
    }

    public function setImage($index, $product_id, $file, $only_new = true, $sku_id = null){

        $res = true;

		static $model;

		if ($file && $product_id) {

			if (!$model) {
				$model = new shopProductImagesModel();
			}

			$safe_url = self::secureUrl($file);
			$_is_url  = filter_var($safe_url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED) !== false;

            $extension = pathinfo(urldecode($file), PATHINFO_EXTENSION);
			$search = array(
				'product_id' => $product_id,
				'ext'		 => $extension
			);

			try {
				$name = self::makeName($file, $search['ext'], $index, $_is_url);
				if ($_is_url) {
					$pattern = sprintf('@/(%d)/images/(\\d+)/\\2\\.(\\d+(x\\d+)?)\\.([^\\.]+)$@', $search['product_id']);
					if (preg_match($pattern, $file, $matches)) {
						$image = array(
							'product_id' => $matches[1],
							'id'         => $matches[2],
							'ext'        => $matches[5],
						);
						if ((strpos($file, shopImage::getUrl($image, $matches[3])) !== false) && $model->countByField($image)) {
							$file = null;
						}
					}
				}

				$search['original_filename'] = $name;
				if ( $file && $only_new && $model->countByField($search) ){
					$file = null;
				}

				if ($file) {
					if ($_is_url) {
						$upload_file = wa()->getTempPath('csv/upload/images/');

						$upload_file .= waLocale::transliterate($name, 'en_US');
						$this->download($safe_url, $upload_file);
						$file = $upload_file;
					}

					$config = wa()->getConfig();

					if (file_exists($file) && ($image = waImage::factory($file))) {
                        $image_changed = false;
						$event = wa()->event('image_upload', $image);
						if ($event) {
							foreach ($event as $result) {
								if ($result) {
									$image_changed = true;
									break;
								}
							}
						}

						$data = array(
							'product_id'        => $product_id,
							'upload_datetime'   => date('Y-m-d H:i:s'),
							'width'             => $image->width,
							'height'            => $image->height,
							'size'              => filesize($file),
							'original_filename' => $name,
							'filename'          => '',
							'ext'               => $extension,
						);

						if ( !$only_new && ($exists = $model->getByField($search)) ) {
							$data = array_merge($exists, $data);
							$thumb_dir = shopImage::getThumbsPath($data);
							$back_thumb_dir = preg_replace('@(/$|$)@', '.back$1', $thumb_dir, 1);
							$paths[] = $back_thumb_dir;
							waFiles::delete($back_thumb_dir); // old backups
							if (file_exists($thumb_dir)) {
								if (!(waFiles::move($thumb_dir, $back_thumb_dir) || waFiles::delete($back_thumb_dir)) && !waFiles::delete($thumb_dir)){
									throw new waException(_w("Error while rebuild thumbnails"));
								}
							}
						}

						if (empty($data['id'])) {
							$image_id = $data['id'] = $model->add($data);
						} else {
							$image_id = $data['id'];
							$model->updateById($image_id, $data);
						}

						if (!$image_id) {
							$this->error("Database error " . $product_id . ' - ' . $file);
                            return $res;
						}

                        if ( (++$this->data['img_k'] % 4) === 0 ){
                            $res = false;
                        }

                        if ( $sku_id && $image_id && !$index ){
                            static $skus_model;
                            if ( !isset($skus_model) ){
                                $skus_model = new shopProductSkusModel();
                            }

                            $skus_model->updateById($sku_id, array('image_id' => $image_id));
                        }

						$image_path = shopImage::getPath($data);
						if ((file_exists($image_path) && !is_writable($image_path)) || (!file_exists($image_path) && !waFiles::create($image_path))) {
							$model->deleteById($image_id);
							throw new waException(
								sprintf("The insufficient file write permissions for the %s folder.", substr($image_path, strlen($config->getConfig()->getRootPath())))
							);
						}

						if ($image_changed) {
							$image->save($image_path);
							if ($config->getOption('image_save_original') && ($original_file = shopImage::getOriginalPath($data))) {
								waFiles::copy($file, $original_file);
							}
						} else {
							waFiles::copy($file, $image_path);
						}
						shopImage::generateThumbs($data, $config->getImageSizes());
					} else {
						$this->error(sprintf('Invalid image file %s', $file));
					}
				} elseif ($file) {
					$this->error(sprintf('File %s not found', $file));
				}

			} catch (waException $e) {
				$this->error($e->getMessage());
			}

			if ($_is_url && $file && file_exists($file)) {
				waFiles::delete($file);
			}
		}

		return $res;
	}

    public function download($url, $file, $n = 1, $auth = false){
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        if ( file_exists($file) ){
            waFiles::delete($file);
        }

        $headers = array();
        $headers[] = "Connection: keep-alive";
        $headers[] = "Pragma: no-cache";
        $headers[] = "Cache-Control: no-cache";
        $headers[] = "Upgrade-Insecure-Requests: 1";
        $headers[] = "Dnt: 1";
        $headers[] = "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
        $headers[] = "Accept-Encoding: gzip, deflate";
        $headers[] = "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,ro;q=0.6";
        $headers[] = "Cookie: PHPSESSID=6v79gnbrun4rpe3l1shqkunkn7; f_referer=https%3A%2F%2Fwww.google.ru%2F; _ym_uid=1528219457130625104; _ga=GA1.2.1582923881.1528219458; _gid=GA1.2.1346112713.1528219458; _ym_isad=1";

        $follow = false;
        if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')) {
            curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
            $follow = true;
        }

        if ( $auth && ($this->data['settings']['source_type'] === 'remote') && !empty($this->data['settings']['http_auth']) && !empty($this->data['settings']['http_login']) && !empty($this->data['settings']['http_pass']) ){
            curl_setopt($ch, CURLOPT_USERPWD, rawurlencode($this->data['settings']['http_login']) . ':' . rawurlencode($this->data['settings']['http_pass']));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FILE, fopen($file, 'w'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_exec($ch);

        if ( !$follow && ($n < 3) && ($redirectURL = curl_getinfo($ch, CURLINFO_REDIRECT_URL)) ){
            curl_close($ch);
            $this->download($redirectURL, $file, $n + 1);
        } else {
            curl_close($ch);
        }
    }

    protected function dget( $index ){
        $return  = null;

        if ( isset($this->data[$index]) ) {
            if ( is_array($this->data[$index]) ) {
                return isset($this->data[$index][$this->data['stage']]) ? $this->data[$index][$this->data['stage']] : $this->data[$index];
            } else {
                return $this->data[$index];
            }
        } else {
            throw new waException('Cannot get value from data. Undefined offset: ' . $index);
        }
    }

    protected function set( $key , $value , $stage = false ) {
        if ( $stage ) {
            $this->data[$key][$this->data['stage']] = $value;
        } else {
            $this->data[$key] = $value;
        }
    }


    protected function isLastStage(){
        $stages     = $this->dget('stages');
        $stage      = $this->dget('stage');
        $last_key   = sizeof($stages) - 1;

        if ( ($key = array_search( $stage , $stages , true )) !== false ) {
            return $key === $last_key;
        } else {
            throw new waException('Undefined stage');
        }
    }


    protected function isDoneStage(){
        return $this->dget('offset') >= $this->dget('total_count');
    }

    protected function nextStage(){
        $stage      = $this->dget('stage');
        $stage_pos  = array_search( $stage , $this->data['stages'] , true );

        if ( $stage_pos === false ) {
            throw new waException('Cannot set next stage because current stage [' . $stage . '] was not found in stages list');
        }

        $next = $stage_pos + 1;
        if ( isset($this->data['stages'][$next]) ) {
            $this->onStageEnd($stage);
            $this->data['stage'] = $this->data['stages'][$next];
            $this->initReader();
        } else {
            throw new waException('Cannot set next stage. Undefined index: ' . ($stage_pos + 1));
        }
    }

    public function onStageEnd($stage){
        switch ($stage){
            case self::STAGE_CATEGORY:
                if ( !empty($this->data['settings']['duplicate_as_child']) && !empty($this->data['move']) ){
                    $parent_id = $this->data['settings']['parent_id'];
                    foreach ( $this->data['move'] as $id => $url ){
                        $this->category_model->move($id, null, $parent_id);
                    }
                    unset($this->data['move']);
                }

                if ( !empty($this->data['undefined_parents']) ){
                    unset($this->data['undefined_parents']);
                }
                break;
        }
    }

    public function quietExecute($profile_id) {
        $result = null;
        try {
            ob_start();
            $this->_processId = $profile_id;
            $this->cli = true;
            $this->init();

            $this->restore();

            $is_done = $this->isDone();
            while (!$is_done) {
                $this->step();
                $is_done = $this->isDone();
            }
            $this->removeSession();
            $_POST['cleanup'] = true;
            $this->finish(null);

            $out = ob_get_clean();
            $result = array(
                'success' => $this->exchangeReport(),
            );

            if ( $this->reader ){
                $this->reader->close();
            }

        } catch (waException $ex) {
            if ($ex->getCode() == '302') {
                $result = array(
                    'warning' => $ex->getMessage(),
                );
            } else {
                $result = array(
                    'error' => $ex->getMessage(),
                );
            }
        }
        return $result;
    }

    public function exchangeReport(){
        $interval = '—';
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
            $interval = sprintf('%02d ч %02d мин %02d с', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60);
        }

        $template = "Импорт из YML; Профиль - %s. \nВремя выполнения:\t%s";
        $report = sprintf($template, $this->processId, $interval);
        if (!empty($this->data['memory'])) {
            $memory = $this->data['memory'] / 1048576;
            $report .= sprintf("\nПотребление памяти:\t%0.3f МБ", $memory);
        }

        if ( isset($this->data['count']['offers']) ){
            $report .= sprintf("\nТоваров обработано: %s", $this->data['count']['offers']);
        }

        if ( isset($this->data['count']['categories']) ){
            $report .= sprintf("\Категорий обработано: %s", $this->data['count']['categories']);
        }

        $profile_id = $this->data['profile_id'];
        $time = time();
        if ( !$profile_id ){
            $settings_model = new waAppSettingsModel();
            $settings_model->set(array('shop','yml'), 'last_cli_time', $time);
        } else {
            $settings = shopYmlHelper::getProfileConfig($profile_id);
            $settings['last_cli_time'] = $time;
            $profiler  = new shopImportexportHelper('yml');
            $profiler->setConfig($settings,$profile_id);
        }

        return $report;
    }

    /**
     * Get product id by name or yml_id
     * @param string
     * @param string
     * @param mixed
     * @return int
     * @throws waException
     */
    private function getProductId($field, $value, $profile_id = null){
        if ( !$field || !in_array($field, array('yml_id', 'name')) ){
            throw new waException('Неизвестный первичный ключ');
        }

        $sql = "SELECT `id` FROM `shop_product` WHERE `" . $field . "` = s:" . $field .
            (!is_null($profile_id) ? " AND `yml_profile_id` = s:profile_id" : (!empty($this->data['settings']['skip_another_profiles']) ? " AND `yml_profile_id` IS NULL" : "")) . " LIMIT 1";

        $data = array($field => $value);
        if ( !is_null($profile_id) ){
            $data['profile_id'] = $profile_id;
        }

        return $this->product_model->query($sql, $data)->fetchField('id');
    }

    private function getProductIdBySku($sku, $profile_id = null){

        if ( empty($sku) ){
            return 0;
        }

        $sql = "SELECT sps.`product_id` FROM `shop_product_skus` AS sps " .
            (!is_null($profile_id) ? " JOIN `shop_product` AS sp
                 ON sps.`product_id` = sp.`id`" : "") .
            "WHERE sps.`sku` = s:sku" . (!is_null($profile_id) ? " AND sp.`yml_profile_id` = i:profile_id" : "") .
            " LIMIT 1";

        $params = array('sku' => $sku);
        if ( !is_null($profile_id) ){
            $params['profile_id'] = $profile_id;
        }

        return (int) $this->product_model->query($sql, $params)->fetchField('product_id');
    }

    protected function isDone() {
        return (array_sum($this->data['offset']) >= array_sum($this->data['total_count'])) && empty($this->data['images']);
    }

}
