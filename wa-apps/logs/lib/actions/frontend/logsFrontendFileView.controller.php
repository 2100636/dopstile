<?php

class logsFrontendFileViewController extends logsFrontendPublishedFileController
{
    protected function getData()
    {
        if (waRequest::post('direction')) {
            $file_item = new logsItemFile($this->path);

            $file = $file_item->get(array(
                'first_line' => waRequest::post('first_line', 0, waRequest::TYPE_INT),
                'last_line' => waRequest::post('last_line', 0, waRequest::TYPE_INT),
                'direction' => waRequest::post('direction'),
                'last_eol' => waRequest::post('last_eol'),
                'file_end_eol' => waRequest::post('file_end_eol'),
                'check' => false,
            ));

            if ($file['error']) {
                throw new Exception($file['error']);
            }

            unset($file['error']);
            $this->json_response['data'] = $file;

            $this->json_response['data']['contents'] = (string) new waLazyDisplay(
                new logsItemLinesAction(array(
                    'html' => $file['contents'],
                ))
            );

            $this->json_response['data']['last_eol'] = $file['last_eol'];
            $this->json_response['data']['file_end_eol'] = $file['file_end_eol'];
        } else {
            $this->executeAction(new logsFrontendFileViewAction(array(
                'path' => $this->path,
            )));
        }
    }
}
