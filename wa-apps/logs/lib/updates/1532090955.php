<?php

$model = new waModel();

try {
    $model->exec('SELECT `password` FROM `logs_published` WHERE 0');
} catch (Exception $e) {
    $model->exec('ALTER TABLE `logs_published` ADD `password` VARCHAR(16) NULL DEFAULT NULL AFTER `hash`');
}

foreach (array(
    'css/logs.css',
    'js/logs.js',
    'lib/actions/backend/logsBackendGetmore.controller.php',
    'lib/actions/dialog/logsDialogPublished.action.php',
    'lib/actions/dialog/logsDialogUpdatePublishedStatus.controller.php',
    'lib/actions/frontend/logsFrontendDownloadFile.controller.php',
    'lib/classes/logsViewAction.class.php',
    'templates/actions/backend/BackendFile.html',
    'templates/actions/backend/BackendGetmore.html',
    'templates/actions/dialog/DialogPublished.html',
    'templates/includes/fileList.html',
) as $file) {
    waFiles::delete(wa()->getAppPath($file, 'logs'));
}

$asm = new waAppSettingsModel();
$asm->del('logs', 'php_log_with_time_limit');
