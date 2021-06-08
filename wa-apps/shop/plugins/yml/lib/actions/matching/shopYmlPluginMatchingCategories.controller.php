<?php
class shopYmlPluginMatchingCategoriesController extends waJsonController {

    /**
     * @var XMLReader
     */
    protected $reader;

    public function execute(){        
        $profile_id   = waRequest::post('profile_id', 0, waRequest::TYPE_INT);
        $settings     = shopYmlHelper::getProfileConfig($profile_id);
        $path         = shopYmlHelper::ymlPath($profile_id, $settings);
        $force_update = waRequest::post('reset', 0, waRequest::TYPE_INT);
        $parent_id    = waRequest::post('parent_id', false, waRequest::TYPE_STRING_TRIM);
        $depth        = waRequest::post('depth', 0, waRequest::TYPE_INT);

        if ( !file_exists($path) || $force_update ){
            if ( empty($settings['url']) ) {
                throw new waException('Не указана ссылка на файл');
            }

            waFiles::upload($settings['url'], $path);
        }

        $zip_path = shopYmlHelper::extract($profile_id, $path, $settings);
        if ( $zip_path ){
            $path = $zip_path;
        }

        $list       = array();
        $from_cache = false;

        $cache_path = wa()->getDataPath('plugins/yml/' . $profile_id, false, 'shop') . '/categ_list' . ($parent_id ? $parent_id : '') . '.php';
        if ( file_exists($cache_path) && !$force_update ){
            $list = include($cache_path);
            $from_cache = !empty($list);
        }

        $intuitive = false;

        if ( empty($list) && file_exists($path) ){
            $this->initReader($path);
            $list = $this->getList($parent_id);

            if ( empty($list) && !$parent_id ){
                $this->initReader($path);
                $list = $this->getList(true);
                $intuitive = true;
            }
        } else if  ($list && !$parent_id ){
            $intuitive = true;
        }

        if ( !$intuitive ){
            $this->countTree($path, $list);
        }
                
        if ( !empty($list) ){
            if ( !$from_cache ){
                waUtils::varExportToFile($list, $cache_path);
            }

            $tree = shopYmlHelper::makeTree($list, $intuitive);

            if ( !$parent_id ){
                $view = wa()->getView();

                $view->assign('file_tree', $tree);
                $view->assign('profile_id', $profile_id);

                $template_path = wa()->getAppPath('plugins/yml/templates/actions/matching/') . 'Categories.html';
                $html = $view->fetch($template_path);
            } else {
                $html = shopYmlPlugin::getHtmlTree($tree, $depth, $profile_id, $parent_id);
            }

            $this->response['file_tree'] = $tree;
            $this->response['html'] = $html;
        }

        if ( !empty($zip_path) ){
            waFiles::delete($zip_path);
        }
    }

    public function countTree($path, &$tree){
        $this->initReader($path);
        while ($this->reader->name === 'category'){
            $parent_id = (string) $this->reader->getAttribute('parentId');

            if ( $parent_id && !empty($tree[$parent_id]) ){
                if ( empty($tree[$parent_id]['count']) ){
                    $tree[$parent_id]['count'] = 0;
                }

                ++$tree[$parent_id]['count'];
            }

            if ( !$this->reader->next('category') ){
                break;
            }
        }
    }

    public function initReader($path = null){
        if ($this->reader){
            $this->reader->close();
        } else {
            $this->reader = new XMLReader();
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if ($this->reader->open($path)){
            while ($this->reader->read() && !(($this->reader->depth === 3) && ($this->reader->name === 'category')));
        } else {
            throw new waException('Невозможно открыть файл');
        }
    }

    public function getList($parent_id = null){
        $list = array();

        while ( $this->reader->name === 'category' ){
            $category = simplexml_load_string($this->reader->readOuterXml());

            $x = array(
                'id'        => (string) $category['id'],
                'parent_id' => isset($category['parentId']) ? (string) $category['parentId'] : 0,
                'name'      => (string) $category
            );

            if ( ($parent_id === true) || (!$parent_id && empty($x['parent_id'])) || ($parent_id && ($x['parent_id'] === $parent_id)) ){
                $list[$x['id']] = $x;
            }

            if ( !$this->reader->next('category') ){
                break;
            }
        }

        return $list;
    }
    
}