<?php
class shopYmlPluginBackendSetupAction extends waViewAction {
    public function execute(){
        $profile_id    = waRequest::request('profile', 0, waRequest::TYPE_INT);
        $profiler      = new shopImportexportHelper('yml');
        $settings      = shopYmlHelper::getProfileConfig($profile_id);
        $default_title = wa('shop')->getPlugin('yml')->getSettings('name');
        
        $type          =  new shopTypeModel();
        $types         =  $type->getAll();
        $profiles      =  $profiler->getList();
        $root_path     =  wa()->getConfig()->getPath('root');

        $version = wa()->getPlugin('yml')->getVersion();
        $this->view->assign('version', $version);
        
        $category_model = new shopCategoryModel();
        $categories     = $category_model->getFullTree( 'id, name, depth' , true );
        
        $path = wa()->getDataPath('plugins/yml/' . $profile_id . '/', true, 'shop') . 'markup.php';
        if ( file_exists($path) ){
            $markup = include($path);
            $this->view->assign('markup', $markup);
        }
        
        $sql      = "SELECT `code`,`name`, `multiple` FROM `shop_feature` WHERE `type` = 'varchar'";
        $features = $type->query($sql)->fetchAll();

        if (empty($settings['source_type'])){
            $settings['source_type'] = 'remote';
        }
        
        $path = shopYmlHelper::sessionPath($profile_id, $settings['source_type']);
        if ( file_exists($path) ){
            $session = include($path);
            $this->view->assign('session', $session);
        }
        
        $this->view->assign( 'features'      , $features      );
        $this->view->assign( 'categories'    , $categories    );
        $this->view->assign( 'default_title' , $default_title );
        $this->view->assign( 'profiles'      , $profiles      );
        $this->view->assign( 'profile_id'    , $profile_id    );
        $this->view->assign( 'root_path'     , $root_path     );
        $this->view->assign( 'types'         , $types         );
        $this->view->assign( 'settings'      , $settings      );
    }
}