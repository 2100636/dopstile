<?php

class logsDialogPublishedRemovePasswordController extends waJsonController
{
    public function execute()
    {
        $path = waRequest::post('path', '', waRequest::TYPE_STRING_TRIM);

        try {
            if (!strlen($path)) {
                throw new logsInvalidDataException();
            }

            $published_model = new logsPublishedModel();

            if ($published_model->countByField(array(
                'path' => $path,
            )) < 1) {
                throw new logsInvalidDataException();
            }

            $published_model->updateByField(array(
                'path' => $path,
            ), array(
                'password' => '',
            ));
            $this->logAction('published_file_remove_password', $path);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
