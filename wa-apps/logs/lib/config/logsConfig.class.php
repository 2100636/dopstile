<?php

class logsConfig extends waAppConfig
{
    public function explainLogs($logs)
    {
        foreach ($logs as $id => $log) {
            if (in_array($log['action'], array('file_delete', 'file_publish', 'file_unpublish'))
            && strlen(ifset($log['params']))) {
                $logs[$id]['params_html'] = 'wa-log/'.$log['params'];
            }
        }
        return $logs;
    }

    public function onCount()
    {
        $php_logging = new logsPhpLogging();

        $in_cloud = logsHelper::inCloud();
        $is_debug = waSystemConfig::isDebug();
        $php_logging_admin = $php_logging->adminConfigEnabled();
        $unlimited_logging_allowed = !$in_cloud || $php_logging_admin;

        $time_config_data = $php_logging->getConfigData(true);
        $php_logging_enabled = $php_logging->getSetting();
        $php_errors = $php_logging->getSetting('errors');

        //update PHP logging config on debug mode setting toggle
        if ($unlimited_logging_allowed && $php_logging_enabled && ($is_debug && $time_config_data || !$is_debug && !$time_config_data)) {
            $php_logging->setSetting(true, $php_errors);
        } elseif ($php_logging->isExpired()) {
            $php_logging->setSetting(false);
        }

        //notify user on large logs size
        $csm = new waContactSettingsModel();
        $large_logs_notify = $csm->getOne(wa()->getUser()->getId(), 'logs', 'large_logs_notify');
        $large_logs_notify = strlen($large_logs_notify) ? (bool) (int) $large_logs_notify : true;    //enabled by default
        if ($large_logs_notify) {
            $total_size = logsHelper::getTotalLogsSize();
            if (logsHelper::isLargeSize($total_size)) {
                return array(
                    'count' => _wd('logs', '1 GB+'),
                    'url' => wa()->getConfig()->getBackendUrl(true).'logs/?action=files&mode=size',
                );
            }
        }
    }
}
