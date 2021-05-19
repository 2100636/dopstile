<?php

class logsPhpLogging
{
    const PHP_LOGGING_TIME_LIMIT_SECONDS = 3600;

    private static $setting_cache;

    public function getSetting($key = null)
    {
        if (is_null(self::$setting_cache)) {
            $config_data = $this->getConfigData(true);

            if ($config_data) {
                $value = array(
                    'time' => $config_data[1],
                    'errors' => explode('|', $config_data[2]),
                );
            } else {
                $config_data = $this->getConfigData(false);

                if ($config_data) {
                    $value = array(
                        'errors' => explode('|', $config_data[1]),
                    );
                } else {
                    $value = false;
                }
            }

            self::$setting_cache = $value;
        }

        if (is_null($key)) {
            $result = self::$setting_cache;
        } else {
            if (is_array(self::$setting_cache)) {
                $result = ifset(self::$setting_cache[$key]);
            } else {
                $result = null;
            }
        }

        return $result;
    }

    public function setSetting($enable, $php_errors = null)
    {
        try {
            $system_config_path = $this->getSystemConfigPath();

            if (!is_writable($system_config_path)) {
                throw new Exception(sprintf(_w('Cannot save changes due to insufficient write permissions for file <tt>%s</tt>.'), $system_config_path));
            }

            $current_config = $this->getSystemConfigContents();

            //clean current config

            //first try to delete time-limited config
            $new_config = preg_replace($this->getPhpLoggingConfigRegexp(true), "\n", $current_config, -1, $replaced);

            //if not found, try to delete simple config
            if (!$replaced) {
                $new_config = preg_replace($this->getPhpLoggingConfigRegexp(false), "\n", $current_config, -1, $replaced);
            }

            //if not found, use unchanged default config
            if (!$replaced) {
                $new_config = $current_config;
            }

            //add new config
            if ($enable) {
                if (!$php_errors) {
                    $php_errors = self::getDefaultErrors();
                }

                $with_time_limit = logsHelper::inCloud() && !$this->adminConfigEnabled()
                    || !logsHelper::inCloud() && !waSystemConfig::isDebug();

                $logging_config = str_replace(
                    array('%TIME%', '%TIME_LIMIT%', '%PHP_ERRORS%'),
                    array(time(), self::PHP_LOGGING_TIME_LIMIT_SECONDS, implode('|', $php_errors)),
                    $this->getPhpLogConfigTemplate($with_time_limit)
                );

                $new_config .= "\n";
                $new_config .= $logging_config;
                $new_config .= "\n";
            }

            //add changed config to file
            if ($new_config != $current_config) {
                $result = waFiles::write($system_config_path, $new_config);

                if (!$result) {
                    throw new Exception(sprintf(_w('Cannot save changes due to insufficient write permissions for file <tt>%s</tt>.'), $system_config_path));
                }

                self::$setting_cache = null;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getConfigData($with_time_limit, $config = null)
    {
        if (is_null($config)) {
            $config = $this->getSystemConfigContents();
        }

        preg_match($this->getPhpLoggingConfigRegexp($with_time_limit), $config, $m);

        return $m;
    }

    public function adminConfigEnabled()
    {
        return (bool) wa()->getConfig()->getOption('php_logging_admin');
    }

    public function isExpired()
    {
        $time = $this->getSetting('time');
        return $time && (time() - $time > self::PHP_LOGGING_TIME_LIMIT_SECONDS);
    }

    public static function getDefaultErrors()
    {
        return array(
            'E_ERROR',
            'E_WARNING',
        );
    }

    private function getPhpLogConfigTemplate($with_time_limit)
    {
        $strings = array(
            "@ ini_set('display_errors', 0);",
            "@ ini_set('error_reporting', %PHP_ERRORS%);",
            "@ ini_set('log_errors', 1);",
            "@ ini_set('error_log', './wa-log/php.log');",
        );

        static $configs = array();
        $key = (int) (bool) $with_time_limit;

        if (!isset($configs[$key])) {
            $configs[$key] = $with_time_limit
                ? "if (time() - %TIME% <= %TIME_LIMIT%) {\n    ".implode("\n    ", $strings)."\n}"
                : implode("\n", $strings);
        }

        return $configs[$key];
    }

    private function getSystemConfigPath()
    {
        return wa()->getConfig()->getPath('config').'/SystemConfig.class.php';
    }

    private function getSystemConfigContents()
    {
        static $result;

        if (!$result) {
            $result = file_get_contents($this->getSystemConfigPath());
        }

        return $result;
    }

    private function getPhpLoggingConfigRegexp($with_time_limit)
    {
        static $result = array();
        $key = (int) (bool) $with_time_limit;

        if (!isset($result[$key])) {
            $value = $this->getPhpLogConfigTemplate($with_time_limit);
            $value = preg_split('/\s+/', $value);
            $value = array_map('wa_make_pattern', $value);
            $value = implode('\s+', $value);
            $value = str_replace(
                array('%TIME%', '%TIME_LIMIT%', '%PHP_ERRORS%'),
                array('(\d+)', '\d+', '(E_[^\)]+)'),
                $value
            );
            $value = '/\s*'.$value.'\s*/s';
            $result[$key] = $value;
        }

        return $result[$key];
    }
}
