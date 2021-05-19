<?php

class logsDialogDeleteAction extends waViewAction
{
    public function execute()
    {
        try {
            if (!$this->getRights('delete_files')) {
                throw new Exception(_w('You have no permissions to delete files and directories.'));
            }

            $path = waRequest::get('path');

            $full_path = logsHelper::getFullPath($path);
            $is_dir = is_dir($full_path);

            $item = logsHelper::getPathParts($path);

            $title = $is_dir ? _w('Delete directory') : _w('Delete file');
            $published_model = new logsPublishedModel();

            if ($is_dir) {
                $dir_files = logsHelper::listDir($full_path, true);

                if ($dir_files) {
                    $dir_files_count = count($dir_files);

                    $warnings = array(
                        _w(
                            'Deleting this directory will also delete %u file contained in it.',
                            'Deleting this directory will also delete %u files contained in it.',
                            $dir_files_count
                        )
                    );

                    foreach ($dir_files as &$dir_file) {
                        $dir_file = $path.'/'.logsHelper::normalizePath($dir_file);
                    }
                    unset($dir_file);

                    $published_files_count = $published_model->countByField(array(
                        'path' => $dir_files,
                    ));

                    if ($published_files_count) {
                        if ($dir_files_count == $published_files_count) {
                            $warnings[] = _w(
                                'After deletion, it will no longer be available via a published link.',
                                'After deletion, they will no longer be available via published links.',
                                $dir_files_count
                            );
                        } else {
                            $warnings[] = _w(
                                'After deletion, %u of them will no longer be available via a published link.',
                                'After deletion, %u of them will no longer be available via published links.',
                                $dir_files_count
                            );
                        }
                    }

                    $item['warning'] = implode(' ', $warnings);
                }
            } else {
                $is_published = $published_model->countByField(array(
                    'path' => $path,
                )) > 0;

                if ($is_published) {
                    $item['warning'] = _w('After deletion, this file will no longer be available via a published link.');
                }
            }

            $this->view->assign('path', $path);
            $this->view->assign('item', $item);
            $this->view->assign('title', $title);
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }
    }
}
