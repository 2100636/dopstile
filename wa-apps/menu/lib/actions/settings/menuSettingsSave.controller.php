<?php


class menuSettingsSaveController extends waJsonController
{

    public function execute()
    {

        $config = $this->getConfig();
        $config_file = $config->getConfigPath('config.php');
        $settings = waRequest::post('settings', array(), waRequest::TYPE_ARRAY);
        waUtils::varExportToFile($settings, $config_file);

        //$asm = new waAppSettingsModel();
        //$asm->set($this->getAppId(), 'test', '');
    }

}