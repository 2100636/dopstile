<?php
class shopYmlPluginProductsAction extends shopProductsAction {

    public function execute(){
        parent::execute();
        $tmpl = wa()->getAppPath('templates/actions/products/', 'shop') . 'Products.html';
        $this->setTemplate($tmpl);
    }

    protected function getHash(){
        $res = null;
        $category_id = waRequest::get('profile_id', null, waRequest::TYPE_INT);
        if (!is_null($category_id)) {
            $this->collection_param = 'yml_profile_id='.$category_id;
            $res = array('yml_profile_id', $category_id);
        }

        return $res;
    }

    protected function getCollection($hash) {
        $c      = parent::getCollection($hash);
        $yml_id = waRequest::get('profile_id');

        $c->addWhere('yml_profile_id = ' . $yml_id );
        return $c;
    }
}