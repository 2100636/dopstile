<?php

class logsFrontendNavigationAction extends waViewAction
{
    public function execute()
    {
        $actions = array();

        switch (waRequest::param('action')) {
            case 'fileView':
                $download_url = wa()->getRouteUrl('/frontend/fileDownload', array(
                    'hash' => waRequest::param('hash'),
                    'path' => waRequest::param('path'),
                ));

                $actions['download'] = array(
                    'icon_class' => 'download',
                    'url' => $download_url,
                    'title' => _w('Download')
                );

                break;
        }

        $path_parts = logsHelper::getPathParts(waRequest::param('path'), false);

        $this->view->assign('actions', $actions);
        $this->view->assign('item', $path_parts);
    }
}
