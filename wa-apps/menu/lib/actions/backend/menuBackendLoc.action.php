<?php

/**
 * A list of localized strings to use in JS.
 */
class menuBackendLocAction extends waViewAction
{

    public function execute()
    {
        $strings = array();

        // Application locale strings
        foreach(array(
                    'Error',
                    'Close',
                    'Saved',
                    'Save',
                    'or',
                    'cancel',
                    'delete',
                    'An error occurred.',
                    'This will permanently delete menu and all its items without the ability to recover. Are you sure?',
                    'Plugins',
                ) as $s) {
            $strings[$s] = _w($s);
        }

        // plural forms hack
        foreach ($this->getPlurals() as $pair) {
            $strings[$pair[0]] = array(
                _w($pair[0]),
                str_replace(2, '%d', _w($pair[0], $pair[1], 2)),
                str_replace(5, '%d', _w($pair[0], $pair[1], 5))
            );
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings

        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }

    public function getPlurals()
    {
        return array(
            array/*_w*/('test', 'tests'),
        );
    }

    public function preExecute()
    {
        // do not save this page as last visited
    }
}
