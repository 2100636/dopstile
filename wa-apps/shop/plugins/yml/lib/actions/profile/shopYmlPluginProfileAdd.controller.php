<?php
class shopYmlPluginProfileAddController extends waJsonController {
    public function execute(){
        $profiles = new shopImportexportHelper('yml');
        
        $config   = array(
            'category'                => 3,
            'images'                  => 3,
            'import_categories'       => 1,
            'searchindentificator'    => 1,
            'searchcatindentificator' => 1,
            'automatization'          => 1,
            'convert_prices'          => 1,
            'skip_another_profiles'   => 0,
            'searchbyname'            => 0,
            'bind_to_profile'         => 0,
            'stock'                   => 0,
            'source_type'             => 'remote',
            'restore'                 => 1,
            'weight_unit'             => 'g',
            'hide_excluded'           => 1,
            'http_auth'               => 0
        );
        
        $name                         = wa('shop')->getConfig()->getGeneralSettings('name');
        
        $this->response['profile_id'] = $profiles->addConfig($name, null, $config);
        $this->response['name']       = $name;
    }
}