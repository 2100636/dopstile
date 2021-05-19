<?php

class logsFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->executeAction('navigation', new logsFrontendNavigationAction());
    }
}
