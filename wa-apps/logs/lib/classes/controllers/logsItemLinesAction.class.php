<?php

class logsItemLinesAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign($this->params);
        $this->setTemplate(wa()->getAppPath('templates/actions/ItemLines.html'));
    }
}
