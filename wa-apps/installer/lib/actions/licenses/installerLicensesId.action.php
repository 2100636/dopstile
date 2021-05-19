<?php

class installerLicensesIdAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setLayout(new installerBackendStoreLayout());
    }

    public function execute()
    {
        $id = $this->getRequest()->param('id', 0, waRequest::TYPE_INT);

        $list = new installerLicensesList([ 'contact_id' => $this->getUserId() ]);
        list($license, $error) = $list->getOne($id, installerLicensesList::FIELD_ALL);

        $this->view->assign([
            'license' => $license,
            'error' => $error,
            'is_auto_install' => $id > 0 && (bool)$this->getRequest()->get('install')
        ]);
    }
}
