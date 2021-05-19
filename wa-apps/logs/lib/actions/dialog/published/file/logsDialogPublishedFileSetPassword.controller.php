<?php

class logsDialogPublishedFileSetPasswordController extends waJsonController
{
    public function execute()
    {
        $path = waRequest::post('path', '', waRequest::TYPE_STRING_TRIM);

        try {
            if (!strlen($path)) {
                throw new logsInvalidDataException();
            }

            $full_path = logsHelper::getFullPath($path);

            if (!logsItemFile::check($full_path)) {
                throw new logsInvalidDataException();
            }

            $published_model = new logsPublishedModel();
            $published_file = $published_model->getByField(array(
                'path' => $path,
            ));

            if (!$published_file) {
                throw new Exception(_w('This file is not published. Reload this page and publish the file.'));
            }

            $password = logsHelper::generatePassword();
            $published_model->updateByField(array(
                'path' => $path,
            ), array(
                'password' => $password,
            ));
            $this->logAction('published_file_set_password', $path);

            $this->response['password'] = $password;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
