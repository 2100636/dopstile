<?php
class shopHidsetPluginSettingsAction extends waViewAction{
    public function execute(){
        $plugin = wa()->getPlugin('hidset');
        $hsets = $plugin->hsets;
        $plugin_sets = [];
        foreach ($plugin->plugins as $plugin_id => $sets){
            $plugin_sets[$plugin_id] = [
                'name' => wa()->getPlugin($plugin_id)->getName(),
                'config' => $plugin->getPluginConfig($plugin_id)
            ];
        }
        ksort($hsets);
        $this->view->assign('hsets', $hsets);
        $this->view->assign('allsets', wa('shop')-> getConfig() -> getOption(null));
        $this->view->assign('plugin_sets', $plugin_sets);
        $this->view->assign('plugins', $plugin->plugins);
    }
}