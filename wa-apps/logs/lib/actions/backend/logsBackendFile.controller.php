<?php

class logsBackendFileController extends logsBackendItemController
{
    protected function check()
    {
        logsItemFile::check(waRequest::get('path'));
    }

    protected function getData()
    {
        if (waRequest::isXMLHttpRequest()) {
            $path = waRequest::get('path');

            $file_item = new logsItemFile($path);
            $file = $file_item->get(array(
                'first_line' => waRequest::post('first_line', 0, waRequest::TYPE_INT),
                'last_line' => waRequest::post('last_line', 0, waRequest::TYPE_INT),
                'direction' => waRequest::post('direction'),
                'last_eol' => waRequest::post('last_eol'),
                'file_end_eol' => waRequest::post('file_end_eol'),
                'check' => false,
            ));

            $response = $file;
            unset($response['error']);
            $this->json_response['data'] = $response;

            if ($file['error']) {
                throw new Exception($file['error']);
            }

            $this->json_response['data']['contents'] = (string) new waLazyDisplay(
                new logsItemLinesAction(array(
                    'html' => $file['contents'],
                ))
            );

            $this->json_response['data']['last_eol'] = $file['last_eol'];
            $this->json_response['data']['file_end_eol'] = $file['file_end_eol'];
            $this->json_response['data']['file_size'] = logsHelper::formatSize(filesize(logsHelper::getFullPath($path)));
        } else {
            $this->executeAction(new logsBackendFileAction());
        }
    }
}
