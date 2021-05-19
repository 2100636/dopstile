<?php

class logsBackendActionController extends logsBackendItemController
{
    protected function check()
    {
        logsItemAction::check(waRequest::get('id'));
    }

    protected function getData()
    {
        if (waRequest::isXMLHttpRequest()) {
            $id = waRequest::get('id');

            $action_item = new logsItemAction($id);
            $action = $action_item->get(array(
                'first_line' => waRequest::post('first_line', 0, waRequest::TYPE_INT),
                'last_line' => waRequest::post('last_line', 0, waRequest::TYPE_INT),
                'direction' => waRequest::post('direction'),
                'check' => false,
            ));

            $response = $action;
            unset($response['error']);
            $this->json_response['data'] = $response;

            if ($action['error']) {
                throw new Exception($action['error']);
            }

            $this->json_response['data']['contents'] = (string) new waLazyDisplay(
                new logsItemLinesAction(array(
                    'html' => $action['contents'],
                ))
            );
        } else {
            $this->executeAction(new logsBackendActionAction());
        }
    }
}
