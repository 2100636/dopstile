<?php
/* *
 * Коротко про обновление:
 * Добавлена возможность массово редактировать доп параметры с списке продуктов категории,
 * алгоритм получения  доп параметров продуктов неоптимален (тупой) и будет виснуть при большом количестве выбранных продуктов,
 * если раньше браузер не повиснет от количества html. Алгоритм получения будет заменен в следующем обновлении
 *
 * Был обнаружен мини баг с переносом фреймворка в другую папку или подпапку, т.к адреса файлов в доп паараметрах хранились от корня домена,
 * после переноса в подпапку адреса становились некорректными, такие адреса были удобны для вывода без дополнительных манипуляций
 * (запросами getRootUrl {$wa_url}), но придется делать!
 * */

 /* *
 * Убираем из ссылок всех файлов директории поселений,
 * для поддержки переноса движка в поддиректории делаем адрес относительным корня фреймворка
 * */
$files_model = new shopAdvancedparamsParamFileModel();
$params_model['product'] = new shopAdvancedparamsParamsModel('product');
$params_model['category'] = new shopAdvancedparamsParamsModel('category');
$params_model['page'] = new shopAdvancedparamsParamsModel('page');
try {
    $files = $files_model->getAll();
    if(is_array($files) && !empty($files)) {
        // Обновляем ссылки на все файлы на относительные
        foreach ($files as $file) {
            $data = array();
            $data['value'] = preg_replace('@.*wa-data/@i', 'wa-data/', $file['value']);
            // Обновляем в url таблице файлов
            $files_model->updateByField(array(
                'action' => $file['action'],
                'action_id' => $file['action_id'],
                'name' => $file['name']
            ), $data);
            // Обновляем url в таблице доп параметров самого экшена
            if(isset($params_model[$file['action']]) && !empty($file['action_id'])) {
                $action_params_model = $params_model[$file['action']];
                $action_params_model->getModel()->updateByField(array(
                    $action_params_model->getActionIdField() => $file['action_id'],
                    'name' => $file['name']
                ), $data);
            }
        }
    }
} catch (waDbException $e) {
    waLog::dump($e->getMessage(),'shop.advancedparams.update.log');
}
