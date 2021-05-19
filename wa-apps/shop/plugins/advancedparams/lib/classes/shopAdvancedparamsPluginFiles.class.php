<?php

/**
 * Class shopAdvancedparamsPluginFiles
 * Класс отвечает за файлы экшена
 */
class shopAdvancedparamsPluginFiles {

    /**
     * Тип  экшена - страница, продукт или категория
     * @var string (page|product|category)
     */
    protected $action = '';
    /**
     * @var int $action_id идентификатор екшена
     */
    protected $action_id = 0;
    /**
     * Модель файлов доп. параметров
     * @var null|shopAdvancedparamsParamFileModel
     */
    protected $param_file_model = null;
    /**
     * Директория файлов экшена от корня сервера
     * @var string
     */
    protected $data_path = '';
    /**
     * Url директории файлов от корня домена
     * @var string
     */
    protected $data_url = '';
    /**
     * Префикс имени файла для подстановки
     * @var int
     */
    protected $file_name_counter = 1;

    /**
     * shopAdvancedparamsPluginFiles constructor.
     * @param string $action
     * @param int $action_id
     */
    public function __construct($action = '', $action_id = 0) {
        $this->action = $action;
        $this->action_id = $action_id;
        $this->param_file_model = new shopAdvancedparamsParamFileModel($this->action);
    }

    /**
     * Возвращает директорию хранения файлов экшена от корня сервера с учетом идентификатора
     * @return string
     */
    public function getDataPath() {
        if(empty($this->data_path)) {
            $this->data_path = wa()->getDataPath($this->getActionPath(), true, shopAdvancedparamsPlugin::APP);
        }
        return $this->data_path;
    }

    /**
     * Возвращает Веб директорию хранения файлов экшена от корня домена с учетом идентификатора экшена
     * @return string
     */
    public function getDataUrl() {
        if(empty($this->data_url)) {
            $this->data_url =  'wa-data/public/'.shopAdvancedparamsPlugin::APP.'/'.$this->getActionPath();
        }
        return $this->data_url;
    }

    /**
     * Возвращает относительную поддиректорию хранения файлов экшена с учетом идентификатора
     * @return string
     */
    protected function getActionPath() {
        if ($this->action =='product') {
            $str = str_pad($this->action_id, 4, '0', STR_PAD_LEFT);
            $sub_path = substr($str, -4, 2).'/'.$this->action_id.'/';
            return $this->getActionRootPath().$sub_path.shopAdvancedparamsPlugin::PLUGIN_ID."/";
        } else {
            return $this->getActionRootPath().shopAdvancedparamsPlugin::PLUGIN_ID."/";
        }

    }

    /**
     * Возвращает часть пути корневой директори файлов екшена
     * @return string
     */
    protected function getActionRootPath() {
        if($this->action =='category') {
            return  "categories/{$this->action_id}/";
        } elseif ($this->action =='product') {
            $str = str_pad($this->action_id, 4, '0', STR_PAD_LEFT);
            return 'products/'.substr($str, -2).'/';
        } elseif($this->action == 'page') {
            return "pages/{$this->action_id}/";
        } else {
            return "img/{$this->action_id}/";
        }
    }
    public static function getFileUrl($url) {
        return wa()->getRootUrl().ltrim($url,DIRECTORY_SEPARATOR);
    }
    /**
     * Проверяет существует ли файл на сервере
     * @param string $url веб адрес файла от корня домена
     * @return bool
     */
    public static function file_exists($action = '', $action_id = 0, $url = '') {
        $real_path = self::getRealpath($action, $action_id, basename($url));
        if(file_exists($real_path)) {
            return true;
        }
        return false;
    }

    /**
     * Возвращает реальный путь к файлу от корня сервера
     * @param string $action тип экшена
     * @param int $action_id идентификатор экшена
     * @param string $file_name имя файла без пути
     * @return null|string
     */
    public static function getRealpath($action = '', $action_id = 0, $file_name = '') {
        if(!empty($file_name)) {
            $filesClass = new self($action, $action_id);
            return $filesClass->getDataPath().$file_name;
        } else {
            return null;
        }
    }

    /**
     * Удаляет все файлы экшена по идентификатору установленному в конструкторе
     */
    public function deleteAll() {
        $path = $this->getDataPath();

        // Получаем все файлы экшена
        $files = $this->param_file_model->getByActionId($this->action_id);

        foreach ($files as $file) {

            $file_name = basename($file['value']);
            $real_path = $path.$file_name;
            $delete_flag = false;
            // Проверяем что файл есть и является файлом
            if(file_exists($real_path) && is_file($real_path)) {
                try {
                    if(waFiles::delete($real_path)) {
                       $delete_flag = true;
                    }
                } catch(Exception $ex) {
                    // Если что-то пошло не так пишем в лог
                    shopAdvancedparamsPlugin::log($ex->getMessage());
                }
            } else {
                // Если файл был удален ранее
                $delete_flag = true;
            }
            if($delete_flag) {
                // Удаляем запись в бд
                $this->param_file_model->deleteByActionIdName($this->action_id, $file['name']);
                // Удаляем директорию файлов плагина, если пуста например директорию pages/34/advancedparams
                $this->deleteEmptyDirectories(wa()->getDataPath($this->getActionRootPath(), true, shopAdvancedparamsPlugin::APP), $path);
            }
        }
        $this->deleteEmptyDirectories(wa()->getDataPath($this->getActionRootPath(), true, shopAdvancedparamsPlugin::APP), $path);
    }

    /**
     * Удаляет файл экшена по имени поля
     * @param string $field_name
     * @return bool
     */
    public function deleteFileByName($field_name = '') {
        $file = $this->param_file_model->getByActionIdName($this->action_id, $field_name);
        // Если такой файл был записан в бд
        if($file) {
            $delete_flag = false;
            $file_name = basename($file['value']);
            $path = $this->getDataPath();
            $real_path = $path.$file_name;
            // Проверяем что файл есть и является файлом
            if(file_exists($real_path) && is_file($real_path)) {
                try {
                    if(waFiles::delete($real_path)) {
                        $delete_flag = true;
                    }
                } catch(Exception $ex) {
                    // Если что-то пошло не так пишем в лог
                    shopAdvancedparamsPlugin::log($ex->getMessage());
                    return false;
                }
            } else {
                $delete_flag = true;
            }
            if($delete_flag) {
                // Удаляем запись в бд
                $this->param_file_model->deleteByActionIdName($this->action_id, $field_name);
                // Удаляем директорию файлов плагина, если пуста например директорию pages/34/advancedparams
                $this->deleteEmptyDirectories(wa()->getDataPath($this->getActionRootPath(), true, shopAdvancedparamsPlugin::APP), $path);
            }
        }
        return true;
    }

    /**
     * Сохраняет файл экшена
     * @param null|waRequestFile $file объект файла
     * @param string $type тип поля
     * @param string $field_name имя поля
     * @param array $errors внешний массив ошибок
     * @return bool|string
     */
    public function  saveFile($file  = null, $type = '', $field_name = '', &$errors, $size = array())
    {
        // Проверяем файл
        if(!is_object($file)) {
            $errors[] = 'Не передан объект файла!';
            return false;
        }
        $path = $this->getDataPath();
        // Заменяем все русские символы на латиницу и удаляем остальные
        $file_name = trim(preg_replace('~[^a-z0-9\.-_]~', '', waLocale::transliterate(basename($file->name))), ". \n\t\r");
        // Генерируем уникальное имя
        $file_name = $this->generateFileName(basename($file_name));
        // Если тип картинка, то сохраняем через объект изображения
        if($type == 'image') {
            try {
                $image = $file->waImage();
                $image = $this->generateThumb($image,$size);
                if ($image->save($path.$file_name)) {
                    $value = $this->getDataUrl().$file_name;
                  
                    $data = array(
                        'action_id'=> $this->action_id,
                        'name' => $field_name,
                        'value'=> $value
                    );
                    $this->param_file_model->save($data);
                    $data['file_link'] = self::getFileUrl($this->getDataUrl().$file_name);
                    return $data;
                }
            } catch(Exception $e) {
                $errors[] = 'Ошибка : '.$e->getMessage().'';
                return false;
            }
        }
        // Проверяем расширение файла
        if(!in_array(waFiles::extension($file_name), array('php', 'phtml', 'htaccess'))) {
            // Сохраняем файл и если удачно отдаем ссылку на него от корня домена
            if($file->moveTo($path, $file_name)) {
                $value = $this->getDataUrl().$file_name;
                $data = array(
                    'action_id'=> $this->action_id,
                    'name' =>$field_name,
                    'value'=> $value
                );
                $this->param_file_model->save($data);
                $data['file_link'] = self::getFileUrl($this->getDataUrl().$file_name);
                return $data;
            } else {
                $errors[] = 'Не удалось сохранить файл!';
                return false;
            }
        } else {
            $errors[] = 'Файл не должен быть исполняемым!';
            return false;
        }
    }
    public function generateThumb($image, $size = array())
    {
        $type  = 'none';
        $width = $height = null;

        if(isset($size['type'])) {
            $type = $size['type'];
            if(isset($size['width']) && intval($size['width'])>0) {
                $width = intval($size['width']);
            }
            if(isset($size['height']) && intval($size['height'])>0) {
                $height = intval($size['height']);
            }
        }
        switch ($type) {
            case 'none':
                return $image;
            case 'max':
                if($width < 1) {
                    throw new waException('Укажите правильный размер изображения!');
                }
                $image->resize($width, $height);
                break;
            case 'width':
                if($width < 1) {
                    throw new waException('Укажите корректную ширину изображения!');
                }
                $image->resize($width, $height);
                break;
            case 'height':
                if($height < 1) {
                    throw new waException('Укажите корректную высоту изображения!');
                }
                $image->resize($width, $height);
                break;
            case 'crop':
                if($width < 1) {
                    throw new waException('Укажите правильный размер изображения!');
                }
                $height = $width;
                $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
                break;
            case 'rectangle':
                if($width < 1 || $height < 1) {
                    throw new waException('Укажите правильные размеры изображения!');
                }
                $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
                break;
            default:
                throw new waException("Неправильный тип изменения размеров изображения");
                break;
        }
        return $image;
    }
    /**
     * Возвращает уникальное имя файла
     * @param string $file_name
     * @return string
     */
    protected function generateFileName($file_name = '') {
        $path = $this->getDataPath();
        if(file_exists($path.$file_name)) {
            if(file_exists($path.$this->file_name_counter.'_'.$file_name)) {
                $this->file_name_counter++;
                return $this->generateFileName($file_name);
            } else {
                $file_name = $this->file_name_counter.'_'.$file_name;
                $this->file_name_counter = 1;
                return $file_name;
            }
        } else {
            return $file_name;
        }
    }
    
    protected function deleteEmptyDirectories($root_path, $data_path) {
        $root_path_array = explode('/',rtrim($root_path, '/'));
        $count_root_path = count($root_path_array);

        $data_path_array = explode('/',rtrim($data_path, '/'));
        $count_data_path = count($data_path_array);
        // Удаляем директории файлов экшена если они пусты до корневой директории
        // например директорию страницы pages/34/advancedparams проверяем до корневой pages/34 и удаляем все пустые
        while($count_data_path>=$count_root_path) {
            $path = implode('/',$data_path_array);
            if(!waFiles::listdir($path)) {
                waFiles::delete($path);
            } else {
                break;
            }
            array_pop($data_path_array);
            $count_data_path = count($data_path_array);
        }
    }
}