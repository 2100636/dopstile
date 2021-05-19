<?php

class logsBackendRenameController extends waViewController
{
    public function execute()
    {
        if (waRequest::isXMLHttpRequest()) {
            $path = waRequest::post('path');
            $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);

            try {
                if (!strlen($path)) {
                    throw new logsInvalidDataException();
                }

                if (!strlen($name)) {
                    throw new Exception(_w('Empty name.'));
                }

                if (preg_match('/[\\\\|\/]+/', $name)
                    || preg_match('/^\.+/', $name)
                    || preg_match('/\.{2,}/', $name)
                ) {
                    throw new Exception(_w('Invalid name.'));
                }

                $full_path = logsHelper::getFullPath($path);

                if (!logsItemFile::check($full_path)) {
                    throw new Exception(_w('Invalid name.'));
                }

                $path_parts = explode('/', $path);
                $last_path_part = array_pop($path_parts);
                $first_path_part = implode('/', $path_parts);
                $new_path_short = (strlen($first_path_part) ? $first_path_part.'/' : '').$name;
                $new_path = logsHelper::getFullPath($new_path_short);

                if ($last_path_part === $name) {
                    throw new Exception(_w('New name matches the old one.'));
                }

                if (file_exists($new_path) && is_file($new_path)) {
                    throw new Exception(_w('A file with this name exists. Enter another name.'));
                }

                if (dirname($full_path) !== dirname($new_path)) {
                    throw new Exception(_w('Invalid name.'));
                }

                if (!logsItemFile::check($new_path, false)) {
                    throw new Exception(_w('Invalid name.'));
                }

                //check before waFiles::move()
                $is_dir = is_dir($full_path);

                //suppress warnings in PHP log
                $result = @waFiles::move($full_path, $new_path);

                if (!$result) {
                    throw new Exception(sprintf(_w('Cannot rename to “%s”. Invalid name or insufficient permissions.'), $name));
                }

                //disable publishing for old path, because renaming will break previously published URL
                //if necessary, a user will re-enable published status after renaming
                $published_model = new logsPublishedModel();
                $published_model->deleteByField(array(
                    'path' => $path,
                ));

                $redirect_url = array(
                    'path' => $new_path_short,
                );

                if (!$is_dir) {
                    $download_url = $redirect_url;
                    $download_url['action'] = 'download';
                    $redirect_url['action'] = 'file';
                }

                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'name' => $name,
                        'path' => $new_path_short,
                        'redirect_url' => '?'.http_build_query($redirect_url),
                    ),
                );

                if (!$is_dir) {
                    $response['data']['download_url'] = '?'.http_build_query($download_url);
                }

                echo json_encode($response);
            } catch (Exception $e) {
                echo json_encode(array(
                    'status' => 'fail',
                    'errors' => array(
                        $e->getMessage(),
                    ),
                ));
            }
        } else {
            $this->executeAction(new logsDialogRenameAction());
        }
    }
}
