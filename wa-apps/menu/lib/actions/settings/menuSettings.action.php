<?php


class menuSettingsAction extends waViewAction
{
    public function execute()
    {
        $options = $this->getConfig()->getOption();

        $this->view->assign('settings', $options);
    }

}