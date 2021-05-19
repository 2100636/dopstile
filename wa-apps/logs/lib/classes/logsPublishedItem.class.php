<?php

class logsPublishedItem
{
    private $path;
    private $hash;

    public function __construct($params)
    {
        $this->path = $params['path'];
        $this->hash = $params['hash'];
    }

    public function get($field = null)
    {
        static $published_item;
        if (is_null($published_item)) {
            $published_item_fields = array(
                'path' => $this->path,
                'hash' => $this->hash,
            );

            $published_model = new logsPublishedModel();
            $published_item = $published_model->getByField($published_item_fields);

            if (!$published_item) {
                $published_item = false;
            }
        }

        return $field && $published_item && isset($published_item[$field]) ? $published_item[$field] : $published_item;
    }

    public function checkAccess()
    {
        $storage_key = $this->getStorageKey();
        $published_item_password = $this->get('password');
        return !$published_item_password || wa()->getStorage()->get($storage_key) === $published_item_password;
    }

    public function getStorageKey()
    {
        return 'logs_published_password_'.md5($this->path);
    }
}
