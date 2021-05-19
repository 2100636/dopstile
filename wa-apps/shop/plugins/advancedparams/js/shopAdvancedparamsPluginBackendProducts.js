(function($){
    function var_dump () {
        var output = '', pad_char = ' ', pad_val = 4, lgth = 0, i = 0, d = this.window.document;
        var getFuncName = function (fn) {
            var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
            if (!name) {
                return '(Anonymous)';
            }
            return name[1];
        };
        var repeat_char = function (len, pad_char) {
            var str = '';
            for (var i=0; i < len; i++) {
                str += pad_char;
            }
            return str;
        };
        var getScalarVal = function (val) {
            var ret = '';
            if (val === null) {
                ret = 'NULL';
            } else if (typeof val === 'boolean') {
                ret = 'bool(' + val + ')';
            } else if (typeof val === 'string') {
                ret = 'string(' + val.length + ') "' + val + '"';
            } else if (typeof val === 'number') {
                if (parseFloat(val) == parseInt(val, 10)) {
                    ret = 'int(' + val + ')';
                } else {
                    ret = 'float(' + val + ')';
                }
            } else if (val === undefined) {
                ret = 'UNDEFINED'; // Not PHP behavior, but neither is undefined as value
            }  else if (typeof val === 'function') {
                ret = 'FUNCTION'; // Not PHP behavior, but neither is function as value
                ret = val.toString().split("\n");
                txt = '';
                for(var j in ret) {
                    txt += (j !=0 ? thick_pad : '') + ret[j] + "\n";
                }
                ret = txt;
            } else if (val instanceof Date) {
                val = val.toString();
                ret = 'string('+val.length+') "' + val + '"'
            }
            else if(val.nodeName) {
                ret = 'HTMLElement("' + val.nodeName.toLowerCase() + '")';
            }
            return ret;
        };
        var formatArray = function (obj, cur_depth, pad_val, pad_char) {
            var someProp = '';
            if (cur_depth > 0) {
                cur_depth++;
            }
            base_pad = repeat_char(pad_val * (cur_depth - 1), pad_char);
            thick_pad = repeat_char(pad_val * (cur_depth + 1), pad_char);
            var str = '';
            var val = '';
            if (typeof obj === 'object' && obj !== null) {
                if (obj.constructor && getFuncName(obj.constructor) === 'PHPJS_Resource') {
                    return obj.var_dump();
                }
                lgth = 0;
                for (someProp in obj) {
                    lgth++;
                }
                str += "array(" + lgth + ") {\n";
                for (var key in obj) {
                    if (typeof obj[key] === 'object' && obj[key] !== null && !(obj[key] instanceof Date) && !obj[key].nodeName) {
                        str += thick_pad + "["+key+"] =>\n" + thick_pad+formatArray(obj[key], cur_depth+1, pad_val, pad_char);
                    } else {
                        val = getScalarVal(obj[key]);
                        str += thick_pad + "["+key+"] =>\n" + thick_pad + val + "\n";
                    }
                }
                str += base_pad + "}\n";
            } else {
                str = getScalarVal(obj);
            }
            return str;
        };
        output = formatArray(arguments[0], 0, pad_val, pad_char);
        for ( i=1; i < arguments.length; i++ ) {
            output += '\n' + formatArray(arguments[i], 0, pad_val, pad_char);
        }
        return output;
    }
    function av(data) {
        alert(var_dump(data));
    }
    function count(mixed_var, mode) {
        var key, cnt = 0;
        if(mode == 'COUNT_RECURSIVE') mode = 1;
        if(mode != 1) mode = 0;
        for (key in mixed_var){
            cnt++;
            if(mixed_var.hasOwnProperty(key) &&  mode==1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object) ){
                cnt += count(mixed_var[key], 1);
            }
        }
        return cnt;
    }
$.shopAdvancedparamsPluginBackendProducts = {
    action: 'product',
    active: false,
    file_upload_url: '?plugin=advancedparams&action=FileUpload',
    field_name_valid: '?plugin=advancedparams&action=FieldNameValid',
    save_objects: {},
    save_request_id: 0,
    current_redactor_value:'',
    init:function () {
        if(this.active) {
            this.end();
        } else {
            this.start();
        }
    },
    getCookie: function(name) {
        var val = false;
        $.each(document.cookie.split(/;\s*/g), function(i, pair) {
            pair = pair.split('=', 2);
            if (pair[0] == name) {
                val = pair[1];
            }
        });
        return val;
    },
    setBinds: function(){
        $(document).off('change','select.advancedparams_plugin-param').on('change','select.advancedparams_plugin-param', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.setSelectValue($(this));
            return false;
        });
        $(document).off('click','.advancedparams_plugin-param-file-upload').on('click','.advancedparams_plugin-param-file-upload', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.selectFile($(this));
            return false;
        });
        $(document).off('change','.advancedparams_plugin-param-file-input').on('change','.advancedparams_plugin-param-file-input', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.fileUpload($(this));
            return false;
        });

        $(document).off('click','.advancedparams_plugin-param-file-delete').on('click','.advancedparams_plugin-param-file-delete', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.fileDelete($(this));
            return false;
        });
        $(document).off('click','.advancedparams_plugin-modal-box-close').on('click','.advancedparams_plugin-modal-box-close', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.closeModalBox($(this));
            return false;
        });
        $(document).off('click','.advancedparams_plugin-modal-background').on('click','.advancedparams_plugin-modal-background', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.closeModalBox($(this));
            return false;
        });
        $(document).off('click','.advancedparams_plugin-add-param').on('click','.advancedparams_plugin-add-param', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.add($(this));
            return false;
        });

        // валидация ключа поля
        $(document).off('change, blur','.advancedparams_plugin_custom_name').on('change, blur','.advancedparams_plugin_custom_name', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.nameValid($(this));
            return false;
        });
        // Удаление произвольного поля
        $(document).off('click','.advancedparams_plugin_custom-delete').on('click','.advancedparams_plugin_custom-delete', function () {
            $(this).closest('.field').detach();
            return false;
        });
        // Меняем активность поля при нажатии на checkbox
        $(document).off('change','.advancedparams_plugin-param-active').on('change','.advancedparams_plugin-param-active', function () {
            $.shopAdvancedparamsPluginBackendProducts.Param.setActive($(this));
            return false;
        });
        // При двойном клике активируем поле
        $(document).off('dblclick','.field').on('dblclick','.field', function () {
            if($(this).find('.advancedparams_plugin-param-active')) {
                var ch = $(this).find('.advancedparams_plugin-param-active').is(':checked');
                if(!ch) {
                    $(this).find('.advancedparams_plugin-param-active').prop('checked', true);
                    $.shopAdvancedparamsPluginBackendProducts.Param.setActive($(this).find('.advancedparams_plugin-param-active'));
                    return false;
                }
            }
        });
        // При изменении значения поля показываем кнопку сохранить
        $(document).off('change', '.advancedparams_plugin-param').on('change','.advancedparams_plugin-param', function () {
            $.shopAdvancedparamsPluginBackendProducts.Product.toggleSaveButton($(this), true);
        });

        // Убираем активность с поля если пустое значение
        $(document).off('change, blur','.advancedparams_plugin-param').on('change,  blur','.advancedparams_plugin-param', function () {
            if($(this).val().trim() == '' ||  $(this).val()=='<p>&#8203;</p>' ||  $(this).val()=='<p></p>' ) {
                var active =  $(this).closest('.field').find('.advancedparams_plugin-param-active');
                if(active.prop('checked')) {
                    active.prop('checked', false);
                    $.shopAdvancedparamsPluginBackendProducts.Param.setActive(active);
                }
            }
            return false;
        });
        $(document).off('focusin','.field .redactor-editor').on('focusin','.field .redactor-editor', function () {
            var element =  $(this).closest('.field').find('textarea');
            $.shopAdvancedparamsPluginBackendProducts.current_redactor_value = element.val();
            return false;
        });
        ///  Убираем активность поля или показываем кнопку сохранить $.shopAdvancedparamsPluginBackendProducts
        $(document).off('focusout','.field .redactor-editor').on('focusout','.field .redactor-editor', function () {
            var element =  $(this).closest('.field').find('textarea');
            var active =  $(this).closest('.field').find('.advancedparams_plugin-param-active');
            if(active.prop('checked')) {
                if( element.val()=='' || element.val()=='<p>&#8203;</p>' ||  element.val()=='<p></p>') {
                    active.prop('checked', false);
                    $.shopAdvancedparamsPluginBackendProducts.Param.setActive(active);
                } else {
                    if($.shopAdvancedparamsPluginBackendProducts.current_redactor_value!=$(this).html()) {
                        $.shopAdvancedparamsPluginBackendProducts.Product.toggleSaveButton(active, true);
                    }
                }
            }
            return false;
        });

        /// Product Save
        $(document).off('click', '.advancedparams-action-save a').on('click','.advancedparams-action-save a', function () {
            $.shopAdvancedparamsPluginBackendProducts.Product.Save($(this));
            return false;
        });
        /// Products Save ALL
        $(document).off('click', '.advancedparams_plugin-action-save-all a').on('click','.advancedparams_plugin-action-save-all a', function () {
            $.shopAdvancedparamsPluginBackendProducts.saveAll();
            return false;
        });
        /// Скрытие поля у продуктов
        $(document).off('change', '.advancedparams_plugin-field-hide').on('change','.advancedparams_plugin-field-hide', function () {
            $.shopAdvancedparamsPluginBackendProducts.hideField($(this));
            return false;
        });
    },
    start: function () {
        this.setBinds();
        this.active = true;
        var table = $('#product-list');
        var set_fields = false;
        if(table.hasClass('zebra')) {
            var columns = table.find('tr.header').find('th').length;
            var ids = {};
            var product_block_counter = 0;
            var product_counter = 1;
                table.find('tr').each(function () {
                    if($(this).hasClass('product')) {
                        var active_check = $(this).find('.drag-handle').find('input');
                        if(active_check.prop('checked')) {
                            var id = $(this).data('product-id');
                            if(!ids.hasOwnProperty(product_block_counter))  {
                                ids[product_block_counter] = {};
                            }
                            ids[product_block_counter][id] = id;
                            if(product_counter==5) {
                               product_block_counter++;
                                product_counter = 1;
                            } else {
                                product_counter++;
                            }
                        }
                    }
                });
            var success = function(response) {
                if (response.status == 'ok') {
                    var products = response.data.actions;
                    var fields = response.data.fields;
                    if(typeof (products)=='object') {
                        table.find('tr').each(function () {
                            if($(this).hasClass('product')) {
                                var id = $(this).data('product-id');
                                if(products.hasOwnProperty(id)) {
                                    var fields = products[id];
                                    var html = '<tr class="advancedparams_plugin-action" id="advancedparams_plugin-action-'+id+'" data-action_id ="'+id+'"><td>&nbsp;</td><td colspan="'+(columns-1)+'">'+fields+'</td></tr>';
                                    $(this).after(html);
                                }
                            }
                        });
                        table.removeClass('single-lined');
                    }
                    if(typeof (fields)=='object' && !set_fields) {
                        var fields_html = '';
                        for(var k in fields) {
                            if(fields.hasOwnProperty(k)) {
                                var field = fields[k];
                                fields_html += '<div class="advancedparams_plugin-field-hide"><label><input type="checkbox" value="'+field.name+'" class="advancedparams_plugin-field-hide"> ['+field.name+'] '+field.title+' </label></div>';
                            }
                        }
                        fields_html += '<div class="advancedparams_plugin-field-hide"><label><input type="checkbox" value="advancedparams_plugin_field_custom" class="advancedparams_plugin-field-hide">Произвольные поля</label></div>';

                        var html = '<div class="advancedparams_plugin-fields-hide"><h3>Скрытие полей доп. параметров</h3><p class="hint">При скрытии данные полей установленные ранее остаются не тронутыми</p>'+fields_html+'</div>';
                        table.before(html);

                    }
                    if(!set_fields) {
                        var save_html = '<div class="advancedparams_plugin-action-save-all" style="display:none;">' +
                            '<a href="#" class="button green">Сохранить все доп. параметры</a>' +
                            '</div>';
                        table.before(save_html);
                    }
                    set_fields = true;
                } else {
                    this.active = false;
                    alert(response.errors.join(','));
                }
            };
            if(count(ids) > 0) {
                for(var block in ids) {
                   var products_ids = ids[block];
                    $.ajax({
                        url: '?plugin=advancedparams&action=Products',
                        data: {ids:products_ids},
                        type: 'post',
                        dataType: 'json',
                        success: success
                    });
                }

            } else {
                this.active = false;
                alert('Выберите хотя бы один товар!');
            }
        } else {
            this.active = false;
            alert('Перейдите в табличное отображение списка товаров!');
        }
    },
    end:function () {
        $('.advancedparams_plugin-fields-hide').detach();
        $('tr.advancedparams_plugin-action').detach();
        $('.advancedparams_plugin-action-save-all').detach();
        this.active = false;
    },
    saveAll:function() {
        if(count(this.save_objects)>0) {
            return false;
        }
        var counter = 0;
        $('.advancedparams_plugin-action').each(function() {
            var save_button = $(this).find('.advancedparams-action-save');
            // Выбираем только измененные продукты
            if(save_button.is(':visible')) {
                $.shopAdvancedparamsPluginBackendProducts.save_objects[counter] = save_button;
                counter++;
            }
        });
        var progress_html = '<div class="progressbar">' +
            '<div class="progressbar-outer">' +
            ' <div class="progressbar-inner" id="advancedaprams_plugin-products-save-progressbar" style="width: 37%;"> ' +
            '</div>' +
            '</div>' +
            '</div>';
        $('.advancedparams_plugin-action-save-all').prepend(progress_html);
        if(count(this.save_objects)>0) {
            this.saveObjects();
        }
        return false;
    },
    saveObjects: function () {
        if(this.save_objects.hasOwnProperty(this.save_request_id.toString())) {
            this.setProgressBar();
            $.shopAdvancedparamsPluginBackendProducts.Product.Save(this.save_objects[this.save_request_id]);
        } else  {
            this.save_request_id = 0;
            $.shopAdvancedparamsPluginBackendProducts.save_objects = {};
            this.end();
        }
    },
    setProgressBar: function() {
        var counts = count(this.save_objects);
        var current = this.save_request_id+1;
        var percent = (current/counts)*100;
        $('#advancedaprams_plugin-products-save-progressbar').css('width',percent.toString()+'%');
    },
    hideField: function(obj) {
        var value = $(obj).val();
        var field_class = '';
        if(value=='advancedparams_plugin_field_custom') {
            field_class = 'advancedparams_plugin_field_custom';
            if($(obj).prop('checked')) {
                $('.advancedparams_plugin_add_field').hide();
            } else {
                $('.advancedparams_plugin_add_field').show();
            }
        } else {
            field_class = 'advancedparams_plugin-field-'+value;
        }

        if($(obj).prop('checked')) {
          $('.'+field_class).hide();
        } else {
            $('.'+field_class).show();
        }
    },
    toggleSaveAllButton: function (flag) {
        if(flag) {
            $('.advancedparams_plugin-action-save-all').show();
        } else {
            $('.advancedparams_plugin-action-save-all').hide();
        }
    },
    Param: {
        setActive: function (obj) {
            var field_container = $(obj).closest('.field');
            var type = field_container.data('type');

            var field =  field_container.find('.advancedparams_plugin-param');
            var flag = true;
            var checked = $(obj).is(':checked');
            if(checked) {
                flag = false;
            }
            if(type=='select') {
                field_container.find('.advancedparams_plugin-param-select').prop('disabled', flag);
                field_container.find('.advancedparams_plugin-param-hidden').prop('readonly', flag);
            } else if(type=='html') {
                field_container.find('.redactor-editor').attr('contenteditable',!flag);
                field.prop('readonly', flag);
            } else if(type=='radio') {
                field.prop('disabled', flag);
            } else {
                field.prop('readonly', flag);
            }
            $.shopAdvancedparamsPluginBackendProducts.Product.toggleSaveButton(obj,true);
        },
        setSelectValue:function (obj) {
            $(obj).parent().find('input.advancedparams_plugin-param').val($(obj).val());
        },
        getImageSizeHtml: function(data) {
            var size_type = 'none';
            var width = '';
            var height = '';
            if( typeof (data)=='object' && data.size_type && data.size_type != 'none') {
                size_type = data.size_type;
                if(!isNaN(parseInt(data.width))) {
                    width = parseInt(data.width);
                }
                if(!isNaN(parseInt(data.height))) {
                    height = parseInt(data.height);
                }
            }
            return '<div class="block" id="advancedparams_plugin-image-size">' +
                '<div class="name">Размер изображения</div>' +
                '<div class="value">' +
                '<label class="advancedparams-field-image-size-label">' +
                '<input type="radio" name="size_type" value="none" '+(size_type=="none"?" checked ":'')+'>Не менять размер' +
                '</label>' +
                '</div>' +
                '<div class="value">' +
                '<label class="advancedparams-field-image-size-label">' +
                '<input type="radio" name="size_type" value="max" '+(size_type=="max"?" checked ":'')+'>Макс. ( Ширина, Высота ) = <strong><span class="star">*</span></strong>' +
                '<input type="text" name="width" value="'+(size_type=="max"? width :'')+'" size="4" class="small-int short numerical" '+(size_type=='max'?'':' style="display: none;" disabled="disabled" ')+' >px ' +
                '</label>' +
                '</div>' +
                '<div class="value">' +
                '<label class="advancedparams-field-image-size-label">' +
                '<input type="radio" name="size_type" value="width" '+(size_type=="width"?" checked ":'')+'>Ширина = <strong><span class="star">*</span></strong>' +
                '<input type="text" name="width" value="'+(size_type=="width"? width :'')+'" size="4" class="small-int short numerical" '+(size_type=='width'?'':' style="display: none;" disabled="disabled" ')+' >px, Высота = <span class="gray">авто</span>' +
                '</label>' +
                '</div>' +
                '<div class="value">' +
                '<label class="advancedparams-field-image-size-label">' +
                '<input type="radio" name="size_type" value="height" '+(size_type=="height"?" checked ":'')+'>Ширина = <span class="gray">авто</span>, Высота = <strong><span class="star">*</span></strong>' +
                '<input type="text" name="height" value="'+(size_type=="height"? height :'')+'" size="4" class="small-int short numerical" '+(size_type=='height'?'':' style="display: none;" disabled="disabled" ')+' >px' +
                '</label>' +
                '</div>' +
                '<div class="value">' +
                '<label class="advancedparams-field-image-size-label">' +
                '<input type="radio" name="size_type" value="crop" '+(size_type=="crop"?" checked ":'')+'>Квадратная обрезка: Размер = <strong><span class="star">*</span></strong>' +
                '<input type="text" name="width" value="'+(size_type=="crop"? width :'')+'" size="4" class="small-int short numerical" '+(size_type=='crop'?'':' style="display: none;" disabled="disabled" ')+' >px' +
                '</label>' +
                '</div>' +
                '<div class="value">' +
                '<label class="advancedparams-field-image-size-label">' +
                '<input type="radio" name="size_type" value="rectangle" '+(size_type=="rectangle"?" checked ":'')+'>Ширина = <strong><span class="star">*</span></strong>' +
                '<input type="text" name="width" value="'+(size_type=="rectangle"? width :'')+'" class="small-int short numerical" '+(size_type=="rectangle"?'':' style="display: none;" disabled="disabled" ')+' >px, Высота = <strong><span class="star">*</span></strong>' +
                '<input type="text" name="height" value="'+(size_type=="rectangle"? height :'')+'" size="4" class="small-int short numerical" '+(size_type=="rectangle"?'':' style="display: none;" disabled="disabled" ')+' >px' +
                '</label>' +
                '</div>' +
                '<div class="value" style="display: none;">' +
                '<input type="submit" value="Сохранить размер" class="button green">' +
                '</div>' +
                '</div>';
        },
        selectFile: function (obj) {
            var action_id = $(obj).closest('tr.advancedparams_plugin-action').data('action_id');
            if(action_id < 1) {
                alert('Неправильный id!');
                return false;
            }
            var param_container = $(obj).closest('.advancedparams_plugin-param-file');
            var type = param_container.data('type');
            var field_name = param_container.data('name');
            var file_html = '<p><span class="hint">Загрузка исполняемых файлов запрещена. При загрузке файлов большого размера будьте уверены, что ваш хостинг позволяет это делать.</span></p>';

            var modal_form = '<div class="advancedparams_plugin-modal">' +
                '<div class="advancedparams_plugin-modal-background"></div><div class="advancedparams_plugin-modal-box" >' +
                '<div class="advancedparams_plugin-modal-box-close"><i class="icon16 close"></i></div>' +
                '<div class="advancedparams_plugin-modal-box-content">' +
                '<form method="post" action="'+$.shopAdvancedparamsPluginBackendProducts.file_upload_url+'" id="file-upload-'+field_name+'" enctype="multipart/form-data">' +
                '<input type="hidden" name="action" value="'+$.shopAdvancedparamsPluginBackendProducts.action+'">' +
                '<input type="hidden" name="action_id" value="'+action_id+'">' +
                '<input type="hidden" name="_csrf" value="'+$.shopAdvancedparamsPluginBackendProducts.getCookie("_csrf")+'">' +
                '<input type="hidden" name="type" value="'+type+'">' +
                '<input type="hidden" name="field_name" value="'+field_name+'">';
            if(type=='image') {
                var image_data ={
                    'size_type': param_container.attr('data-size_type'),
                    'width':param_container.attr('data-width'),
                    'height':param_container.attr('data-height')
                };
                modal_form += this.getImageSizeHtml(image_data);
            }
            modal_form +='<input  type="file" name="advancedparams_file" class="advancedparams_plugin-param-file-input" value="">';
            if(type == 'file') {
                modal_form += file_html;
            }
            modal_form +='<br><span class="errors" style="display: none;"></span>' +
                '</form>' +
                '</div></div></div>';
            $('body').append(modal_form);
            $('#advancedparams_plugin-image-size').find('input[name=size_type]').change(function () {
                $(this).closest('#advancedparams_plugin-image-size').find('input[type=text]').each(function () {
                    $(this).prop('disabled',true).hide();
                });
                $(this).closest('.value').find('input[type=text]').each(function () {
                    $(this).prop('disabled',false).show();
                });
            });
        },
        closeModalBox:function(obj) {
            $(obj).closest('.advancedparams_plugin-modal').detach();
        },
        fileDelete: function (obj) {
            var action_id = $(obj).closest('tr.advancedparams_plugin-action').data('action_id');
            var field = $(obj).closest('.field');
            var post_data = {};
            post_data['file_link'] = field.find('.advancedparams_plugin-param').val();
            post_data['action'] = $.shopAdvancedparamsPluginBackendProducts.action;
            post_data['action_id'] = action_id;
            var param_container = field.find('.advancedparams_plugin-param-file');
            post_data['field_name'] = param_container.data('name');
            var success = function(response) {
                if (response.status == 'ok') {
                    field.find('.advancedparams_plugin-param').val();
                    field.find('.advancedparams_plugin-param').attr('disabled', true);
                    field.find('.advancedparams_plugin-param-active').attr('checked', false);
                    field.find('.advancedparams_plugin-param-file-preview').html('');
                    field.find('.advancedparams_plugin-param-file-action').html('Вставьте ссылку файла  <input type="text" name="advancedparams_url" class="advancedparams_plugin-param-file-input" value=""><br>или <a href="#" class="advancedparams_plugin-param-file-upload"><i class="icon16 add"></i>Загрузить</a><br><span class="errors"></span>');
                } else {
                    alert(response.errors.join(','));
                }
            };
            $.ajax({
                url: '?plugin=advancedparams&action=FileDelete',
                data: post_data,
                type: 'post',
                dataType: 'json',
                success: success
            });
        },
        fileUpload: function (obj) {
            var self = this;
            var success = function(response) {
                if(typeof(response)=='object' && response.status =='ok' &&
                    typeof(response.data)=='object' && response.data.file_link) {
                    var data = response.data;
                    var field = $(document).find('#advancedparams_plugin-'+data.action_id+'-field-'+data.field_name);
                    field.find('.advancedparams_plugin-param').val(data.value);
                    field.find('.advancedparams_plugin-param').attr('disabled', false);
                    field.find('.advancedparams_plugin-param-active').attr('checked', true);
                    var file_preview = '';
                    if(data.type=='image') {
                        file_preview = '<a href="'+data.file_link+'" target="_blank">'+data.file_name+'<br><br><img src="'+data.file_link+'" class="advancedparams_plugin-param-image" /></a>';
                    } else {
                        file_preview = '<a href="'+data.file_link+'" target="_blank"><i class="icon16 download"></i>'+data.file_name+'</a>';
                    }
                    field.find('.advancedparams_plugin-param-file-preview').html(file_preview);
                    field.find('.advancedparams_plugin-param-file-action').html('<a href="#" class="advancedparams_plugin-param-file-delete"><i class="icon16 delete"></i>Удалить</a>');
                    self.closeModalBox(form);
                    $.shopAdvancedparamsPluginBackendProducts.Product.toggleSaveButton(field,true);
                } else {
                    error(response.errors.join(','));
                }
            };
            var error;
            if($(obj).attr('name')=='advancedparams_file') {
                var form =  $(obj).closest('form');
                error = function (err) {
                    form.find('.errors').html(err).show();
                    form.find('input[type=file]').val('');
                };
                var iframe_id = $(form).attr('id')+'-iframe';
                $(form).after("<iframe id=" + iframe_id + " name=" + iframe_id + " style='display:none;'></iframe>");
                $(form).attr('target', iframe_id);
                var iframe = $('#' + iframe_id);
                $(form).submit();
                iframe.load(function() {
                    var r;
                    try {
                        var data = $(this).contents().find('body').html();
                        r = $.parseJSON(data);
                    } catch (e) {
                        error(e);
                        return;
                    }
                    success(r);
                });
            } else if($(obj).attr('name')=='advancedparams_url') {
                var action_id = $(obj).closest('tr.advancedparams_plugin-action').data('action_id');
                if(action_id<1) {
                    alert('Неверный id!');
                    return false;
                }
                var field = $(obj).closest('.field');
                var post_data = {};
                post_data['advancedparams_url'] = $(obj).val();
                post_data['file_link'] = field.find('.advancedparams_plugin-param').val();
                post_data['action'] = $.shopAdvancedparamsPluginBackendProducts.action;
                post_data['action_id'] = action_id;
                var param_container = field.find('.advancedparams_plugin-param-file');
                post_data['field_name'] = param_container.data('name');
                post_data['size_type'] =  param_container.attr('data-size_type');
                post_data['width'] = param_container.attr('data-width');
                post_data[ 'height'] = param_container.attr('data-height');
                error = function (err) {
                    field.find('.errors').html(err).show();
                    field.find('input[type=text]').val('');
                };
                $.ajax({
                    url: $.shopAdvancedparamsPluginBackendProducts.file_upload_url,
                    data:  post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
            }
        },
        nameValid: function(obj) {
            var name = $(obj).val();
            var success = function(response) {
                if(typeof(response)=='object' && response.status =='ok') {

                } else {
                    $(obj).val('');
                    alert(response.errors.join(','));
                }
            };
            $.ajax({
                url: $.shopAdvancedparamsPluginBackendProducts.field_name_valid,
                data:  {'name':name},
                type: 'post',
                dataType: 'json',
                success: success
            });
        },
        add: function (obj) {
            var add_field = $(obj).closest('.field');
            var rand = function() {
                return Math.random().toString(36).substr(2); // remove `0.`
            };
            var token = function() {
                return rand() + rand(); // to make it longer
            };
            var md5 = token();
            var html = '<div class="field" id="advancedparams_plugin-field-'+md5+'" data-type="custom">' +
                '<div class="name">' +
                ' <input type="text" class="advancedparams_plugin-param advancedparams_plugin_custom_name" name="advancedparams_plugin['+md5+'][name]">' +
                ' </div>' +
                '<div class="value">' +
                ' <input type="text" class="advancedparams_plugin-param advancedparams_plugin_custom_value"  name="advancedparams_plugin['+md5+'][value]"> ' +
                '<a href="#" class="advancedparams_plugin_custom-delete inline"><i class="icon16 delete"></i></a>' +
                '</div>';
            add_field.before(html);
        }
    },
    Product: {
        Save: function (obj) {
            var form = $(obj).closest('form');
            var href = form.attr('action');
            var success = function(response) {
                if (response.status == 'ok') {
                    form.find('.advancedparams-action-save').hide();
                    if(count($.shopAdvancedparamsPluginBackendProducts.save_objects)>0) {
                        $.shopAdvancedparamsPluginBackendProducts.save_request_id++;
                        setTimeout(function () {
                            $.shopAdvancedparamsPluginBackendProducts.saveObjects();
                        },200);
                    }
                } else {
                    alert(response.errors.join(','));
                }
            };
            $.ajax({
                url:  href,
                data: form.serialize(),
                type: 'post',
                dataType: 'json',
                success: success
            });
        },
        toggleSaveButton: function (obj,flag) {
            if(flag) {
                $(obj).closest('form').find('.advancedparams-action-save').show();
            } else {
                $(obj).closest('form').find('.advancedparams-action-save').hide();
            }
            $.shopAdvancedparamsPluginBackendProducts.toggleSaveAllButton(true);
        }
    }
};
$(document).ready(function () {
    // Активируем редактирование доп параметров продуктов
    $(document).off('click', '#advancedparams_plugin-edit-products').on('click','#advancedparams_plugin-edit-products', function () {
            $.shopAdvancedparamsPluginBackendProducts.init($(this));
            return false;
    });
});
})(jQuery);



