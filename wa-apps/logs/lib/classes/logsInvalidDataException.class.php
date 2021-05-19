<?php

class logsInvalidDataException extends Exception
{
    public function __construct($message = null, $code = null)
    {
        parent::__construct(_w('Invalid data. Reload this page and try again.'), null);
    }
}
