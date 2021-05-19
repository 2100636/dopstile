<?php

class logsFrontendFileViewAction extends waViewAction
{
    public function execute()
    {
        $path = $this->params['path'];
        $page = waRequest::get('page', null, waRequest::TYPE_INT);

        $file_item = new logsItemFile($path);
        $file = $file_item->get(array(
            'page' => $page,
        ));

        if ($page !== null && ($page < 1 || $page >= $file['page_count'])) {
            $base_url = $this->view->getHelper()->currentUrl(false, true);
            $this->redirect($base_url);
        } else {
            $this->view->assign('item', $file);
            $this->setTemplate(wa()->getAppPath('templates/actions/ItemView.html'));
            $this->setLayout(new logsFrontendLayout());
        }
    }
}
