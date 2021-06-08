<?php
class shopYmlPluginMatchingLocalTreeController extends waJsonController {

    /**
     * @var shopCategoryModel
     */
    protected $model;

    public function execute(){
        $local_id    = waRequest::post("local_id", null, waRequest::TYPE_INT);
        $depth       = waRequest::post('depth', 0, waRequest::TYPE_INT);
        $foreign_cat = waRequest::post("foreign_category", array(), waRequest::TYPE_ARRAY);
        $profile_id  = waRequest::post('profile_id', 0, waRequest::TYPE_INT);

        $this->model = new shopCategoryModel();

        $selected_id = 0;
        $view        = wa()->getView();
        $map         = array();
        
        $path = wa()->getDataPath('plugins/yml/' . $profile_id . '/', false, 'shop' ) . '/categ_map.php';
            
        if ( file_exists($path) ){
            $map = include($path);
        }

        if ( !empty($foreign_cat) ){
            if ( !empty($map) && isset($map[$foreign_cat['id']]) ){
                $selected_id = $map[$foreign_cat['id']]['id'];
                $foreign_cat['mode'] = $map[$foreign_cat['id']]['mode'];
            }

            if ( !waRequest::post('no_foreign', 0, waRequest::TYPE_INT) ){
                $view->assign('foreign_cat', $foreign_cat);
            }
        }

        $view->assign('selected_id', $selected_id);
        $path  = wa()->getAppPath('plugins/yml/templates/actions/matching/', 'shop') . 'LocalTree.html';

        $list  = $this->model->getTree($local_id, $depth);

        $root_id = null;
        if ( $selected_id ){
            $bread_crumbs = $this->getRootCategoryId($selected_id);
            if ( $bread_crumbs['root_id'] && !empty($list[$bread_crumbs['root_id']]) ){
                $root_id = (int) $bread_crumbs['root_id'];

                if ( !empty($bread_crumbs['ids']) ){
                    $bread_crumbs['ids'] = array_reverse($bread_crumbs['ids']);

                    $dpth = 1; $r = array();
                    foreach ( $bread_crumbs['ids'] as $id){
                        $r[$id] = $this->model->getTree((int) $id, $dpth);
                        unset($r[$id][$id]);
                        ++$dpth;
                    }

                    waLog::log(var_export($r, true), 'ok2.log');

                    $this->response['breads'] = $r;
                    $view->assign('breadcrumbs', $r);
                }
            }
        }

        $view->assign('root_id' , $root_id);
        
        if ( $local_id && isset($list[$local_id]) ){
            unset($list[$local_id]);
        }

        if ( !empty($list) ){
            foreach ( $list as &$l ){
                $l['children_count'] = (int) $this->model->query('SELECT COUNT(*) AS k FROM `shop_category` WHERE `parent_id` = i:pid', array('pid' => $l['id']))->fetchField('k');
            }
            unset($l);
        }

        $view->assign('categories', $list);        
        $this->response['html'] = $view->fetch($path);        
    }

    public function putChilds(&$category, $category_id, $path_ids, $depth = 1){
        $chlds = $this->model->getTree($category_id, $depth);
        waLog::log(var_export($chlds,true), 'fff.log');
        if ( $chlds ){
            $category[$category_id]['childs'] = $chlds;
            unset($path_ids[$category_id]);

            if ( $path_ids ){
                $next_id = (int) reset($path_ids);
                $this->putChilds($category[$category_id]['childs'], $next_id, $path_ids, $depth+1);
            }
        }
    }

    public function getRootCategoryId($child_id){
        $result    = 0;
        $parent_id = $this->model->select('parent_id')->where('id = ' . intval($child_id))->fetchField('parent_id');


        $ids = array();

        if ( $parent_id ){
            $ids[] = $parent_id;

            $t = true;
            while ($parent = $this->model->getById($parent_id)){
                $t = false;
                if (!empty($parent['parent_id'])){
                    $parent_id = $parent['parent_id'];
                    $ids[] = $parent_id;
                } else {
                    $result = $parent['id'];
                    break;
                }
            }

            if ($t && $parent_id ){
                $result = $parent_id;
            }
        }

        return array( 'ids' => $ids, 'root_id' => $result);
    }
}