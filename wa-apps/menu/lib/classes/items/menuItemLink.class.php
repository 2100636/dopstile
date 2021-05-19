<?php


class menuItemLink extends menuItem
{
    /**
     * Список доступных параметров для данного типа ссылки.
     * Параметры из этого списка будут автоматически сохраняться через настройки пункта меню.
     *
     * Иначе в классе нужно переписать методы
     * saveParams() и renderParamControls()
     *
     * @return array
     */
    protected function getAvailableParams() {
        return array(
            'url' => array(
                'value'        => '#',
                'title'        => 'URL',
                'control_type' => waHtmlControl::INPUT,
            ),
            'target' => array(
                'value'        => '_self',
                'title'        => _w("Target"),
                'control_type' => waHtmlControl::SELECT,
                'options' => array(
                    '_blank' => _w('New tab'),
                    '_self' => _w('Current tab'),
                    '_parent' => _w('Parent window'),
                    '_top' => _w('Top frame'),
                ),
            ),
        );
    }

    /**
     * @return string
     */
    public function getBackendIcon()
    {
        return 'link';
    }

    public function toArray()
    {
        $data = parent::toArray();
        return $data;
    }
}