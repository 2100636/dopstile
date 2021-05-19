<?php

class logsDialogSettingsSaveController extends waJsonController
{
    public function execute()
    {
        try {
            if (!$this->getRights('change_settings')) {
                throw new Exception(_w('You have no permissions to change settings.'));
            }

            $settings = waRequest::post('settings', array(), waRequest::TYPE_ARRAY);

            //PHP logging
            $php_logging = new logsPhpLogging();
            $error = $php_logging->setSetting(!empty($settings['php_log']), $settings['php_log_errors']);

            if (strlen($error)) {
                throw new Exception($error);
            }

            if (isset($settings['php_log'])) {
                unset($settings['php_log']);
            }

            unset($settings['php_log_errors']);

            //personal notification on large logs size
            $csm = new waContactSettingsModel();
            $csm->set(wa()->getUser()->getId(), 'logs', 'large_logs_notify', (int) ifset($settings['large_logs_notify']));

            if (isset($settings['large_logs_notify'])) {
                unset($settings['large_logs_notify']);
            }

            //other settings
            if (!isset($settings['hide'])) {
                //save empty array to differentiate it from null when there is no value yet and a default value must be used
                $settings['hide'] = array(
                    'root_path' => array(),
                    'ip' => array(),
                );
            }

            $app_settings_model = new waAppSettingsModel();
            foreach ($settings as $name => $value) {
                $app_settings_model->set('logs', $name, is_array($value) ? json_encode($value) : $value);
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
