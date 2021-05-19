<?php

class installerLicensesAutocompleteController extends waController
{
    public function execute()
    {
        $list = new installerLicensesList([
            'count_per_page' => 10,
            'contact_id' => $this->getUserId()
        ]);

        $product_name = $this->getRequest()->request('term', '', waRequest::TYPE_STRING_TRIM);
        if (strlen($product_name) > 0) {
            $list->addFilter(installerLicensesList::FILTER_NAME, '*=', $product_name);
        }

        list($licenses, $_) = $list->getList();

        $product_names = waUtils::getFieldValues($licenses, 'name');

        $term_safe = htmlspecialchars($product_name, ENT_QUOTES, 'utf-8');

        $data = [];
        foreach ($product_names as $name) {
            if (strlen($product_name) > 0) {
                $label = $this->prepare($name, $term_safe);
            } else {
                $label = htmlspecialchars($name, ENT_QUOTES, 'utf-8');
            }
            $data[] = [
                'value' => $name,
                'label' => $label,
            ];
        }

        $this->getResponse()->addHeader('Content-Type', 'application/json');
        die(json_encode($data));
    }

    // Helper for contactsAutocomplete()
    protected function prepare($str, $term_safe, $escape = true)
    {
        $pattern = '~('.preg_quote($term_safe, '~').')~ui';
        $template = '<span class="bold highlighted">\1</span>';
        if ($escape) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }
        return preg_replace($pattern, $template, $str);
    }
}
