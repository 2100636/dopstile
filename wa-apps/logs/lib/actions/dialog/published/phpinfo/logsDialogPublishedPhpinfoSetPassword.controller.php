<?php

class logsDialogPublishedPhpinfoSetPasswordController extends waJsonController
{
    public function execute()
    {
        $path = '//phpinfo//';

        try {
            $published_model = new logsPublishedModel();
            $published = $published_model->getByField(array(
                'path' => $path,
            ));

            if (!$published) {
                throw new Exception(_w('PHP configuration page is not published. Reload this page and try again.'));
            }

            $password = logsHelper::generatePassword();
            $published_model->updateByField(array(
                'path' => $path,
            ), array(
                'password' => $password,
            ));

            $this->logAction('published_phpinfo_set_password', $path);

            $this->response['password'] = $password;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
