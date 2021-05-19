<?php


class menuItemControlsController extends waController
{

    public function execute()
    {
        $mi = new menuItemModel();
        $item = $mi->getItem(waRequest::get('id'), waRequest::get('type'));

        echo $item->renderParamControls();
    }

}