<?php

class shopAdvancedparamsPluginBackendSettingsAction extends waViewAction {
    
    public function execute() {
        $plugin = wa(shopAdvancedparamsPlugin::APP)->getPlugin(shopAdvancedparamsPlugin::PLUGIN_ID);
        // получаем все настройки плагина, чтобы передать их в шаблон
        $settings = $plugin->getSettings();
        $this->view->assign('settings', $settings);
    }
} 