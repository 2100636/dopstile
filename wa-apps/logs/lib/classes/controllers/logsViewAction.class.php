<?php

abstract class logsViewAction extends waViewAction
{
    public function __construct()
    {
        parent::__construct();

        if ($this instanceof logsBackendNavigationAction) {
            if (!in_array(waRequest::get('action'), array('file', 'action'))) {
                wa()->getResponse()->setCookie('back_url', wa()->getConfig()->getCurrentUrl());
            }
        } else {
            $this->setLayout(new logsBackendLayout());
        }
    }
}
