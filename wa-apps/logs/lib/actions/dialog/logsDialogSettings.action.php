<?php

class logsDialogSettingsAction extends waViewAction
{
    public function execute()
    {
        try {
            if (!$this->getRights('change_settings')) {
                throw new Exception(_w('You have no permissions to change settings.'));
            }

            $php_logging = new logsPhpLogging();
            $php_log_setting = $php_logging->getSetting();
            $php_log_errors = $php_logging->getSetting('errors');

            if (!$php_log_errors) {
                $php_log_errors = logsPhpLogging::getDefaultErrors();
            }

            $csm = new waContactSettingsModel();
            $large_logs_notify_setting = $csm->getOne(wa()->getUser()->getId(), 'logs', 'large_logs_notify');

            $controls = array(
                'large_logs_notify' => array(
                    'title' => _w('Notify me on large logs size'),
                    'control_type' => waHtmlControl::CHECKBOX,
                    'value' => 1,
                    'checked' => strlen($large_logs_notify_setting) ? (bool) (int) $large_logs_notify_setting : true,    //enabled by default
                    'description' => sprintf(
                        _w('Show indicator %s next to app icon in main menu when total logs size exceed 1&nbsp;GB.'),
                        sprintf(
                            '<span class="indicator red" style="top: 0; padding-top: 5px;">%s</span>',
                            _w('1 GB+')
                        )
                    ),
                ),
                'php_log' => array(
                    'title' => _w('Enable PHP error log'),
                    'control_type' => waHtmlControl::CHECKBOX,
                    'value' => 1,
                    'checked' => !empty($php_log_setting),
                    'description' => $this->getPhpLoggingDescription($php_log_setting),
                    'class' => 'php_log_setting',
                ),
                'php_log_errors' => array(
                    'title' => _w('Types of PHP errors'),
                    'control_type' => waHtmlControl::GROUPBOX,
                    'options' => array(
                        array(
                            'value' => 'E_ALL',
                            'title' => _w('all'),
                            'description' => 'E_ALL',
                        ),
                        array(
                            'value' => 'E_ERROR',
                            'title' => _w('fatal errors'),
                            'description' => 'E_ERROR',
                        ),
                        array(
                            'value' => 'E_WARNING',
                            'title' => _w('warnings'),
                            'description' => 'E_WARNING',
                        ),
                    ),
                    'value' => $php_log_errors,
                    'field_style' => $php_log_setting ? '' : 'display: none',
                ),
                'root_path' => array(
                    'title' => _w('Show relative file paths instead of absolute paths in error logs'),
                    'namespace' => 'hide',
                    'control_type' => waHtmlControl::GROUPBOX,
                    'options' => array(
                        'frontend' => _w('in published files'),
                        'backend' => _w('in backend'),
                    ),
                    'value' => array_fill_keys(logsHelper::getHideSetting('root_path'), 1),
                ),
                'ip' => array(
                    'title' => _w('Hide IP addresses in error logs'),
                    'namespace' => 'hide',
                    'control_type' => waHtmlControl::GROUPBOX,
                    'options' => array(
                        'frontend' => _w('in published files'),
                        'backend' => _w('in backend'),
                    ),
                    'value' => array_fill_keys(logsHelper::getHideSetting('ip'), 1),
                ),
            );

            foreach ($controls as $control_name => &$control) {
                if (isset($control['namespace'])) {
                    $control['namespace'] = array('settings', $control['namespace']);
                } else {
                    $control['namespace'] = 'settings';
                }

                $style = isset($control['field_style']) ? sprintf(' style="%s"', $control['field_style']) : '';

                $control += array(
                    'control_wrapper' => '<div class="field field-'.str_replace('_', '-', $control_name)
                        .'"'.$style.'><div class="name">%s</div><div class="value">%s<br>%s<br><br></div></div>',
                    'title_wrapper' => '%s',
                    'description_wrapper' => '<span class="hint">%s</span>',
                );
                $control = waHtmlControl::getControl($control['control_type'], $control_name, $control);
            }
            unset($control);

            $this->view->assign('controls', implode('', $controls));
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }
    }

    private function getPhpLoggingDescription($php_log_setting)
    {
        $debug_mode_setting_name = _ws('Developer mode');
        $end_time = $php_log_setting && !empty($php_log_setting['time']) ? date('H:i', $php_log_setting['time'] + 3600) : null;

        $php_logging = new logsPhpLogging();
        $php_logging_admin_config_enabled = $php_logging->adminConfigEnabled();
        $system_settings_url = wa()->getConfig()->getBackendUrl(true).'webasyst/settings/';

        $description = _w('PHP error messages will be saved to file <tt>wa-log/<b>php.log</b></tt>.');
        $description .= '<br><br>';

        if (logsHelper::inCloud()) {
            $description .= '<span class="bold black">';

            if ($php_logging_admin_config_enabled) {
                if ($php_log_setting) {
                    $description .= _w('PHP errors are being logged without limitations, because <tt>php_logging_admin</tt> parameter is enabled in Logs’ configuration file.');
                    $description .= ' '.sprintf(
                        _w('To make PHP errors be logged only during 1 hour, disable <tt>php_logging_admin</tt> parameter in file <tt>%s</tt>.'),
                        'wa-config/apps/logs/config.php'
                    );
                } else {
                    $description .= _w('PHP errors will be logged without limitations, because <tt>php_logging_admin</tt> parameter is enabled in Logs’ configuration file.');
                    $description .= ' '.sprintf(
                        _w('To make PHP errors be logged only during 1 hour, disable <tt>php_logging_admin</tt> parameter in file <tt>%s</tt>.'),
                        'wa-config/apps/logs/config.php'
                    );
                }
            } else {
                if ($end_time) {
                    $description .= sprintf(_w('PHP errors will be logged during 1 hour only (until %s), to prevent large error logs from occupying server disk space.'), $end_time);
                } else {
                    $description .= _w('PHP errors will be logged during 1 hour only, to prevent large error logs from occupying server disk space.');
                }

                $description .= ' '._w('Re-enable logging after this time expires, if necessary.');
            }

            $description .= '</span> ';
        } else {
            //not in Cloud

            if (waSystemConfig::isDebug()) {
                $description .= '<span class="bold black">';

                if ($php_log_setting) {
                    $description .= sprintf(
                        _w('PHP errors are being logged without limitations, because “%s” is enabled <a href="%s" target="_blank">in&nbsp;system settings</a>.'),
                        $debug_mode_setting_name,
                        $system_settings_url
                    )
                    .' '.sprintf(_w('To make PHP errors be logged only during 1 hour, disable "%s".'), $debug_mode_setting_name);
                } else {
                    $description .= sprintf(
                        _w('PHP errors will be logged without limitations, because “%s” is enabled <a href="%s" target="_blank">in&nbsp;system settings</a>.'),
                        $debug_mode_setting_name,
                        $system_settings_url
                    )
                        .' '.sprintf(_w('To make PHP errors be logged only during 1 hour, disable "%s".'), $debug_mode_setting_name);
                }

                $description .= '</span> ';
            } else {
                $description .= '<span class="bold black">';
                if ($end_time) {
                    $description .= sprintf(
                        _w('PHP errors will be logged during 1 hour only (until %s), to prevent large error logs from occupying server disk space.'),
                        $end_time
                    );
                } else {
                    $description .= _w('PHP errors will be logged during 1 hour only, to prevent large error logs from occupying server disk space.');
                }

                $description .= ' '._w('Re-enable logging after this time expires, if necessary.')
                    .'<br>'
                    .sprintf(
                        _w('This limitation will not be applied if you enable “%s” setting <a href="%s" target="_blank">in&nbsp;system settings</a>.'),
                        $debug_mode_setting_name,
                        $system_settings_url
                    );
                $description .= '</span> ';
            }
        }

        return $description;
    }
}
