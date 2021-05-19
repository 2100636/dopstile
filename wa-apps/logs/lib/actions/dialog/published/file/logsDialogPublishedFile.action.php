<?php

class logsDialogPublishedFileAction extends waViewAction
{
    public function execute()
    {
        $path = waRequest::get('path');

        try {
            if (!$this->getRights('publish_files')) {
                throw new Exception('You have no permissions to publish or unpublish log files.');
            }

            $routing_exists = (bool) wa()->getRouting()->getByApp('logs');

            if (!$routing_exists) {
                throw new Exception(
                    sprintf(
                        _w('No routing rule to create public links to files. Add a <em>hidden</em> rule for Logs app in Site app’s “<a href="%s">Structure</a>” section.'),
                        wa()->getAppUrl('site').'#/routing/'
                    )
                );
            }

            $published_model = new logsPublishedModel();
            $published_file = $published_model->getByField(array(
                'path' => $path,
            ));

            $url = $published_file ? wa()->getRouteUrl(
                'logs/frontend/fileView',
                array(
                    'hash' => $published_file['hash'],
                    'path' => $path,
                ),
                true
            ) : '';

                $controls = array(
                    'status' => array(
                        'value' => array(
                            'control_type' => waHtmlControl::CHECKBOX,
                            'value' => 1,
                            'checked' => !empty($published_file),
                            'class' => 'published-status-ibutton',
                            'data' => array(
                                'path' => $path,
                            ),
                        ),
                    ),
                    'url' => array(
                        'value' => array(
                            'control_type' => waHtmlControl::INPUT,
                            'value' => $url,
                            'readonly' => true,
                            'class' => 'published-url auto-select',
                            'style' => 'width: 450px;',
                        ),
                    ),
                    'password' => array(
                        'path' => array(
                            'control_type' => waHtmlControl::HIDDEN,
                            'value' => $path,
                        ),
                        'value' => array(
                            'control_type' => waHtmlControl::INPUT,
                            'value' => $published_file['password'],
                            'readonly' => true,
                            'class' => 'auto-select published-password-value',
                        ),
                    ),
                );

                foreach ($controls as &$group_controls) {
                    foreach ($group_controls as $control_name => &$control) {
                        $control = waHtmlControl::getControl($control['control_type'], $control_name, $control);
                    }
                }
                unset($group_controls, $control);

                $this->view->assign('controls', $controls);
                $this->view->assign('published_file', $published_file);
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }

        $this->view->assign('file', logsHelper::getPathParts($path));
    }
}
