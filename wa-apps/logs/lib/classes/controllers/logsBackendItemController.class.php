<?php

abstract class logsBackendItemController extends waViewController
{
    protected $json_response = array(
        'data' => array(),
        'errors' => array(),
    );

    public function execute()
    {
        try {
            $this->check();
            $this->getData();
        } catch (Exception $e) {
            $message = $e->getMessage();

            if (waRequest::isXMLHttpRequest()) {
                if (!strlen($message)) {
                    $message = _w('Invalid data. Reload this page and try again.');
                }

                $this->json_response['errors'][] = $message;
            } else {
                if (strlen($message)) {
                    throw new waException($message);
                } else {
                    logsHelper::redirect();
                }
            }
        }

        if (waRequest::isXMLHttpRequest()) {
            echo json_encode($this->json_response);
        }
    }

    abstract protected function check();
    abstract protected function getData();
}
