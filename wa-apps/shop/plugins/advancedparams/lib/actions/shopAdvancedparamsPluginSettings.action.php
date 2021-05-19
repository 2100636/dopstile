<?php

class shopAdvancedparamsPluginSettingsAction extends waViewAction
{
    public function execute() {
        $this->view->assign('banned_fields', shopAdvancedparamsPlugin::getConfigParam('banned_fields'));
        $this->view->assign('field_types', shopAdvancedparamsPlugin::getConfigParam('field_types'));
        $this->view->assign('field_types_selectable', shopAdvancedparamsPlugin::getConfigParam('field_types_selectable'));
    }
}
