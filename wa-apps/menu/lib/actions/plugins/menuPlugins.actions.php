<?php

class menuPluginsActions extends waPluginsActions
{
    public function preExecute()
    {
        if (!$this->getUser()->isAdmin('menu')) {
            throw new waRightsException(_ws('Access denied'));
        }
    }
}