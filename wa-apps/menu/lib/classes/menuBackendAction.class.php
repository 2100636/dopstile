<?php


class menuBackendAction extends waViewAction
{

    public function preExecute()
    {
        parent::preExecute();

        if(!waRequest::isXMLHttpRequest()) {
            $layout = new menuBackendLayout();
            $this->setLayout($layout);
        }
    }

}