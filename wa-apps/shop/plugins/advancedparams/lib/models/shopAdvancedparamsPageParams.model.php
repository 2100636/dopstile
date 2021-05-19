<?php

/**
 * Class shopAdvancedparamsPageParamsModel
 */
class shopAdvancedparamsPageParamsModel extends shopPageParamsModel
{
    /**
     * Колонка идентификатора экшена
     * @var string
     */
    protected $action_id_field = 'page_id';

    /**
     * Возвращает колонку идентификатора экшена
     * @return string
     */
    public function getActionIdField() {
        return $this->action_id_field;
    }

    /**
     * Возвращает доп. параметры по id экшена
     * @param $id
     * @return array
     */
    public function get($id)
    {
        $params = array();
        foreach ($this->getByField('page_id', $id, true) as $p) {
            $params[$p['name']] = $p['value'];
        }
        return $params;
    }

    /**
     * Сохраняет доп. параметры экшена по id
     * @param $id
     * @param array $params
     * @return bool
     */
    public function set($id, $params = array())
    {
        if ($id) {
            // remove if params is null
            if (is_null($params)) {
                return $this->deleteByField(array(
                    'page_id' => $id
                ));
            }

            // candidate to delete
            $delete_params = $this->get($id);

            // accumulate params to add (new params) and update old params
            $add_params = array();
            foreach ($params as $name => $value) {
                if (isset($delete_params[$name])) {
                    // update old param
                    $this->updateByField(array(
                        'page_id' => $id,
                        'name' => $name
                    ), array(
                            'value' => $value
                        )
                    );
                    // remove from candidate to delete
                    unset($delete_params[$name]);
                } else {
                    // param to add
                    $add_params[] = array(
                        'page_id' => $id,
                        'name' => $name,
                        'value' => $value
                    );
                }
            }

            // delete
            foreach ($delete_params as $name => $value) {
                $this->deleteByField(array(
                    'page_id' => $id,
                    'name' => $name
                ));
            }

            // add new params
            if ($add_params) {
                $this->multipleInsert($add_params);
            }

            return true;
        }
        return false;
    }
}