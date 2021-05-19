<?php

class logsBackendLayout extends waLayout
{
    public function execute()
    {
        $this->executeAction('navigation', new logsBackendNavigationAction());
        $loc = array(
            'cancel',
            'Delete',
            'OK',
            'Settings',
            'Save',
            'Close',
            'Rename',
        );
        $loc = array_flip($loc);
        foreach ($loc as $key => &$string) {
            $string = _w($key);
        }
        unset($string);

        $this->view->assign('loc', $loc);
        $this->view->assign('ajax', waRequest::isXMLHttpRequest());
    }
}
