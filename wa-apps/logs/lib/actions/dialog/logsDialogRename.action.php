<?php

class logsDialogRenameAction extends waViewAction
{
    public function execute()
    {
        $path = waRequest::get('path');

        try {
            if (!$this->getUser()->getRights('logs', 'rename')) {
                throw new Exception(_w('You have no permissions to rename files and directories.'));
            }

            $full_path = logsHelper::getFullPath($path);

            if (!logsItemFile::check($full_path)) {
                throw new logsInvalidDataException();
            }

            $published_model = new logsPublishedModel();
            $is_dir = is_dir($full_path);

            if (!$is_dir) {
                $is_published = $published_model->countByField(array(
                    'path' => $path,
                )) > 0;
            }

            $item = array(
                'path' => $path,
                'edit_name' => basename($path),
                'title' => $is_dir ? _w('Rename directory') : _w('Rename file'),
                'is_published' => !empty($is_published),
            );

            $item += logsHelper::getPathParts($path);

            if ($is_dir) {
                $dir_files = logsHelper::listDir($full_path, true);
                foreach ($dir_files as &$dir_file) {
                    $dir_file = $path.'/'.logsHelper::normalizePath($dir_file);
                }
                unset($dir_file);

                if ($dir_files) {
                    $published_files_count = $published_model->countByField(array(
                        'path' => $dir_files,
                    ));

                    if ($published_files_count) {
                        $item['warning'] = _w(
                            'After renaming, %u published file in this directory will no longer be available via a published link.',
                            'After renaming, %u published files in this directory will no longer be available via published links.',
                            $published_files_count
                        );
                    }
                }
            } else {
                if ($is_published) {
                    $item['warning'] = _w('After renaming, this file will no longer be available via a published link.');
                }
            }

            $this-> view->assign('item', $item);
        } catch (Exception $e) {
            $this-> view->assign('error', $e->getMessage());
        }
    }
}
