<?php

class logsDialogPublishedFileUpdateStatusController extends waJsonController
{
    public function execute()
    {
        try {
            if (!$this->getRights('publish_files')) {
                throw new Exception(_w('You have no permissions to publish or unpublish log files.'));
            }

            $path = waRequest::post('path', '', waRequest::TYPE_STRING_TRIM);

            if (!strlen($path)) {
                throw new logsInvalidDataException();
            }

            if (!logsItemFile::check($path)) {
                throw new logsInvalidDataException();
            }

            $status = waRequest::post('status', null, waRequest::TYPE_INT);

            if (is_null($status)) {
                throw new logsInvalidDataException();
            }

            $full_path = logsHelper::getFullPath($path);

            if (!is_readable($full_path)) {
                throw new Exception(_w('This file is not available any more. Reload this page.'));
            }

            $published_model = new logsPublishedModel();
            $data = array(
                'path' => $path,
            );

            if ($status) {
                //make sure no duplicate paths are being saved
                if (!$published_model->countByField($data)) {
                    do {
                        $hash = waString::uuid();
                    } while ($published_model->countByField(array(
                        'hash' => $hash,
                    )) > 0);

                    $password = logsHelper::generatePassword();

                    $data['hash'] = $hash;
                    $data['password'] = $password;
                    $published_model->insert($data);
                    $this->logAction('file_publish', $path);
                }

                $url_data = $published_model->getByField(array(
                    'path' => $path
                ));
                $this->response['url'] = wa()->getRouteUrl('logs/frontend/fileView', $url_data, true);
                $this->response['password'] = $password;
            } else {
                $published_model->deleteByField($data);
                $this->logAction('file_unpublish', $path);
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
