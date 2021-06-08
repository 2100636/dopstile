<?php
class shopYmlPluginSnapshotMakeController extends waJsonController {
    static  $limit = 50000;
    private $offset;
    private $profile_id;

    private $done = false;

    private $settings;

    public $source_type;

    public function array_insert_before($key, array $array, $new_key, $new_value) {
        if (array_key_exists($key, $array)) {
            $new = array();

            foreach ($array as $k => $value) {
                if ($k === $key) {
                    $new[$new_key] = $new_value;
                }
                $new[$k] = $value;
            }

            return $new;
        }
        return $array;
    }

    public function execute(){
        $this->profile_id   = waRequest::request('profile_id',0, waRequest::TYPE_INT);
        $this->offset       = waRequest::post('offset', 0, waRequest::TYPE_INT);

        $settings           = shopYmlHelper::getProfileConfig($this->profile_id);

        $zip_file           = waRequest::post('zip_file');

        if ( empty($settings['source_type']) ){
            $this->response['error'] = 'Не указан тип источника';
            return false;
        }

        $this->settings = $settings;

        $this->source_type = $settings['source_type'];

        $nodes_count        = 0;
        $file_path          = !$zip_file ? shopYmlHelper::ymlPath($this->profile_id, $settings) : $zip_file;
        $shot_data          = array();

        if ( $this->offset === -1 ){
            $force_update = waRequest::post('reset', 0, waRequest::TYPE_INT);
            $cache_path   = shopYmlHelper::snapshotPath($this->profile_id, $settings['source_type']);

            if ( !$force_update && file_exists($cache_path) ){
                $shot_data = include($cache_path);
                if ( !empty($shot_data) ){
                    $this->done = true;
                }
            }

            if ( !$this->done &&  ($force_update || empty($shot_data)) ){
                if ( ($settings['source_type'] === 'remote') ){
                    if ( empty($settings['url']) ){
                        $this->response['error'] = _w('No file link specified');
                        return false;
                    }

                    $this->download($settings['url'], $file_path);
                }

                $this->offset = 0;

                if (!file_exists($file_path)){
                    $this->response['error'] = _w('File not found');
                    return false;
                }

                $zip_file = shopYmlHelper::extract($this->profile_id, $file_path, $settings);
                if ( $zip_file ){
                    $this->response['zip_file'] = $zip_file;
                }
            }

        } elseif ($this->offset >= 0) {
            $shot_data  = $this->collect($file_path, $nodes_count);
            $tmp_path   = wa()->getDataPath('plugins/yml/' . $this->profile_id . '/' . $this->source_type . '/', true, 'shop') . 'tmp.snapshot.php';

            if ( ($this->offset === 0) && !$nodes_count ){
                if ( $zip_file ){
                    waFiles::delete($zip_file);
                }

                $this->response['error'] = 'Файл не соответствует стандарту YML';
                return false;
            }

            if ( !$nodes_count || ($nodes_count < self::$limit) ){
                $this->done = true;
                waFiles::delete($tmp_path, true);

                if ( $zip_file ){
                    waFiles::delete($zip_file);
                }
            } else {
                waUtils::varExportToFile($shot_data, $tmp_path);
                $shot_data = null;
            }

            $this->offset += $nodes_count;
        }

        if ( !empty($shot_data) ){
            $cache_path   = shopYmlHelper::snapshotPath($this->profile_id, $settings['source_type']);
            waUtils::varExportToFile($shot_data, $cache_path);
        }

        if ( $zip_file ){
            $this->response['zip_file'] = $zip_file;
        }

        if ( $this->done ){
            $this->response['html'] = !empty($shot_data) ? $this->fetchSnapshot($shot_data, $settings) : false;
        } else {
            $this->response['offset'] = $this->offset;
        }
    }

    public function download($url, $file, $n = 1){
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

        if ( ($this->settings['source_type'] === 'remote') && !empty($this->settings['http_auth']) && !empty($this->settings['http_login']) && !empty($this->settings['http_pass']) ){
            curl_setopt($ch, CURLOPT_USERPWD, rawurlencode($this->settings['http_login']) . ':' . rawurlencode($this->settings['http_pass']));
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

    public function collect($file_path, &$nodes){
        $reader     = new XMLReader();

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$reader->open($file_path, null, LIBXML_NONET)) {
            waFiles::delete($file_path);
            return false;
        }

        $shot_data        = array();
        $last_inserted    = null;
        $key              = null;

        $tn = array(
            XMLReader::CDATA => 1,
            XMLReader::TEXT  => 1
        );

        $path  = array(); $d = 0; $p = ''; $prev = null;

        while ( $reader->read() ){
            $depth = $reader->depth;

            if ( !($reader->name === 'offer' && $reader->depth === 3 && $reader->nodeType === XMLReader::ELEMENT) ){
                if ( $depth > $d ){
                    $path[$d] = $d . ':' . $p;
                } elseif ( $depth < $d ){
                    end($path);
                    while ( $path && (key($path) >= $depth) ){
                        array_pop($path);
                        end($path);
                    }
                }

                $d = $reader->depth;
                $p = $reader->name;
            } else {
                break;
            }
        }

        if ( $this->offset > 0 ){
            $i = 0;

            do {
                if ( $reader->nodeType === XMLReader::ELEMENT ){
                    $depth = $reader->depth;

                    if ( $depth > $d ){
                        $path[$d] = $d . ':' . $p;
                    } elseif ( $depth < $d ){
                        end($path);
                        while ( $path && (key($path) >= $depth) ){
                            array_pop($path);
                            end($path);
                        }
                    }

                    $d = $reader->depth;
                    $p = $reader->name;

                    if ( ++$i > $this->offset ){
                        break;
                    }

                    $prev = $reader->nodeType;
                }
            } while ($reader->read());

            $tmp_path = wa()->getDataPath('plugins/yml/' . $this->profile_id . '/' . $this->source_type . '/', true, 'shop') . 'tmp.snapshot.php';
            if ( file_exists($tmp_path) ){
                $shot_data = include($tmp_path);
                if ( !is_array($shot_data) ){
                    $shot_data = array();
                }
            }
        }

        $wl   = array(XMLReader::ELEMENT => 1, XMLReader::END_ELEMENT => 1) + $tn;
        do {
            if ( isset($wl[$reader->nodeType]) ){
                if ( $reader->nodeType === XMLReader::ELEMENT ){
                    $name     = $reader->localName;
                    $depth    = $reader->depth;

                    if ( ($name === 'param') && ($param_name = $reader->getAttribute('name')) ){
                        $param_name = str_replace(array('"', "'"), '_', $param_name);
                        $name .= '::' . $param_name;
                    }

                    if ( $depth > $d ){
                        $path[$d] = $d . ':' . $p;
                    } elseif ( $depth < $d ){
                        end($path);
                        while ( key($path) >= $depth ){
                            array_pop($path);
                            end($path);
                        }
                    }

                    if ( !($reader->isEmptyElement && !$reader->hasAttributes) ) {
                        $key = implode("\\", $path) . ($path ? "\\" : '') . $depth . ':' . $name;

                        if (empty($shot_data[$key])) {
                            $last_inserted = $key;

                            $insert = true;
                            if ($path) {
                                $parent_close = implode("\\", $path) . '/';
                                if (!empty($shot_data[$parent_close])) {
                                    $shot_data = $this->array_insert_before($parent_close, $shot_data, $key, array('value' => null, 'type' => null));
                                    $insert = false;
                                }
                            }

                            if ($insert) {
                                $shot_data[$key] = array('value' => null, 'type' => null);
                            }

                            if ($reader->isEmptyElement) {
                                $shot_data[$key]['empty'] = 1;
                            }
                        } elseif ( empty($shot_data[$key]['value']) && !$reader->isEmptyElement ){
                            $last_inserted = $key;
                        } else {
                            $last_inserted = null;
                        }

                        if ($reader->hasAttributes) {
                            if (!is_array($shot_data[$key])) {
                                $shot_data[$key] = array(
                                    'value' => $shot_data[$key],
                                    'attrs' => array()
                                );
                            }

                            while ($reader->moveToNextAttribute()) {
                                $shot_data[$key]['attrs'][$reader->name] = array('value' => $reader->value, 'type' => null);
                            }

                            $reader->moveToElement();
                        }

                        if ( $reader->isEmptyElement ){
                            $last_inserted = false;
                        }
                    }

                    $d = $reader->depth;
                    $p = $reader->name;

                    ++$nodes;

                    if ( $nodes === self::$limit ){
                        break;
                    }

                } elseif ( $last_inserted && isset($tn[$reader->nodeType]) ){
                    $shot_data[$last_inserted]['value'] = $reader->value;
                } elseif ( $reader->nodeType === XMLReader::END_ELEMENT ){
                    if ( !empty($last_inserted) ){
                        $shot_data[$last_inserted]['end'] = 1;
                        $last_inserted = null;
                    } elseif (($reader->nodeType === $prev) || ($last_inserted === false)) {

                        if ( $reader->depth < $d ){
                            end($path);
                            while ( $path && (key($path) >= $reader->depth) ){
                                array_pop($path);
                                end($path);
                            }

                            $d = $reader->depth;
                            $p = $reader->name;
                        }

                        if ( $reader->depth > 3 ) {
                            $end_key = implode("\\", $path) . (!empty($path) ? "\\" : '') . $reader->depth . ':' . $reader->localName . '/';

                            if ( empty($shot_data[$end_key]) ){
                                $shot_data[$end_key] = 1;
                            }
                        }
                    }
                }

                $prev = $reader->nodeType;
            }

        } while ($reader->read());

        $reader->close();

        return $shot_data;
    }

    public function fetchSnapshot($shot_data, $settings){
        $view = wa()->getView();
        $view->assign(array('profile_id' => $this->profile_id, 'snapshot' => $shot_data));

        $map = array();
        $map_path = shopYmlHelper::mapPath($this->profile_id, $settings['source_type']);

        if ( file_exists($map_path) ){
            $map = include($map_path);
        }

        $default_map = false;
        if ( empty($map) ){
            $map_path = shopYmlHelper::mapPath($this->profile_id, $settings['source_type'], true);
            if ( file_exists($map_path) ){
                $map = include($map_path);
                $default_map = true;
            }
        }

        $view->assign('is_default_map', $default_map);
        $view->assign('map', $map);

        $max_inputs = (int) @ini_get('max_input_vars');
        if ( !$max_inputs || ($max_inputs < 0) ) {
            $max_inputs = 500;
        }

        $max_inputs -= 10;
        if ( $max_inputs < 10 ){
            $max_inputs = 10;
        } elseif ( $max_inputs > 1500 ){
            $max_inputs = 1000;
        }

        $view->assign('max_inputs', $max_inputs);

        $feature_model = new shopFeatureModel();
        $features      = $feature_model->select('id,name,multiple')->fetchAll('id');
        $view->assign('features', $features);

        $feature_types = shopFeatureModel::getTypes();
        $view->assign('feature_types', $feature_types);

        return $view->fetch(wa()->getAppPath('plugins/yml/templates/actions/snapshot/', 'shop') . 'Snapshot.html');
    }

}