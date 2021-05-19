<?php

abstract class logsBackendItemAction extends logsViewAction
{
    protected $action;
    protected $id;
    protected $value;

    public function __construct()
    {
        parent::__construct();
        $this->value = waRequest::get($this->id);
    }

    public function execute()
    {
        try {
            if (!strlen($this->value)) {
                logsHelper::redirect();
            }

            if (!$this->check()) {
                logsHelper::redirect();
            }

            $page = waRequest::get('page', null, waRequest::TYPE_INT);
            $item_url = sprintf('?action=%s&%s=%s', $this->action, $this->id, $this->value);

            if (!empty($page) && $page < 1) {
                $this->redirect($item_url);
            }

            $item = $this->getItem(array(
                'page' => $page,
            ));

            if (isset($item['error']) && strlen($item['error'])) {
                throw new Exception($item['error']);
            }

            if ($page > 1 && $page >= $item['page_count']) {
                $this->redirect($item_url);
            } else {
                $this->view->assign('item', $item);
            }
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }

        $this->view->assign('item_lines_url', '?action=itemLines');
        $this->setTemplate(wa()->getAppPath('templates/actions/ItemView.html'));
    }

    abstract protected function check();
    abstract protected function getItem($params);
}
