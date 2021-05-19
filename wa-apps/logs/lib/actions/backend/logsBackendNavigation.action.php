<?php

class logsBackendNavigationAction extends logsViewAction
{
    private $backend_url;

    public function __construct()
    {
        parent::__construct();
        $this->backend_url = logsHelper::getLogsBackendUrl(false);
    }

    public function execute()
    {
        $path = waRequest::get('path');
        $action = waRequest::get('action');

        $back_url = $this->getBackUrl();

        if ($action == 'file') {
            $total_size = filesize(logsHelper::getFullPath($path));
        } else {
            if ($action != 'action') {
                $total_size = logsHelper::getTotalLogsSize();
            }
        }

        if (in_array($action, array('file', 'action', 'actions')) || strlen($path)) {
            if (in_array($action, array('file', 'action'))) {
                $this->view->assign('back', strpos($back_url, $this->backend_url) === 0);
            }

            if (strlen($path)) {
                $breadcrumbs = $this->getBreadcrumbs($path);
            } elseif (in_array($action, array('action', 'actions'))) {
                $breadcrumbs = array(
                    array(
                        'name' => _w('logs'),
                        'url' => '',
                    ),
                    array(
                        'name' => _w('user actions'),
                        'url' => http_build_query(array(
                            'action' => 'actions',
                        )),
                    ),
                );

                if ($action == 'action') {
                    $breadcrumbs[] = array(
                        'name' => logsItemAction::getName(waRequest::get('id')),
                        'url' => http_build_query(array(
                            'action' => 'action',
                            'id' => waRequest::get('id'),
                        )),
                    );
                }
            }

            $this->view->assign('breadcrumbs', $breadcrumbs);
        }

        if (isset($total_size)) {
            $total_size_classes = array('total-size');
            if (waRequest::get('action') == 'file') {
                $total_size_classes[] = 'total-size-file';
            } elseif (logsHelper::isLargeSize($total_size)) {
                $total_size_classes[] = 'total-size-large';
            }

            $total_size_hint = waRequest::get('action') == 'file' ? _w('This file’s size') : _w('All log files‘ total size');
        }

        $this->view->assign('view_modes', $this->getViewModes());
        $this->view->assign('item_actions', $this->getItemActions());
        $this->view->assign('total_size', !empty($total_size) ? logsHelper::formatSize($total_size) : null);
        $this->view->assign('total_size_class', isset($total_size_classes) ? implode(' ', $total_size_classes) : '');
        $this->view->assign('total_size_hint', ifset($total_size_hint));
        $this->view->assign('back_url', $back_url);
        $this->view->assign('is_item_list', !in_array(waRequest::get('action'), array('file', 'action')));
    }

    private function sortViewModes($a, $b)
    {
        if ($a['selected'] != $b['selected']) {
            return $b['selected'] ? 1 : -1;
        } else {
            return $a['sort'] < $b['sort'] ? -1 : 1;
        }
    }

    private function getBreadcrumbs($path)
    {
        $path_parts = explode('/', $path);

        if (!strlen($path_parts[0])) {
            return false;
        }

        $result = array();
        $result[] = array(
            'name' => 'wa-log',
            'url' => '',
        );

        $item_path = '';
        foreach ($path_parts as $part) {
            $item_path .= $item_path ? '/'.$part : $part;
            $result[] = array(
                'name' => $part,
                'url' => http_build_query(array(
                    'path' => $item_path,
                )),
            );
        }
        return $result;
    }

    private function getBackUrl()
    {
        if (in_array(waRequest::get('action'), array('file', 'action'))) {
            $back_url = waRequest::cookie('back_url', $this->backend_url);
        } else {
            $path_parts = explode('/', waRequest::get('path'));
            array_pop($path_parts);
            $back_url = $path_parts ? '?path='.implode('/', $path_parts) : $this->backend_url;
        }

        if ($back_url != $this->backend_url) {
            $current_url = wa()->getConfig()->getCurrentUrl();
            $back_url_contents = $current_url_contents = null;
            parse_str(str_replace($this->backend_url, '', $back_url), $back_url_contents);
            parse_str(str_replace($this->backend_url, '', $current_url), $current_url_contents);

            if ($back_url_contents && $current_url_contents) {
                if ($back_url_contents == $current_url_contents) {
                    //same keys & values regardless of order
                    $back_url = $this->backend_url;
                }
            }
        }

        return $back_url;
    }

    private function getViewModes()
    {
        $view_modes = array(
            array(
                'action' => '',
                'mode'   => '',
                'url'    => '',
                'title'  => _w('By directory'),
                'sort'   => 0,
                'icon'   => 'folders',
            ),
            array(
                'action' => 'files',
                'mode'   => 'updatetime',
                'url'    => '?action=files&mode=updatetime',
                'title'  => _w('By update time'),
                'sort'   => 1,
                'icon'   => 'bytime',
            ),
            array(
                'action' => 'files',
                'mode'   => 'size',
                'url'    => '?action=files&mode=size',
                'title'  => _w('By file size'),
                'sort'   => 2,
                'icon'   => 'bysize',
            ),
        );
        foreach ($view_modes as &$view_mode) {
            $view_mode['selected'] = $view_mode['action'] == waRequest::get('action') && $view_mode['mode'] == waRequest::get('mode');
        }
        usort($view_modes, array($this, 'sortViewModes'));

        return $view_modes;
    }

    private function getItemActions()
    {
        $result = array();
        $action = waRequest::get('action');
        $path = waRequest::get('path');

        if ($action == 'file') {
            $result['download'] = array(
                'title' => _w('Download'),
                'icon_class' => 'download',
                'url' => '?action=download&path='.$path,
            );

            if ($this->getUser()->getRights('logs', 'publish_files')) {
                $result['published'] = array(
                    'title' => _w('Public link'),
                    'icon_class' => 'globe',
                    'data' => array(
                        'path' => $path,
                    ),
                );
            }
        }

        if (strlen($path)) {
            if ($this->getUser()->getRights('logs', 'rename')) {
                $result['rename'] = array(
                    'title' => _w('Rename'),
                    'icon_class' => 'edit-bw',
                    'data' => array(
                        'path' => $path,
                    ),
                );
            }

            if ($this->getUser()->getRights('logs', 'delete_files')) {
                $result['delete'] = array(
                    'title' => _w('Delete'),
                    'icon_class' => 'cross-bw',
                    'data' => array(
                        'path' => $path,
                        'return-url' => $this->getBackUrl(),
                    ),
                );
            }
        }

        $common_actions = array();

        if ($this->getUser()->getRights('logs', 'view_phpinfo')) {
            $common_actions['phpinfo'] = array(
                'title' => _w('View PHP info'),
                'icon_class' => 'script-php',
            );
        }

        if ($this->getUser()->getRights('logs', 'change_settings')) {
            $common_actions['settings'] = array(
                'title' => _w('Settings'),
                'icon_class' => 'settings',
            );

            $common_actions['settings']['data'] = array(
                'hide-data' => json_encode(logsHelper::getHideSetting(null, true)),
            );
        }

        if ($common_actions) {
            $result[''] = '&nbsp;&nbsp;&nbsp;';
            $result += $common_actions;
        }

        return $result;
    }
}
