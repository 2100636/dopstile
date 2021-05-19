<?php

class logsHelper
{
    const LOGS_SIZE_NOTIFICATION_LIMIT = 1073741824;    //large is 1GB or more

    public static function hideData($log)
    {
        if (self::getHideSetting('root_path', true)) {
            $log = str_replace(
                wa()->getConfig()->getRootPath().DIRECTORY_SEPARATOR,
                '',
                $log
            );
        }

        if (self::getHideSetting('ip', true)) {
            $log = preg_replace(
                '/\b(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\b/',
                'xxx.xxx.xxx.xxx',
                $log
            );
        }

        return $log;
    }

    public static function getHideSetting($key = null, $for_env = false)
    {
        static $values = null;

        if (is_null($values)) {
            $setting = wa()->getSetting('hide');

            if (!strlen($setting)) {
                $values = array(
                    'root_path' => array('frontend'),
                    'ip' => array('frontend'),
                );
            } else {
                $values = json_decode($setting, true);
            }
        }

        if ($key) {
            if (!isset($values[$key])) {
                $values[$key] = array();
            }

            if ($for_env) {
                $result = in_array(wa()->getEnv(), $values[$key]);
            } else {
                $result = $values[$key];
            }
        } else {
            if ($for_env) {
                $result = array();
                foreach ($values as $setting_key => $value) {
                    if (in_array(wa()->getEnv(), $value)) {
                        $result[] = $setting_key;
                    }
                }
                sort($result);
            } else {
                $result = $values;
            }
        }

        return $result;
    }

    public static function getLogsBackendUrl($absolute = true)
    {
        return wa()->getRootUrl($absolute).wa()->getConfig()->getBackendUrl().'/logs/';
    }

    public static function getTotalLogsSize()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $files = self::listDir(wa()->getConfig()->getPath('log'), true);
        $root_log_dir = wa()->getConfig()->getPath('log');
        $result = 0;

        foreach ($files as $file) {
            $result += filesize($root_log_dir.DIRECTORY_SEPARATOR.$file);
        }
        return $result;
    }

    public static function formatSize($size)
    {
        static $locale_decimal_point;

        if (is_null($locale_decimal_point)) {
            $locale_info = waLocale::getInfo(wa()->getLocale());
            $locale_decimal_point = ifset($locale_info['decimal_point'], '.');
        }

        $result = waFiles::formatSize($size, '%0.2f', _w('B,KB,MB,GB'));
        if ($locale_decimal_point != '.') {
            $result = str_replace('.', $locale_decimal_point, $result);
        }
        return $result;
    }

    public static function isLargeSize($value)
    {
        return $value >= self::LOGS_SIZE_NOTIFICATION_LIMIT;
    }

    public static function inCloud()
    {
        return wa()->appExists('hosting') && wa()->getConfig()->getAppConfig('hosting')->getInfo('vendor') == 'webasyst';
    }

    public static function listDir($dir, $recursive = false)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $result = waFiles::listdir($dir, $recursive);
        return array_filter($result, array(__CLASS__, 'filterFiles'));
    }

    private static function filterFiles($path)
    {
        return basename($path) != '.htaccess';
    }

    /**
     * @param boolean $reverse True turns / to DIRECTORY_SEPARATOR, false turns DIRECTORY_SEPARATOR to /
     */
    public static function normalizePath($path, $reverse = false)
    {
        if (DIRECTORY_SEPARATOR != '/') {
            if ($reverse) {
                return str_replace('/', DIRECTORY_SEPARATOR, $path);
            } else {
                return str_replace(DIRECTORY_SEPARATOR, '/', $path);
            }
        } else {
            return $path;
        }
    }

    public static function log($message)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        waLog::log($message, 'logs/errors.log');
    }

    public static function getFullPath($path)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $full_paths = array();

        if (!isset($full_paths[$path])) {
            $full_paths[$path] = wa()->getConfig()->getPath('log').DIRECTORY_SEPARATOR.self::normalizePath($path, true);
        }

        return $full_paths[$path];
    }

    public static function getPathParts($path, $with_logs_root = true)
    {
        $path_parts = explode('/', $path);
        $name = array_pop($path_parts);

        if (is_dir(self::getFullPath($path))) {
            $name .= '/';
        }

        $folder = implode('/', $path_parts);
        $logs_root = $with_logs_root ? 'wa-log/' : '';

        return array(
            'folder' => $logs_root.(strlen($folder) ? $folder.'/' : ''),
            'name' => $name,
        );
    }

    public static function generatePassword()
    {
        return substr(preg_replace('/\W/', '', waString::uuid()), 0, 16);
    }

    public static function redirect()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        wa()->getResponse()->redirect(wa()->getAppUrl());
    }
}
