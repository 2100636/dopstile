<?php

class logsItemAction
{
    const LINES_PER_PAGE = 50;

    private $id;

    public function __construct($id)
    {
        if (!self::check($id)) {
            throw new logsInvalidDataException();
        }

        $this->id = $id;
    }

    public function get($params = array())
    {
        /**
         * lines are counted from 0
         * pages are counted from 1
         */

        $error = '';

        try {
            $log_model = new waLogModel();

            $line_count = $log_model->countByField(array(
                'action' => $this->id,
            ));

            if (!$line_count) {
                throw new Exception();
            }

            $page_count = (int) floor($line_count / self::LINES_PER_PAGE) + 1;

            if (isset($params['page'])) {
                $offset = ($params['page'] - 1) * self::LINES_PER_PAGE;
            } elseif (isset($params['first_line'])) {
                //id of first line of a page == number of previous lines
                if ($params['direction'] == 'previous') {
                    $offset = max($params['first_line'] - self::LINES_PER_PAGE, 0);
                } else {
                    $offset = $params['last_line'] + 1;
                }
            } else {
                $offset = ($page_count - 1) * self::LINES_PER_PAGE;
            }

            $entries = $log_model->query(
                "SELECT
                    app_id,
                    datetime,
                    params
                FROM wa_log
                WHERE action = s:action_id
                ORDER BY datetime ASC
                LIMIT i:offset, i:limit",
                array(
                    'action_id' => $this->id,
                    'offset' => $offset,
                    'limit' => self::LINES_PER_PAGE,
                )
            )->fetchAll();

            if (!$entries) {
                throw new Exception();
            }

            if (isset($params['direction'])) {
                if ($params['direction'] == 'previous') {
                    $first_line = max($params['first_line'] - count($entries), 0);
                    $last_line = $params['last_line'];
                } else {
                    $first_line = $params['first_line'];
                    $last_line += $params['last_line'] + count($entries);
                }
            } elseif (isset($params['page'])) {
                $first_line = ($params['page'] - 1) * self::LINES_PER_PAGE;
                $last_line = $first_line - 1 + count($entries);
            } else {
                $first_line = $line_count - count($entries);
                $last_line = $line_count - 1;
            }

            $app_ids = waUtils::getFieldValues($entries, 'app_id');

            $app_names = array();
            foreach ($app_ids as $app_id) {
                $app_info = wa()->getAppInfo($app_id);
                $app_names[$app_id] = $app_info['name'];
            }

            foreach ($entries as $i => &$entry) {
                $entry_params = json_decode($entry['params'], true);

                if (!is_array($entry_params)) {
                    unset($entries[$i]);
                    continue;
                }

                $values = array(
                    waDateTime::format('humandatetime', strtotime($entry['datetime'])),
                    _w($entry_params['source']),
                    sprintf("'%s'", $entry_params['login']),
                    $entry_params['ip'],
                    sprintf(_w('%s app'), $app_names[$entry['app_id']]),
                );
                $entry = implode(' | ', $values);
            }
            unset($entry);

            $contents = implode(PHP_EOL, $entries);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        //add new line characters
        if (strlen(ifset($contents))) {
            if (!empty($params['direction'])) {
                if ($params['direction'] == 'previous') {
                    $contents = $contents.PHP_EOL;
                } else {
                    $contents = PHP_EOL.$contents;
                }
            }
        }

        return array(
            'contents' => ifset($contents),
            'page_count' => (int) ifset($page_count),
            'error' => $error,
            'first_line' => (int) ifset($first_line),
            'last_line' => (int) ifset($last_line),
        );
    }

    public static function check($id)
    {
        return array_key_exists($id, self::getActions());
    }

    public static function getName($id)
    {
        static $actions;
        if (!$actions) {
            $actions = self::getActions();
        }
        return $actions[$id];
    }

    public static function getActions()
    {
        return array(
            'login_failed' => _ws('login failed'),
        );
    }
}
