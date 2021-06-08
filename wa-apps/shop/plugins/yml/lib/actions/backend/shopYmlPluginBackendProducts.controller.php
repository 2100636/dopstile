<?php
class shopYmlPluginBackendProductsController extends waJsonController {
    
    public function execute(){
        
        $data = waRequest::request();
        
        $data['method'] = ifempty($data['method']);
        
        if (!empty($data['method'])){
            $this->{$data['method']}($data);            
        }
        
    }
    
    protected function editextid($data){
        $product         = new shopProduct($data['intid']);
        $product->yml_id = $data['extid'];
        $product->save();
    }
}
