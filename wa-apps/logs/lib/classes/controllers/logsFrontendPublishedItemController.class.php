<?php

abstract class logsFrontendPublishedItemController extends waViewController
{
    protected $hash;
    protected $path;
    protected $action;
    protected $not_published_warning;
    protected $json_response = array();

    public function execute()
    {
        try {
            $this->check();

            $published_item = new logsPublishedItem(array(
                'path' => $this->path,
                'hash' => $this->hash,
            ));

            $item_data = $published_item->get();

            if (!$item_data) {
                throw new Exception($this->not_published_warning);
            }

            if ($item_data['password']) {
                if (waRequest::isXMLHttpRequest()) {
                    $post_password = waRequest::post('password', null, waRequest::TYPE_STRING_TRIM);

                    if (is_null($post_password)) {
                        if ($published_item->checkAccess()) {
                            $this->getData();
                        } else {
                            $this->json_response['data']['return_url'] = '';
                            throw new waException(_w('File access password has changed. Reload this page to enter a correct password.'));
                        }
                    } else {
                        $storage_key = $published_item->getStorageKey();

                        if ($post_password === $item_data['password']) {
                            wa()->getStorage()->set($storage_key, $post_password);

                            $this->json_response = array(
                                'status' => 'ok',
                            );
                        } else {
                            if (strlen($post_password)) {
                                $message = _w('Incorrect password.');
                                wa()->getStorage()->del($storage_key);
                            } else {
                                $message = _w('Empty password.');
                            }

                            throw new waException($message);
                        }
                    }
                } else {
                    if ($published_item->checkAccess()) {
                        $this->getData();
                    } else {
                        $this->executeAction(new logsFrontendRequestPasswordAction(array('action' => $this->action)));
                    }
                }
            } else {
                $this->getData();
            }
        } catch (waException $e) {
            //show response to user

            if (waRequest::isXMLHttpRequest()) {
                $this->json_response += array(
                    'status' => 'fail',
                    'errors' => array($e->getMessage()),
                );
            } else {
                throw new waException($e->getMessage(), 403);
            }
        } catch (Exception $e) {
            //log response to file

            if (waSystemConfig::isDebug()) {
                $message = $e->getMessage();
                if (strlen($message)) {
                    logsHelper::log($message);
                }
            }

            if (waRequest::isXMLHttpRequest()) {
                $this->json_response = array(
                    'status' => 'fail',
                );
            } else {
                throw new waException('', 404);
            }
        }

        if (waRequest::isXMLHttpRequest()) {
            if (empty($this->json_response['status']) && !empty($this->json_response['data'])) {
                $this->json_response['status'] = 'ok';
            }

            $this->jsonResponse();
        }
    }

    protected function check()
    {
        if (!strlen($this->hash)) {
            throw new Exception();
        }
    }

    protected function jsonResponse()
    {
        echo json_encode($this->json_response);
    }

    abstract protected function getData();
}
