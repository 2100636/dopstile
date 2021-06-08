<?php
class shopYmlPluginFeaturesController extends waJsonController {
    public function execute(){        
        $feature_id = waRequest::post('feature_id', 0, waRequest::TYPE_INT);
        $this->response['html'] = self::getFeaturesHtml($feature_id);
    }
    
    public static function getFeaturesHtml($selected_feature_id = null){
        
        $model      = new shopFeatureModel();
        $features   = $model->select('id,name')->order('name ASC')->fetchAll('id');
        $html       = '';
        
        if ( $features ){
            $html = '
                <div class="field">
                    <div class="name"></div>
                    <div class="value">
                        <span class="param-label">Выберите характеристику</span>
                        <select name="param[feature_id]">';
            
            foreach ( $features as $f ){
                $selected = $selected_feature_id == $f['id'] ? ' selected' : '';
                $html .= '<option' . $selected . ' value="' . $f['id'] . '">' . $f['name'] . ' </option>';
            }
            
            $html .= '</select></div></div>';
        }
        
        return $html;
    }
    
}