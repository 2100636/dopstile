(function($){
$.shopAdvancedparamsPlugin = {
    action: '',
    action_id:0,
    file_upload_url: '?plugin=advancedparams&action=FileUpload',
    field_name_valid: '?plugin=advancedparams&action=FieldNameValid',
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
    init: function(action, action_id) {
        this.action = action;
        this.setActionId(action_id);
        var hide_selector = '';
        if(this.action=='category') {
            hide_selector = 'textarea[name=params]';
        } else if(this.action=='product') {
            hide_selector = 'textarea[name="product[params]"]';
        } else if (this.action=='page') {
            hide_selector = 'textarea[name=other_params]';
        }
        $(hide_selector).closest('.field').hide();
    },
    setActionId:function (action_id) {
        if(!isNaN(parseInt(action_id))) {
            this.action_id = parseInt(action_id);
        }
    },
    getPageId: function () {
        if(this.action == 'product' && this.action_id < 1) {
            this.setActionId($('#s-product-edit-forms').find('input[name="product[id]"]').val());
        }
        return this.action_id;
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
                        '<input type="text" name="width" value="'+(size_type=="rectangle"? width :"")+'" class="small-int short numerical" '+(size_type=="rectangle"?"":' style="display: none;" disabled="disabled" ')+' >px, Высота = <strong><span class="star">*</span></strong>' +
                        '<input type="text" name="height" value="'+(size_type=="rectangle"? height :"")+'" size="4" class="small-int short numerical" '+(size_type=="rectangle"?'':' style="display: none;" disabled="disabled" ')+' >px' +
                    '</label>' +
                '</div>' +
                '<div class="value" style="display: none;">' +
                    '<input type="submit" value="Сохранить размер" class="button green">' +
                '</div>' +
                '</div>';
        },
        selectFile: function (obj) {
            if($.shopAdvancedparamsPlugin.getPageId()<1) {
                if($.shopAdvancedparamsPlugin.action == 'product') {
                    alert('Сначала сохраните продукт!');
                } else {
                    alert('Загрузка файлов доступна только после сохранения!');
                }
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
                '<form method="post" action="'+$.shopAdvancedparamsPlugin.file_upload_url+'" id="file-upload-'+field_name+'" enctype="multipart/form-data">' +
                '<input type="hidden" name="action" value="'+$.shopAdvancedparamsPlugin.action+'">' +
                '<input type="hidden" name="action_id" value="'+$.shopAdvancedparamsPlugin.getPageId()+'">' +
                '<input type="hidden" name="type" value="'+type+'">' +
                '<input type="hidden" name="_csrf" value="'+$.shopAdvancedparamsPlugin.getCookie("_csrf")+'">' +
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
            var field = $(obj).closest('.field');
            var post_data = {};
            post_data['file_link'] = field.find('.advancedparams_plugin-param').val();
            post_data['action'] = $.shopAdvancedparamsPlugin.action;
            post_data['action_id'] = $.shopAdvancedparamsPlugin.getPageId();
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
                if($.shopAdvancedparamsPlugin.getPageId()<1) {
                    if($.shopAdvancedparamsPlugin.action == 'product') {
                        alert('Сначала сохраните продукт!');
                    } else {
                        alert('Загрузка файлов доступна только после сохранения!');
                    }
                    return false;
                }
                var field = $(obj).closest('.field');
                var post_data = {};
                post_data['advancedparams_url'] = $(obj).val();
                post_data['file_link'] = field.find('.advancedparams_plugin-param').val();
                post_data['action'] = $.shopAdvancedparamsPlugin.action;
                post_data['action_id'] = $.shopAdvancedparamsPlugin.getPageId();
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
                    url: $.shopAdvancedparamsPlugin.file_upload_url,
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
                url: $.shopAdvancedparamsPlugin.field_name_valid,
                data:  {'name':name},
                type: 'post',
                dataType: 'json',
                success: success
            });
        },
        add: function (obj) {
            var con = $(obj).closest('.field');
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
            con.before(html);
        }
    }
};
$(document).ready(function () {
    $(document).off('change','.advancedparams_plugin-param-active').on('change','.advancedparams_plugin-param-active', function () {
        $.shopAdvancedparamsPlugin.Param.setActive($(this));
        return false;
    });
    $(document).off('change','select.advancedparams_plugin-param').on('change','select.advancedparams_plugin-param', function () {
        $.shopAdvancedparamsPlugin.Param.setSelectValue($(this));
        return false;
    });
    $(document).off('click','.advancedparams_plugin-param-file-upload').on('click','.advancedparams_plugin-param-file-upload', function () {
        $.shopAdvancedparamsPlugin.Param.selectFile($(this));
        return false;
    });
    $(document).off('change','.advancedparams_plugin-param-file-input').on('change','.advancedparams_plugin-param-file-input', function () {
        $.shopAdvancedparamsPlugin.Param.fileUpload($(this));
        return false;
    });

    $(document).off('click','.advancedparams_plugin-param-file-delete').on('click','.advancedparams_plugin-param-file-delete', function () {
        $.shopAdvancedparamsPlugin.Param.fileDelete($(this));
        return false;
    });
    $(document).off('click','.advancedparams_plugin-modal-box-close').on('click','.advancedparams_plugin-modal-box-close', function () {
        $.shopAdvancedparamsPlugin.Param.closeModalBox($(this));
        return false;
    });
    $(document).off('click','.advancedparams_plugin-modal-background').on('click','.advancedparams_plugin-modal-background', function () {
        $.shopAdvancedparamsPlugin.Param.closeModalBox($(this));
        return false;
    });
    $(document).off('click','.advancedparams_plugin-add-param').on('click','.advancedparams_plugin-add-param', function () {
        $.shopAdvancedparamsPlugin.Param.add($(this));
        return false;
    });
    $(document).off('click','.advancedparams_plugin-toggle').on('click','.advancedparams_plugin-toggle', function () {
        var fields_container  = $('.advancedparams_plugin-fields');
        if(fields_container.hasClass('advancedparams_plugin-hide')) {
            fields_container.removeClass('advancedparams_plugin-hide');
            $(this).find('i').removeClass('rarr');
            $(this).find('i').addClass('darr');
        } else {
            fields_container.addClass('advancedparams_plugin-hide');
            $(this).find('i').removeClass('darr');
            $(this).find('i').addClass('rarr');
        }
        return false;
    });
    
    // валидация ключа поля
    $(document).off('change, blur','.advancedparams_plugin_custom_name').on('change, blur','.advancedparams_plugin_custom_name', function () {
        $.shopAdvancedparamsPlugin.Param.nameValid($(this));
        return false;
    });
    
    $(document).off('click','.advancedparams_plugin_custom-delete').on('click','.advancedparams_plugin_custom-delete', function () {
        $(this).closest('.field').detach();
        return false;
    });
    // При двойном клике активируем поле
    $(document).off('dblclick','.field').on('dblclick','.field', function () {
        if($(this).find('.advancedparams_plugin-param-active')) {
            var ch = $(this).find('.advancedparams_plugin-param-active').is(':checked');
            if(!ch) {
                $(this).find('.advancedparams_plugin-param-active').prop('checked', true);
                $.shopAdvancedparamsPlugin.Param.setActive($(this).find('.advancedparams_plugin-param-active'));
                return false;
            }
        }
    });
    // Убираем активность с поля если пустое значение
    $(document).off('change, blur','.advancedparams_plugin-param').on('change,  blur','.advancedparams_plugin-param', function () {
       if($(this).val().trim() == '' || ( $(this).attr('type')=='textarea' && $(this).val()=='<p>&#8203;</p>')  ) {
          var active =  $(this).closest('.field').find('.advancedparams_plugin-param-active');
           if(active.prop('checked')) {
               active.prop('checked', false);
               $.shopAdvancedparamsPlugin.Param.setActive(active);
           }
       }  
       return false;
    });
    // Убиравем активность поля с визуального редактора, для тех кто стер и думает , задержка 2 с
    $(document).off('DOMSubtreeModified','.field .redactor-editor').on('DOMSubtreeModified','.field .redactor-editor', function () {
       var element =  $(this).closest('.field').find('textarea');
        if( (element.val()=='' || element.val()=='<p>&#8203;</p>')) {
            var active =  $(this).closest('.field').find('.advancedparams_plugin-param-active');
            if(active.prop('checked')) {
                setTimeout(function () {
                    if((element.val()=='' || element.val()=='<p>&#8203;</p>')) {
                        active.prop('checked', false);
                        $.shopAdvancedparamsPlugin.Param.setActive(active);
                    }
                },'2000');
               
            }
        }
        return false;
    });
});
})(jQuery);