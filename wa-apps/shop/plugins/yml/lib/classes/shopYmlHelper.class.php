<?php
class shopYmlHelper {
    
    static $model;

    public static function ymlPath($profile_id, $settings = array()){
        if ( empty($settings) ){
            $settings = self::getProfileConfig($profile_id);
        }

        if ( $settings['source_type'] === 'server' ){
            if ( !empty($settings['server.file']) && file_exists($settings['server.file']) ){
                return $settings['server.file'];
            }

            return false;
        }

        return self::path( 'plugins/yml/' . $profile_id. '/' . $settings['source_type'], '/' . $profile_id . '.' . $settings['source_type'] . '.yml');
    }

    public static function extract($profile_id, $file_path, $settings, $replace = true){
        $result = false;

        if (function_exists('zip_open') && ($zip = zip_open($file_path)) && is_resource($zip) && ($zip_entry = zip_read($zip))) {
            $path = self::path('plugins/yml/' . $profile_id . '/' . $settings['source_type'],'/');

            $file = $path . 'unzip.yml';

            if ( !$replace && file_exists($file) ){
                zip_entry_close($zip_entry);
                zip_close($zip);

                return $file;
            }

            waFiles::delete($file);

            $zip_fs = zip_entry_filesize($zip_entry);

            if ($z = fopen($file, "w")) {
                $size = 0;
                while ($zz = zip_entry_read($zip_entry, max(0, min(4096, $zip_fs - $size)))) {
                    fwrite($z, $zz);
                    $size += 1024;
                }

                fclose($z);
                zip_entry_close($zip_entry);
                zip_close($zip);

                if ( file_exists($file) ){
                    $result = $file;
                }
            } else {
                zip_entry_close($zip_entry);
                zip_close($zip);
            }
        }

        return $result;
    }

    public static function snapshotPath($profile_id, $type = 'remote'){
        return self::path('plugins/yml/' . $profile_id. '/' . $type, '/' . $profile_id . '.' . $type . '.snapshot.php' );
    }

    public static function mapPath($profile_id, $source_type, $default = false){
        return $default ? wa()->getAppPath('plugins/yml/lib/config/', 'shop') . 'default.map.php' :
            self::path('plugins/yml/' . $profile_id . '/' . $source_type,'/' . $profile_id . '.' . $source_type . '.map.php');
    }

    public static function sessionPath($profile_id, $source_type){
        return self::path('plugins/yml/' . $profile_id . '/' . $source_type . '/', 'session.php');
    }

    public static function path($path, $suffix){
        return wa()->getDataPath($path, true, 'shop') . $suffix;
    }
    
    public static function fetchSelectable($value, $price = null, $value_id = null){
        $result = array('value' => $value);
        if ( !is_null($price) ) $result['price'] = (double) str_replace(',', '.', (string) $price);

        if ( $value_id ){
            $result['id'] = $value_id;
        }

        return $result;
    }
    
    public static function getProfileConfig($profile_id = null){
       if ( $profile_id ){
           $helper = new shopImportexportHelper('yml');
           $config = $helper->getConfig($profile_id);

           if ( empty($config['config']['name']) ){
               $config['config']['name'] = !empty($config['name']) ? $config['name'] : 'Без названия';
           }

            return !empty($config['config']) ? $config['config'] : array();
       }
       
       return wa('shop')->getPlugin('yml')->getSettings();
    }
    
    public static function getMapFields($flat = false, $extra_fields = false){
        $fields = array(
            'product'               => array(
                'yml_id'           => 'Внешний ID',
                'name'             => _w('Product name'),
                'currency'         => _w('Currency'),
                'summary'          => _w('Summary'),
                'description'      => _w('Description'),
                'status'           => _w('Status'),
                'type_id'        => _w('Product type'),
                //'tax_name'         => _w('Taxable'),
                'meta_title'       => _w('Title'),
                'meta_keywords'    => _w('META Keyword'),
                'meta_description' => _w('META Description'),
                'url'              => _w('Storefront link'),
                'images'           => _w('Product images'),
                'video_url'        => _w('Video URL on YouTube or Vimeo'),
                'category_id'      => 'ID категории',
                'group_id'         => 'ID группы',
                'sku'              => 'Артикул товара'
            ),
            //'product_custom_fields' => array(),
            'sku'                   => array(
                'name'           => _w('SKU name'), //2
                'sku'            => _w('SKU code'), //3
                'price'          => _w('Price'),
                'rrc'            => 'РРЦ',
                'available'      => _w('Available for purchase'),
                'compare_price'  => _w('Compare at price'),
                'purchase_price' => _w('Purchase price'),
                'stock'          => _w('In stock')
            ),
            //'sku_custom_fields'     => array(),
            'feature' => array(
                'exists' => 'Загрузить в существующую',
                'auto'   => 'Загрузить автоматически'
            )
        );

        if ($extra_fields) {
            $product_model = new shopProductModel();
            $sku_model     = new shopProductSkusModel();
            $meta_fields = array(
                'product' => $product_model->getMetadata(),
                'sku'     => $sku_model->getMetadata(),
            );
            
            $black_list = array(
                'id',
                'contact_id',
                'create_datetime',
                'edit_datetime',
                'type_id',
                'image_id',
                'image_filename',
                'tax_id',
                'cross_selling',
                'badge',
                'currency',
                'upselling',
                'total_sales',
                'sku_type',
                'sku_count',
                'sku_id',
                'ext',
                'price',
                'compare_price',
                'min_price',
                'max_price',
                'count',
                'rating_count',
                'category_id',
                'base_price_selectable',
                'compare_price_selectable',
                'purchase_price_selectable',
                'rating',
                'yml_profile_id'
            );

            $white_list = array(
                'id_1c' => 'Идентификатор 1C'
            );

            foreach ($meta_fields['product'] as $field => $info) {
                if (!in_array($field, $black_list)) {
                    $name = ifset($white_list[$field], $field);
                    if (!empty($meta_fields['sku'][$field])) {
                        if (!isset($fields['sku'][$field])) {
                            $fields['sku'][$field] = $name;
                        }

                        if (!isset($fields['product'][$field])) {
                            $fields['product'][$field] = sprintf('%s: %s', _w('Product'), $name);
                        }

                    } else {
                        if (!isset($fields['product'][$field])) {
                            $fields['product'][$field] = $name;
                        }
                    }
                }
            }
        }

        $stock_model = new shopStockModel();
        $stocks = $stock_model->getAll('id');
        if ($stocks) {
            foreach ($stocks as $stock_id => $stock) {
                $fields['sku']['stock:'.$stock_id] = _w('In stock').' @'.$stock['name'];
            }
        }

        /**
         * @event product_custom_fields
         * @return array[string]string $return[%plugin_id%]['product'] array
         * @return array[string]string $return[%plugin_id%]['sku'] array
         */
        $custom_fields = wa('shop')->event('product_custom_fields');
        if ($custom_fields) {
            foreach ($custom_fields as $plugin_id => $custom_plugin_fields) {
                # %plugin_id%-plugin became %plugin_id%_plugin
                $plugin_id = preg_replace('@-plugin$@', '_plugin', $plugin_id);
                if (isset($custom_plugin_fields['product'])) {
                    foreach ($custom_plugin_fields['product'] as $field_id => $field_name) {
                        $fields['product_custom_fields'][$plugin_id.':'.$field_id] = $field_name;
                    }
                }
                if (isset($custom_plugin_fields['sku'])) {
                    foreach ($custom_plugin_fields['sku'] as $field_id => $field_name) {
                        $fields['sku_custom_fields'][$plugin_id.':'.$field_id] = $field_name;
                    }
                }
            }
        }


        if ($flat) {
            $fields_ = $fields;
            $fields = array();
            $flat_order = array(
                'product:name',
                'sku:skus:-1:name',
                'sku:skus:-1:sku',
                'product:currency'
            );

            foreach ($flat_order as $field) {
                list($type, $field) = explode(':', $field, 2);
                $fields[$field] = $fields_[$type][$field];
                unset($fields_[$type][$field]);
            }
            $fields += $fields_['sku'];
            $fields += $fields_['product'];
        }

        return $fields;
    }
    
    public static function merge_arrays( array &$array1, array &$array2 ){
        $merged = $array1;
      
        foreach ( $array2 as $key => &$value ){
              if ( is_array($value) && isset ($merged[$key]) && is_array ($merged[$key]) ){
                $merged[$key] = self::merge_arrays( $merged[$key], $value );
              } else {
                $merged[$key] = $value;
              }
        }
      
        return $merged;
    }
    
    public static function makeTree($arr, $intuitive = false){
        $new = array();

        if ( !empty($arr) ){
            foreach ($arr as $a){
                $new[(int) $a['parent_id'] ][] = $a;
            }

            if ( $intuitive && $new && empty($new[0]) ){
                $new[0] = array();

                foreach ( $arr as $arr_id => $ch ){
                    if ( !empty($ch['parent_id']) && empty($arr[$ch['parent_id']]) ){
                        $new[0][] = $ch;

                        if ( isset($new[$ch['parent_id']]) ){
                            unset($new[$ch['parent_id']]);
                        }
                    }
                }
            }

            return !empty($new[0]) ? self::createTree($new, ifempty($new[0], array())) : $arr;
        }

        return array();
    }

    private static function createTree(&$list, $parent){
        $tree = array();
        foreach ($parent as $k=>$l){
            if (isset($list[$l['id']])){
                $l['children'] = self::createTree($list, $list[$l['id']]);
            }
            $tree[$l['id']] = $l;
        }
        return $tree;
    }

    public static function saveProfileConfig($profile_id = 0, $settings ){
        if ( !$profile_id ){
            $model      = new waAppSettingsModel();
            $token      = array('shop', 'yml');

            foreach ( $settings as $k => $v ){
                $model->set($token, $k, $v);
            }

        } else {
            $profiler  = new shopImportexportHelper('yml');
            $profiler->setConfig($settings,$profile_id);
        }
    }

}