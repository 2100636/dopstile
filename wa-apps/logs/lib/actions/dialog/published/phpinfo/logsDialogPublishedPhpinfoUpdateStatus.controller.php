<?php

class logsDialogPublishedPhpinfoUpdateStatusController extends waJsonController
{
    public function execute()
    {
        try {
            if (!$this->getRights('publish_files')) {
                throw new Exception(_w('You have no permissions to publish or unpublish the PHP configuration page.'));
            }

            if (!function_exists('phpinfo')) {
                throw new Exception(_w('Function <tt>phpinfo()</tt> is not available on your server.'));
            }

            $published_model = new logsPublishedModel();
            $data = array(
                'path' => '//phpinfo//',
            );

            $status = waRequest::post('status', null, waRequest::TYPE_INT);

            if ($status) {
                do {
                    $hash = waString::uuid();
                } while ($published_model->countByField(array(
                    'hash' => $hash,
                )) > 0);

                $password = logsHelper::generatePassword();

                $data['hash'] = $hash;
                $data['password'] = $password;
                $published_model->insert($data);
                $this->logAction('phpinfo_publish');

                $this->response['url'] = wa()->getRouteUrl('logs/frontend/phpinfo', array('hash' => $hash), true);
                $this->response['password'] = $password;
            } else {
                $published_model->deleteByField($data);
                $this->logAction('phpinfo_unpublish');
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
