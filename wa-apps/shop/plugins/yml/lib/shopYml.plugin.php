<?php
class shopYmlPlugin extends shopPlugin {
    
    protected $params;
    
    static $yml_meta_fields = array(
        '2:name'     => 'Магазин',
        '2:company'  => 'Компания',
        '2:url'      => 'Адрес сайта',
        '2:platform' => 'Платформа',
        '2:email'    => 'Почта'

    );

    public static function getTypeLabel($type_id){
        return ifempty(self::$param_fields[$type_id],
                       ifempty(self::$product_fields[$type_id], $type_id));
    }

    public static function translate($key = null){
        $translates = array();
        $translates['product'] = _w('Basic fields');
        $translates['product_custom_fields'] = _w("Custom product fields");
        $translates['sku'] = _w('SKU fields');
        $translates['sku_custom_fields'] = _w("Custom sku fields");
        $translates['feature'] = 'Характеристики товара';
        //$translates['feature+'] = _w('Add as new feature');

        return $key ? ifempty($translates[$key],'') : $translates;
    }
    
    public function external_id($params){
        $this->params = $params;
        return array('title_suffix' => $this->generate_html());
    }
    
    public function category_external_id(){
        $id     = waRequest::get('category_id');
        $extid  = $this->getExtId($id);
            
        return waHtmlControl::getControl(waHtmlControl::INPUT, 'yml_id', array(
            'value' => $extid,
            'title' => 'YML ID',
            'title_wrapper' => '%s',
            'control_wrapper' => '<div class="field"><div class="name">%s</div><div class="value no-shift">%s%s</div></div>',
        ));
    }
    
    public function categorySave(){
        $id    = waRequest::request('category_id');
        $extid = waRequest::post('yml_id');
        $model = new shopCategoryModel();

        if ( isset($id) && isset($extid) ) {
            $holders = array(
                'yml_id' => $extid,
                'id'     => $id
            );
              
            $sql = "UPDATE `shop_category` SET yml_id = s:yml_id WHERE `id` = s:id";
            $model->exec($sql, $holders);
        }
    }
                                     
    protected function generate_html(){
        
        $path = wa('shop')->getAppStaticUrl('shop/plugins/yml');
        
        $html  = '<link href="'. $path .'css/products.css" rel="stylesheet" type="text/css" />';
        $html .= '<script src="'. $path .'js/products.js"></script>'; 
        $html .= '<div class="hint float-right">';
        $html .= $this->create_input();
        $html .= '</div>';
        
        return $html;
    }
                                    
    protected function create_input(){
        $value = $this->isexist();
        if ($value === false){
            return false;
        } else{
            $html = 'YML ID: <input type="text" name="externid" id="extern_optom" value="'.$value.'"/> <input type="hidden" name="internid"  value="'.$this->params['id'].'"/>';
            $html .= '<div id="extern_optom_save_button"><i class="icon16 disk"></i></div>';
        }
        return $html;
    }
    
    protected function getExtId($id){
        $model = new shopCategoryModel();
        $result = $model->getById($id);
        return ifempty($result['yml_id']);
    }
    
    protected function isexist(){
        $product    = new shopProductModel();
        $product_id = $this->params['id'];
        $value      = $product->getById($product_id);
        return ifempty($value['yml_id']);
    }
    
    public static function cutMetaFields(&$shot_data){
        $result = false;
        if ( $shot_data ){
            foreach ( shopYmlPlugin::$yml_meta_fields as $k => $d){
                if (!empty($shot_data[$k])){
                    if ( empty($shot_data[$k]['currencies']) ){
                        $result[$k] = $shot_data[$k]['value'];
                    } elseif (!empty($shot_data[$k]['currencies']) ){
                        $_v = array();
                        foreach ($shot_data[$k]['currencies'] as $currency){
                            if ( !empty($currency['id']) ){
                                $_v[] = $currency['id'] . (!empty($currency['rate']) ? ' (' . $currency['rate'] . ')' : '');
                            }
                        }
                        
                        $result[$k] = $_v ? implode('; ', $_v) : null;
                    }
                }
                
                if ( array_key_exists($k, $shot_data) ){
                    unset($shot_data[$k]);
                }
            }
        }
        unset($shot_data);
        return $result;
    }
    
    public static function getHtmlTree($tree, $depth = 0, $profile_id = 0, $parent_id = ''){
        $html = '';
        
        if ( !empty($tree) ){            
            static $map;            
            if ( !isset($map) ){
                $map_path = wa()->getDataPath('plugins/yml/' . $profile_id, false, 'shop') . '/categ_map' . ($parent_id ? $parent_id : '') . '.php';
                $map = array();
                if ( file_exists($map_path) ){
                    $map = include($map_path);
                } else {
                    $map = array();
                }
            }
            
            $html = '<ul class="yml-category-tree' . (!$depth ? ' first' : '') . '">';
            foreach ( $tree as $t ) {
                $has_child = !empty($t['children']) || !empty($t['count']);

                $special_class = '';
                if ( isset($map[$t['id']]) ){
                    $special_class = ' matched';
                }

                if ( !empty($t['count']) ){
                    $special_class .= ' dynamic';
                }
                
                $html .= '<li' . ($special_class ? ' class="' . $special_class . '"' : '').  '>
                    <span class="category-name' . ($has_child ? ' has_children' : '') . '" data-depth="'.$depth.'" data-id="' . $t['id'] . '"'. (!empty($t['parent_id']) ? ' data-parent-id="' . $t['parent_id'] . '"' : '') . '>'
                        . '<span class="js-cat-name"><i class="icon16 folder"></i> '
                        . $t['name'] . '</span>'
                        . ($has_child ?'<span class="category-arr">
                            <i class="icon16 darr"></i>
                       </span>' : '') .
                    '</span>';
                
                if ( !empty($t['children']) ){
                    $html .= self::getHtmlTree($t['children'], $depth+1, $profile_id);
                }
                
                $html .= '</li>';
            }
            
            $html .= '</ul>';
        }
        
        return $html;
    }
    
    public function backendProducts(){
        $main = wa()->getPlugin('yml')->getSettings('name');

        $profiles = array();
        if ( $main ){
            $profiles[0] = array(
                'name' => $main
            );
        }

        $profiler      = new shopImportexportHelper('yml');
        $_profiles      =  $profiler->getList();

        if ( $_profiles ){
            $profiles += $_profiles;
        }

        $list          = '';

        if ( $profiles ){
            $list = '<ul class="menu-v with-icons">';
            foreach ( $profiles as $p_id => $p ){
                $list .= '
                        <li data-type="yml_id" id="yml_id-' . $p_id . '">
                            <a href="#/products/hash=yml_id/' . $p_id . '/">
                                <i class="icon16 zone"></i>
                                ' . $p['name'] . '
                            </a>
                        </li>
                    ';
            }
            $list .= '</ul>';
        }
        
        return array(
            'sidebar_section' => '
                <div class="block drop-target" id="s-yml-profiles-block">
                    <h5 class="heading">
                        <i class="icon16 collapse-handler darr" id="s-yml-profiles-handler"></i>
                        Профили YML
                    </h5>
                    
                    <div class="s-collection-list" id="s-yml-profiles-list">
                        ' . $list . '
                    </div>
                </div>

                <script type="text/javascript">
                    $.products.ymlAction = function(){
                        var params = Array.prototype.join.call(arguments, \'/\');
                        params = $.shop.helper.parseParams(params || \'\');
                        
                        this.load(\'?module=products&\' + this.buildProductsUrlComponent(params), this.checkAlerts);
                    };
                </script>');
    }

    public function productsCollection($params){
        $collection = $params['collection'];

        $hash   = $collection->getHash();
        $yml_id = (int) $hash[1];

        $params['auto_title'] = false;
        $config = shopYmlHelper::getProfileConfig($yml_id);

        $collection->addTitle($config['name']);

        if (count($hash) == 2 && $hash[0] == 'yml_id') {
            $collection->addWhere('yml_profile_id = ' . $yml_id);
            return true;
        } else {
            return false;
        }
    }

    public static function formatSize($size) {

        if ( !is_numeric($size) && is_string($size) && file_exists($size) ){
            $size = filesize($size);
        }

        if ($size >= 1073741824){
            $size = number_format($size / 1073741824, 2) . ' GB';
        }elseif ($size >= 1048576){
            $size = number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            $size = number_format($size / 1024, 2) . ' KB';
        } elseif ($size > 1){
            $size = $size . ' bytes';
        } elseif ($size == 1) {
            $size = $size . ' bytes';
        } else {
            $size = '0 bytes';
        }

        return $size;
    }

    public static function pathInfo($path){
        return $path ? pathinfo($path) : array();
    }

    public static function getChilds($category, $breads, $selected_id = null){
        $html = ' <ul class="menu-v with-icons">';

        foreach ($breads[$category['id']] as $sc) {
            $html .= '<li class="dr' . ($sc['id'] == $selected_id ? ' matched' : '') . '" id = "yml-category-' . $sc['id'] . '" data-id="' . $sc['id'] . '" data-depth="' . $sc['depth'] . '" data-type="category">
                <a href="#/products/category_id=' . $sc['id'] . '" class="s-product-list">
                    <span class="name">
                        <i class="icon16 folder" ></i>
                            ' . $sc['name'] . '
                    </span>

                    <i class="icon16 darr overhanging" id="yml-category-' . $sc['id'] . '-handler"></i>
                </a>
                ';

            if ( !empty($breads[$sc['id']]) ){
                $html .= self::getChilds($sc, $breads, $selected_id);
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

}
