<?php

class logsBackendDeleteController extends waJsonController
{
    public function execute()
    {
        $path = waRequest::post('path', '', waRequest::TYPE_STRING_TRIM);

        try {
            if (!strlen($path)) {
                throw new logsInvalidDataException();
            }

            if (!$this->getRights('delete_files')) {
                throw new Exception(sprintf(
                    _w('Insufficient access rights for user with id = %u to delete log files or directories.'),
                    $this->getUserId()
                ));
            }

            $full_path = logsHelper::getFullPath($path);
            $available = logsItemFile::check($full_path);

            if (!$available) {
                throw new Exception(sprintf(_w('%s item is not available for deletion.'), $path));
            }

            $is_dir = is_dir($full_path);

            if ($is_dir) {
                $dir_files = logsHelper::listDir($full_path, true);
                if ($dir_files) {
                    foreach ($dir_files as &$dir_file) {
                        $dir_file = $path.'/'.logsHelper::normalizePath($dir_file);
                    }
                    unset($dir_file);
                }
            }

            $deleted = waFiles::delete($full_path);

            if (!$deleted) {
                throw new Exception(sprintf(_w('Could not delete %s.'), $path));
            }

            if (!$is_dir || $dir_files) {
                $published_model = new logsPublishedModel();
                $published_model->deleteByField(array(
                    'path' => $is_dir ? $dir_files : $path,
                ));
            }

            if ($is_dir) {
                if (!empty($dir_files)) {
                    foreach ($dir_files as $dir_file) {
                        $this->logAction('file_delete', $dir_file);
                    }
                }
            } else {
                $this->logAction('file_delete', $path);
            }

            $update_total_size = (bool) waRequest::get('update_size', 0, waRequest::TYPE_INT);

            if ($update_total_size && (!$is_dir || !empty($dir_files))) {
                $total_size = logsHelper::getTotalLogsSize();
                $is_large = logsHelper::isLargeSize($total_size);

                //remove outdated indicator from cache
                if (!$is_large) {
                    $apps_count = wa()->getStorage()->read('apps-count');
                    unset($apps_count['logs']);
                    wa()->getStorage()->set('apps-count', $apps_count);
                }

                $this->response['total_size'] = logsHelper::formatSize($total_size);
                $this->response['total_size_class'] = $is_large ? 'total-size total-size-large' : 'total-size';
                $this->response['is_large'] = $is_large;
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
