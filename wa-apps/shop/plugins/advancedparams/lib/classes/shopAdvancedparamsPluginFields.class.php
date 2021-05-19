<?php

/**
 * Class shopAdvancedparamsPluginFields
 * Класс отвечает за генерацию HTML кода полей екшена из Доп.параметров
 */
class shopAdvancedparamsPluginFields {

    /**
     * Тип  экшена - страница, продукт или категория
     * @var string (page|product|category)
     */
    protected $action = '';
    /**
     *  Массив полей екшена из таблицы плагина, с типом и возможными значениями
     * @var array
     */
    protected $action_fields = array(); 
    /**
     * Массив установленных параметров екшена из таблицы параметров страниц, продуктов или категорий
     * @var array
     */ 
    protected $action_params = array();
    /**
     * Идентификатор екшена, ID страницы, продукта или категории
     * @var int
     */
    protected $action_id = 0;
    /**
     * Массив типов полей из конфига field_type=> field_name
     * @var array
     */
    protected $field_types = array();
    /**
     * Начальное название полей в бекенде namespace[field_name]
     * @var string
     */
    protected $field_namespace = '';
    /**
     * Css класс присваиваемый всем полям в бекенде
     * @var string
     */
    protected $field_class = '';
    /**
     * БД Модель полей экшена
     * @var null|shopAdvancedparamsFieldModel
     */
    protected $fields_model = null;
    /**
     *  Модель реальных значений полей
     * @var null|shopAdvancedparamsParamValueModel
     */
    protected $param_value_model = null;
    /**
     *  Модель значений файловых параметров
     * @var null|shopAdvancedparamsParamFileModel
     */
    protected $param_file_model = null;

    protected $real_values = array();

    /**
     * shopAdvancedparamsPluginFields constructor.
     * @param $action string тип екшена страница, продукт или категория
     */
    public function __construct($action = '')
    {
        // Устанавливаем типы полей
        $field_types = shopAdvancedparamsPlugin::getConfigParam(shopAdvancedparamsPlugin::CONFIG_FIELD_TYPES_KEY);
        if(is_array($field_types)) {
            $this->field_types = $field_types;
        }

        $this->action = $action; // тип экшена - страница, продукт или категория

        $this->field_namespace = shopAdvancedparamsPlugin::PARAM_FIELD_NAME;// префикс названия полей
        $this->field_class = shopAdvancedparamsPlugin::PARAM_FIELD_CLASS; // Css класс полей
        // Модели
        $this->fields_model = new shopAdvancedparamsFieldModel(); // Модель полей
        $this->param_value_model = new shopAdvancedparamsParamValueModel($this->action); // Модель реальных значений полей
        $this->param_file_model = new shopAdvancedparamsParamFileModel($this->action); // Модель значений файловых параметров
        $this->action_fields = $this->fields_model->getActionFields($this->action);
    }

    /**
     * Возвращает массив полей екшена в виде HTML строк (Основной метод получения данных класса)
     * @access public
     * @param integer $action_id идентификатор екшена
     * @param array $action_params Массив доп. параметров екшена key=>value
     * @return array
     */
    public function getFields($action_id, $action_params) {
        $this->action_id = $action_id;
        $this->real_values = $this->param_value_model->get($action_id);
        $return = array();
        foreach ($this->action_fields as $k=>$v)  {
            $value = null;
            if(isset($action_params[$v['name']])) {
                $value = $action_params[$v['name']];
            }
            $return[$k] = $this->getField($v, $value);
           unset($action_params[$v['name']]);
        }
        if(!empty($action_params)) {
            foreach($action_params as $name => $value) {
                if(!shopAdvancedparamsPlugin::isBannedField($this->action, $name)) {
                    $return[$name] = $this->getCustomParam($name, $value);
                }

            }
        }
        return $return;
    }

    /**
     * Генерирует HTML код поля
     * @access protected
     * @param array $field данные поля
     * @param null| string $value значение поля из доп. параметров
     * @return string (HTML)
     */
    protected function getField($field, $value = null) {
        // Формируем название метода для генерации поля
        if(array_key_exists($field['type'], $this->field_types)) {
            $method = 'get'.ucfirst($field['type']).'Field';
        } else {
            $field['type']= 'input';
            $method = 'getInputField';
        }
        // Проверяем существование метода
        if(method_exists($this, $method)) {
            $field_html = $this->$method($field, $value);
            //$action_variables = shopAdvancedparamsPlugin::getConfigParam('action_variable');
            //$variable = '<span class="hint">{'.$action_variables[$field['action']].'.'.htmlspecialchars($field['name']).'}</span>';
            $html = '<div class="field advancedparams_plugin-field-'.htmlspecialchars($field['name']).'" id="advancedparams_plugin-'.$this->action_id.'-field-'.htmlspecialchars($field['name']).'" data-type="'. $field['type'].'">           
                    <div class="name">'.$this->getFieldName($field,$value).'</div>
                    <div class="value">'.$field_html.'<br><span class="hint">'.nl2br(htmlspecialchars($field['description'])).'</span>
                    </div>
                    </div>';
            return $html;
        } else {
            return '';
        }
    }
    protected function getCustomParam($name, $value) {
        $id = md5(rand('11342412', 99999999999).time());
        $html = '<div class="field advancedparams_plugin_field_custom" id="advancedparams_plugin-field-'.$id.'" data-type="custom">           
                    <div class="name">
                    <input type="text" value="'.htmlspecialchars($name).'" class="'.$this->getClass().' advancedparams_plugin_custom_name" name="advancedparams_plugin['.$id.'][name]">
                    </div>
                    <div class="value">
                     <input type="text"  value="'.htmlspecialchars($value).'"  class="'.$this->getClass().' advancedparams_plugin_custom_value" name="advancedparams_plugin['.$id.'][value]"></span>
                     <a href="#" class="advancedparams_plugin_custom-delete inline"><i class="icon16 delete"></i></a>
                    </div>
                    </div>';
        return $html;
    }
    /**
     * Генерирует INPUT
     * @access protected
     * @param array $field данные поля
     * @param null|string $value
     * @return string (HTML)
     */
    protected function getInputField($field, $value) {
        $field = '<input type="text" class="'.$this->getClass().'" data-type="'.$this->getType($field['type']).'" name="'.$this->getName($field['name']).'" value="'.$this->getValue($field, $value).'"  '.(is_null($value)?'readonly':'').' /> ';
        return $field;
    }

    /**
     * Генерирует Textarea
     * @access protected
     * @param array $field данные поля
     * @param null|string $value
     * @return string (HTML)
     */
    protected function getTextareaField($field, $value)
    {
        $html = '<textarea class="'.$this->getClass().'" data-type="'.$this->getType($field['type']).'" name="'.$this->getName($field['name']).'" '.(is_null($value)?'readonly':'').' >'.$this->getValue($field, $value).'</textarea>';
        return $html;
    }

    /**
     * Генерирует Select поле
     * @access protected
     * @param array $field данные поля
     * @param null|string $value
     * @return string (HTML)
     */
    protected function getSelectField($field, $value)
    {
        if(isset($field['values']) && is_array($field['values'])&& !empty($field['values'])) {
            $html = '<select class="'.$this->getClass().' advancedparams_plugin-param-select" data-type="'.$this->getType($field['type']).'"  name="'.htmlspecialchars($this->field_namespace).'_select['.htmlspecialchars($field['name']).']" '.(is_null($value)?'disabled':'').' >';
            if(is_null($value)) {
                $value = $field['default_value'];
            }
            foreach($field['values'] as $k=>$v) {
                $html .= '<option value="'.htmlspecialchars($v['value']).'" '.($v['value']==$this->getValue($field, $value)?'selected="selected"':'').'>'.$v['value'].'</option>';
            }
            $html .= '</select><input type="hidden" class="'.$this->getClass().' advancedparams_plugin-param-hidden" name="'.$this->getName($field['name']).'" value="'.$this->getValue($field, $value).'" >';
        } else {
            $html = 'Добавьте значения в <a href="/'.wa()->getConfig()->getBackendUrl().'/'.shopAdvancedparamsPlugin::APP.'/?action=plugins#/advancedparams/">настройках плагина</a>!';
        }
        return $html;
    }

    /**
     * Генерирует поле Картинки
     * @access protected
     * @see self::getFileField
     */
    protected function getImageField($field, $value) {
        return $this->getFileField($field, $value);
    }

    /**
     * Генерирует поле файла
     * @access protected
     * @param array $field данные поля
     * @return string (HTML)
     */
    protected function getFileField($field, $param_value) {
        // значение поля берет из таблицы файлов, таким образом можно выключать доп.параметр не удаляя файл
        $value =  htmlspecialchars($this->param_file_model->getFileLink($this->action_id, $field['name']));
        // Если файла не существует удаляем значение
        if(!empty($value) && !shopAdvancedparamsPluginFiles::file_exists($this->action, $this->action_id, $value)) {
            $value = null;
        }
        if(empty($value) && !empty($param_value) && shopAdvancedparamsPluginFiles::file_exists($this->action, $this->action_id, $param_value)) {
            $this->param_file_model->save(array(
                'action' => $this->action,
                'action_id' => $this->action_id,
                'name' => $field['name'],
                'value' => $param_value
                ));
            $value = $param_value;
        }
        if(!empty($value)) {
            $name = basename($value);
            // Если тип поля картинка, то показываем превью
            $file_url = shopAdvancedparamsPluginFiles::getFileUrl($value);
            if($field['type']=='image') {
                $file_preview = '<a href="'.$file_url.'" target="_blank">'.$name.'<br><br>
                <img src="'.$file_url.'" class="advancedparams_plugin-param-image" />
                </a>';
            } else {
                $file_preview = '<a href="'.$file_url.'" target="_blank">
                    <i class="icon16 download"></i>'.$name.'
                </a><br>'; 
            }
            $html = '
            <input  class="'.$this->getClass().'" type="hidden" data-type="'.$this->getType($field['type']).'"  name="'.$this->getName($field['name']).'" value="'.$value.'"  >
                <div class="advancedparams_plugin-param-file-preview">'.$file_preview.'</div>
                <div class="advancedparams_plugin-param-file-action">
                <a href="#" class="advancedparams_plugin-param-file-delete"><i class="icon16 delete"></i>Удалить</a>
                </div>
                   ';
        } else {
            $html = '<input  class="'.$this->getClass().'" type="hidden" data-type="'.$this->getType($field['type']).'"  name="'.$this->getName($field['name']).'" value="'.$value.'"  disabled>
                <div class="advancedparams_plugin-param-file-preview"></div>
                <div class="advancedparams_plugin-param-file-action">
                Вставьте ссылку файла  <input type="text" name="advancedparams_url" class="advancedparams_plugin-param-file-input" value="">
                <br>или <a href="#" class="advancedparams_plugin-param-file-upload"><i class="icon16 add"></i>Загрузить</a><br>
                <span class="errors"></span>
                </div>';
        }
        $image_data = '';
        if($field['type']=='image') {
            if(!empty($field['size_type'])) {
                $image_data .= ' data-size_type="'.$field['size_type'].'" ';
            } else {
                $image_data .= ' data-size_type="none" ';
            }
            if(!empty($field['width'])) {
                $image_data .= ' data-width="'.$field['width'].'" ';
            }
            if(!empty($field['height'])) {
                $image_data .= ' data-height="'.$field['height'].'" ';
            }
        }
        $html = '
            <div class="advancedparams_plugin-param-file" data-type="'.$this->getType($field['type']).'" data-name="'.htmlspecialchars($field['name']).'" '.$image_data.'>
            '.$html.'
            </div>';
        return $html;
    }
    /**
     * Генерирует RadioGroup поле
     * @access protected
     * @param array $field данные поля
     * @param null|string $value
     * @return string (HTML)
     */
    protected function getRadioField($field, $value)
    {
        $html = '';
        $param_value = $value;
        if(is_null($value)) {
            $value = $field['default_value'];
        }
        if(isset($field['values']) && is_array($field['values']) && !empty($field['values'])) {
            foreach($field['values'] as $k=>$v) {
                $html .= '<label><input type="radio" class="'.$this->getClass().'" data-type="'.$this->getType($field['type']).'" name="'.$this->getName($field['name']).'" value="'.htmlspecialchars($v['value']).'"  '.(is_null($param_value)?' disabled':'').'  '.($v['value']==$this->getValue($field, $value)?' checked':'').'/> - '.htmlspecialchars($v['value']).'</label><br>';
            }
        } else {
            $html = 'Добавьте значения в <a href="/'.wa()->getConfig()->getBackendUrl().'/'.shopAdvancedparamsPlugin::APP.'/?action=plugins#/advancedparams/">настройках плагина</a>!';
        }
        return $html;
    }
    /**
     * Генерирует поле с визуальным html редактором (Redactor)
     * @access protected
     * @param array $field данные поля
     * @param null|string $value
     * @return string (HTML)
     */
    protected function getHtmlField($field, $value) {
        $name = $this->getName($field['name']);
        $real_value = '';
        if(isset($this->real_values[$field['name']])) {
            $real_value = $this->real_values[$field['name']];
        }

        $params = array(
            'class' => $this->getClass().' advancedparams_plugin-redactor-textarea',
            'value' =>  $real_value,
            'id' =>$this->getClass().'-'.$this->action_id.'-'.htmlspecialchars($field['id'])
        );
        if(is_null($value)) {
            $params['readonly'] = true;
        }
        $html = self::getEditorControl($name, $params);
        return $html;
    }

    /**
     * Возвращает полное имя поля (параметр name)
     * @access protected
     * @param string $name
     * @return string
     */
    protected function getName($name = '') {
        return !empty($this->field_namespace)? htmlspecialchars($this->field_namespace.'['.$name.']') : htmlspecialchars($name);
    }

    /**
     * Возвращает обработанное значение поля
     * @access protected
     * @param null|string $value
     * @return string
     */
    protected function getValue($field, $value) {
        if(isset($this->real_values[$field['name']])) {
            $value = $this->real_values[$field['name']];
        }
        return !is_null($value)? htmlspecialchars($value) : '';
    }

    /**
     * Возвращает обработанное значение типа поля
     * @access protected
     * @param null|string $type
     * @return string
     */
    protected function getType($type) {
        return !is_null($type)? htmlspecialchars($type) : '';
    }

    /**
     * Возвращает Сss класс поля
     * @access protected
     * @return string
     */
    protected function getClass() {
        return htmlspecialchars($this->field_class);
    }

    protected function getFieldName($field, $value) {
        $html = '<label><input type="checkbox" class="advancedparams_plugin-param-active" name="'.htmlspecialchars($this->field_namespace).'_active['.htmlspecialchars($field['id']).']" value="1" '.(!is_null($value)?'checked="true"':'').'> '.htmlspecialchars($field['title']).'<br><span class="hint">['.htmlspecialchars($field['name']).']</span></label>';
        return $html;
    }

    /**
     * Генерирует HTML код редактора (Redactor)
     * @access protected
     * @param string $field_name имя поля
     * @param array $params массив параметров для редактора с обязательным ключем value
     * @return string (HTML)
     */
    protected static function getEditorControl($field_name = '', array $params = array()){
        $app = wa();
        $lang = substr($app->getLocale(), 0, 2);

        $control_name = htmlentities($field_name, ENT_QUOTES, waHtmlControl::$default_charset);
        $value = htmlentities((string)$params['value'], ENT_QUOTES, waHtmlControl::$default_charset);
        $attributes = array_flip(array('class', 'style', 'id', 'placeholder','readonly'));

        $control = '<div style="border:1px solid #ddd"><textarea name="' . $control_name .'" ';
        foreach (array_intersect_key($params, $attributes) as $key => $val) {
            $control .= $key . '="' . $val . '" ';
        }
        $control .= '>' . $value . '</textarea></div>';

        $config = array(
            'lang'         => $lang,
            'minHeight'    => 200,
            'paragraphy'   => false,
            'convertDivs'  => false,
            'deniedTags'   => false,
            'toolbarFixed' => false,
            'plugins'      => array('fontcolor', 'fontsize', 'fontfamily', 'table','video', 'source'),
            'buttons'      => array('html', 'format', 'bold', 'italic', 'underline', 'deleted', 'video', 'unorderedlist', 'orderedlist', 'outdent', 'indent', 'image', 'link', 'table', 'alignment', 'horizontalrule','lists'),
            'imageUpload'  => '?module=pages&action=uploadimage&filelink=1',
            'imageUploadFields' => array('_csrf'=> waRequest::cookie("_csrf", "")),
            'uploadImageFields' =>  array('_csrf'=> waRequest::cookie("_csrf", "")),
        );
        $contenteditable = '';
        if(isset($params['readonly'])) {
            $contenteditable = ' $("#' . $params['id'] . '").closest(".redactor-box").find(".redactor-editor").attr("contenteditable",false); ';
        }
        $control .= '<script>$(function(){  $.shopAdvancedparamsPluginRedactor.init($("#' . $params['id'] . '"),' .json_encode( $config) . '); '.$contenteditable.'})</script>';
        return $control;
    }
}