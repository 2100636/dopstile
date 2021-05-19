<?php

class logsDialogPhpinfoAction extends waViewAction
{
    public function execute()
    {
        try {
            if (!function_exists('phpinfo')) {
                throw new Exception(_w('Function <tt>phpinfo()</tt> is not available on your server.'));
            }

            if (!$this->getRights('view_phpinfo')) {
                throw new Exception(_w('You have no permissions to view PHP configuration.'));
            }

            $can_publish_phpinfo = $this->getRights('publish_phpinfo');

            if ($can_publish_phpinfo) {
                $this->view->assign('can_publish_phpinfo', $can_publish_phpinfo);

                if ((bool) wa()->getRouting()->getByApp('logs')) {
                    $model = new logsPublishedModel();
                    $path = '//phpinfo//';

                    $published = $model->getByField(array(
                        'path' => $path,
                    ));

                    $url = $published ? wa()->getRouteUrl('logs/frontend/phpinfo', array(
                        'hash' => $published['hash'],
                    ), true) : '';

                    $controls = array(
                        'status' => array(
                            'value' => array(
                                'control_type' => waHtmlControl::CHECKBOX,
                                'value' => 1,
                                'checked' => !empty($published),
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
                                'value' => ifset($published, 'password', ''),
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
                    $this->view->assign('published', $published);
                    $this->view->assign('url', $url);
                } else {
                    $this->view->assign(
                        'controls_error',
                        sprintf(
                            _w('No routing rule to create a public link to the PHP configuration page. Add a <em>hidden</em> rule for Logs app in Site app’s “<a href="%s">Structure</a>” section.'),
                            wa()->getAppUrl('site').'#/routing/'
                        )
                    );
                }
            }
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }
    }
}
