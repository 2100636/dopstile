(function($){
$.shopAdvancedparamsPluginBackend = {
    default_action: '?plugin=advancedparams&action=fields&type=category',
    field_types_selectable: {},
    action:'',
    hooks:{},
    field_types: {},
    init: function() {
        this.load(this.default_action);
    },
    loadAction: function (obj) {
        var link = $(obj).attr('href');
        this.load(link);
        return false;
    },
    load: function (url,  call_function) {
        $("#advancedparams-content").html('<div class="block triple-padded"><i class="icon16 loading"></i>Loading...</div>');
        return  $.get(url, function (result) {
           $("#advancedparams-content").html(result);
            if (typeof call_function === 'function') {
                call_function.call(this);
            }
        });
    },
    Field: {
        field_save_url: '?plugin=advancedparams&action=FieldSave',
        field_delete_url: '?plugin=advancedparams&action=FieldDelete',
        field_change_type_url: '?plugin=advancedparams&action=FieldChangeType',
        field_image_size_type_url:'?plugin=advancedparams&action=fieldImageTypeSave',
        field_value_save_url: '?plugin=advancedparams&action=FieldValueSave',
        field_value_delete_url: '?plugin=advancedparams&action=FieldValueDelete',

        field_container_selector: '.advancedparams-field',
        field_name_selector: '.advancedparams-field-name',
        field_title_selector: '.advancedparams-field-title',
        field_description_selector: '.advancedparams-field-description',
        field_type_selector: '.advancedparams-field-type',
        field_save_selector: '.advancedparams-field-save',
        field_edit_selector: '.advancedparams-field-edit',
        field_values_container: '.advancedparams-field-data',
        field_value_selector: '.advancedparams-field-value',
        Add: function(obj) {
            var field_types = $.shopAdvancedparamsPluginBackend.field_types;
            var types = '<div class="field" style="display: none"><div class="name">Тип параметра</div><div class="value"><select name="type" class="advancedparams-field-type-edit">';
            if(typeof (field_types)=='object') {
                for(var k in field_types) {
                    if(field_types.hasOwnProperty(k)) {
                        types += '<option value="'+k+'">'+field_types[k]+'</option>';
                    }
                }
            } else {
                types += '<option value="input">Текстовое поле</option>';
            }
            types +=  '</select></div></div>';
            var name = $(obj).data('name');
            var html = '<div class="fields-group advancedparams-field advancedparams-field-new">' +
                '<div class="advancedparams-field-delete" style="display: none;"><a href="#"><i class="icon16 delete"></i></a></div>' +
                '<input type="hidden" name="action" value="'+$.shopAdvancedparamsPluginBackend.action+'">' +
                '<input type="hidden" name="id" value="">' +
                '<div class="field">' +
                '<div class="name">Ключ поля</div>' +
                '<div class="value">' +
                '<span class="advancedparams-field-name" style="display: none;"></span>' +
                '<input class="advancedparams-field-name-edit" name="name" value="'+(name? name:"")+'" >' +
                '<a href="#" class="advancedparams-field-edit" style="display: none;"><i class="icon16 edit"></i></a>' +
                ' </div>' +
                '</div>' +
                '<div class="field">' +
                    '<div class="name">Название поля</div>' +
                    '<div class="value">' +
                        '<span class="advancedparams-field-title" style="display: none;"></span>' +
                        '<input class="advancedparams-field-title-edit" name="title">' +
                    ' </div>' +
                '</div>' +
                '<div class="field">' +
                '<div class="name">Описание поля</div>' +
                '<div class="value">' +
                ' <span class="hint advancedparams-field-description" style="display: none;"></span>' +
                '<textarea class="advancedparams-field-description-edit" name="description"></textarea>' +
                '</div>' +
                ' </div>' +
                     types+
                ' <div class="field advancedparams-field-save">' +
                ' <div class="value submit">' +
                ' <a href="#" class="button yellow">Сохранить</a>  или <a href="#" class="advancedparams-field-cancel">отмена</a>' +
                ' </div>' +
                ' </div>' +
                '</div>';
            if($(obj).hasClass('button')) {
                $(obj).closest('.field').before(html);
            } else {
                $(obj).closest('.advancedparams-field-new').before(html);
                $(obj).closest('.advancedparams-field-new').hide();
                
            }

        },
        Edit: function(obj) {
           var field =  $(obj).closest(this.field_container_selector);
            field.find(this.field_title_selector).hide();
            field.find(this.field_description_selector).hide();
            field.find(this.field_title_selector+'-edit').show();
            field.find(this.field_description_selector+'-edit').show();
            field.find(this.field_save_selector).show();
        },
        Save: function (obj) {
            var field =  $(obj).closest(this.field_container_selector);
            var post_data = {};
            post_data['title'] =  field.find(this.field_title_selector+'-edit').val();
            post_data['description'] =  field.find(this.field_description_selector+'-edit').val();
            post_data['action'] = '';
            post_data['name'] = '';
            field.find('input[type=hidden]').each(function () {
                if($(this).val()!='') {
                    post_data[$(this).attr('name')] = $(this).val();
                }
            });
            if(post_data['action'] =='') {
                post_data['action'] = $.shopAdvancedparamsPluginBackend.action;
            }
            if(post_data['name'] =='') {
                post_data['name'] =  field.find(this.field_name_selector+'-edit').val();
            }
            var self = this;
            var success = function(response) {
                if (response.status == 'ok') {
                    var data = response.data;
                    // Записываем и скрываем поля
                    if(!field.data('id')) {
                        field.data('id',data.id);
                        field.find('input[name=id]').val(data.id);
                        field.find(self.field_name_selector+'-edit').attr('type', 'hidden');
                        field.find(self.field_name_selector).text(data.name).show();
                        field.find(self.field_type_selector+'-edit').val(data.type).closest('.field').show();
                        field.find('.advancedparams-field-delete').show();
                        field.removeClass('advancedparams-field-new');
                    }
                    field.find(self.field_description_selector+'-edit').val(data.description).hide();
                    field.find(self.field_title_selector+'-edit').val(data.title).hide();
                    // Показываем значения
                    field.find(self.field_title_selector).text(data.title).show();
                    field.find(self.field_description_selector).text(data.description).show();
                    // Обновляем скрытые поля
                    field.find('input[type=hidden]').each(function () {
                        if(data[$(this).attr('name')]) {
                            $(this).val(data[$(this).attr('name')]);
                        }
                    });
                    field.find(self.field_save_selector).hide();
                    field.find(self.field_edit_selector).show();
                } else {
                    alert(response.errors.join(','));
                }
            };
            $.ajax({
                url: this.field_save_url,
                data: post_data,
                type: 'post',
                dataType: 'json',
                success: success
            });
        },
        Delete: function(obj, force) {
            var field =  $(obj).closest(this.field_container_selector);
            var field_id = field.data('id');
            var self = this;
            if(field_id) {
                var post_data = {id: field_id};
                if(force == 1) {
                    post_data['force'] = 1;
                }
                var success = function(response) {
                    if(response.status =='ok') {
                        var data = response.data;
                        if(typeof (data) =='object') {
                            // Если пришел запрос на подтверждение удаления данных
                            if(data['confirm']) {
                                if(confirm(data['confirm'])) {
                                    // Удаляем поле и все данные доп параметров экшена
                                    self.Delete(obj, 1);
                                }
                            }
                        } else {
                            if(data=='ok') {
                                field.detach();
                            }
                        }
                    } else {
                        alert(response.errors.join(','));
                    }
                };
                $.ajax({
                    url: this.field_delete_url,
                    data: post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
            } else {
                alert('Не определен идентификатор поля!');
            }
        },
        imageSizeChange: function (obj) {
           var  change_flag = false;
            var field_container = $(obj).closest(this.field_container_selector);
                $(obj).closest('.value').find('input[type=text]').each(function () {
                    var name  = $(this).attr('name');
                    if( field_container.attr('data-'+name) != $(this).val() ) {
                       change_flag = true;
                    }
                });
                var submit_button = $(obj).closest('.advancedparams-field-image-size').find('form').find('input[type=submit]');
            this.setActiveImageSizeButton(submit_button,change_flag);
        },
        imageSizeTypeSave: function (obj) {
            var form = $(obj).closest('form');
            var field_container = form.closest(this.field_container_selector);
            var success = function(response) {
                if (typeof(response)=='object' && response.status == 'ok') {
                    var data = response.data;
                    for(var k in data) {
                        if(data.hasOwnProperty(k)) {
                            field_container.attr('data-'+k,data[k]);
                            form.find('input[name='+k+']').val(data[k]);
                        }
                    }
                    form.find('input[type=submit]').closest('.value').hide();
                } else {
                    alert(response.errors.join(','));
                }
            };
            $.ajax({
                url: this.field_image_size_type_url,
                data: form.serialize(),
                type: 'post',
                dataType: 'json',
                success: success
            });
            return false;
        },
        imageSizeTypeChange: function (obj) {
            var size_container =  $(obj).closest('.advancedparams-field-image-size');
            size_container.find('input[type=text]').each(function () {
                $(this).prop('disabled',true).hide();
            });
            $(obj).closest('.value').find('input[type=text]').each(function () {
                $(this).prop('disabled',false).show();
            });
            var  change_flag = false;
            var field_container = $(obj).closest(this.field_container_selector);
            var submit_button = $(obj).closest('.advancedparams-field-image-size').find('form').find('input[type=submit]');
            if( $(obj).val() != field_container.attr('data-size_type')  ) {
                change_flag = true;
            } else {
                $(obj).closest('.value').find('input[type=text]').each(function () {
                    var name  = $(this).attr('name');
                    if( field_container.attr('data-'+name) != $(this).val() ) {
                        change_flag = true;
                    }
                });
            }
             this.setActiveImageSizeButton(submit_button,change_flag);
        },
        setActiveImageSizeButton:function (button, active) {
            if(active) {
                // вместо тугла проверяем
                if(button.hasClass('green')) { 
                    button.removeClass('green');
                }
                if(!button.hasClass('yellow')) {
                    button.addClass('yellow');
                }
                button.closest('.value').show();
            } else {
                // вместо тугла проверяем
                if(button.hasClass('yellow')) {
                    button.removeClass('yellow');
                }
                if(!button.hasClass('green')) {
                    button.addClass('green');
                }
                button.closest('.value').hide();
            }
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
            return '<div class="field advancedparams-field-data advancedparams-field-image-size">' +
                '<form action="'+this.field_image_size_type_url+'" method="post">' +
                    '<input type="hidden" name="field_id" value="'+data.id+'">' +
                    '<div class="name">Размер изображения</div>' +
                    '<div class="value">' +
                        '<label class="advancedparams-field-image-size-label">' +
                            '<input type="radio" name="size_type" value="none" '+(size_type=='none'?' checked':'')+'>Не менять размер' +
                        '</label>' +
                    '</div>' +
                    '<div class="value">' +
                        '<label class="advancedparams-field-image-size-label">' +
                            '<input type="radio" name="size_type" value="max" '+(size_type=='max'?' checked':'')+'>Макс. ( Ширина, Высота ) = <strong><span class="star">*</span></strong>' +
                            '<input type="text" name="width" value="'+(size_type=='max'? width :'')+'" size="4" class="small-int short numerical" '+(size_type=='max'?'':' style="display: none;" disabled="disabled"')+'>px ' +
                        '</label>' +
                    '</div>' +
                    '<div class="value">' +
                        '<label class="advancedparams-field-image-size-label">' +
                            '<input type="radio" name="size_type" value="width" '+(size_type=='width'?' checked':'')+'>Ширина = <strong><span class="star">*</span></strong>' +
                            '<input type="text" name="width" value="'+(size_type=='width'? width :'')+'" size="4" class="small-int short numerical" '+(size_type=='width'?'':' style="display: none;" disabled="disabled"')+'>px, Высота = <span class="gray">авто</span>' +
                        '</label>' +
                    '</div>' +
                    '<div class="value">' +
                        '<label class="advancedparams-field-image-size-label">' +
                            '<input type="radio" name="size_type" value="height" '+(size_type=='height'?' checked':'')+'>Ширина = <span class="gray">авто</span>, Высота = <strong><span class="star">*</span></strong>' +
                            '<input type="text" name="height" value="'+(size_type=='height'? height :'')+'" size="4" class="small-int short numerical" '+(size_type=='height'?'':' style="display: none;" disabled="disabled"')+'>px' +
                        '</label>' +
                    '</div>' +
                    '<div class="value">' +
                        '<label class="advancedparams-field-image-size-label">' +
                            '<input type="radio" name="size_type" value="crop" '+(size_type=='crop'?' checked':'')+'>Квадратная обрезка: Размер = <strong><span class="star">*</span></strong>' +
                            '<input type="text" name="width" value="'+(size_type=='crop'? width :'')+'" size="4" class="small-int short numerical" '+(size_type=='crop'?'':' style="display: none;" disabled="disabled"')+'>px' +
                        '</label>' +
                    '</div>' +
                    '<div class="value">' +
                        '<label class="advancedparams-field-image-size-label">' +
                            '<input type="radio" name="size_type" value="rectangle" '+(size_type=='rectangle'?' checked':'')+'>Ширина = <strong><span class="star">*</span></strong>' +
                            '<input type="text" name="width" value="'+(size_type=='rectangle'? width :'')+'" class="small-int short numerical" '+(size_type=='rectangle'?'':' style="display: none;" disabled="disabled"')+'>px, Высота = <strong><span class="star">*</span></strong>' +
                            '<input type="text" name="height" value="'+(size_type=='rectangle'? height :'')+'" size="4" class="small-int short numerical" '+(size_type=='rectangle'?'':' style="display: none;" disabled="disabled"')+'>px' +
                        '</label>' +
                    '</div>' +
                    '<div class="value" style="display: none;">' +
                        '<input type="submit" value="Сохранить размер" class="button green">' +
                    '</div>' +
                '</form>' +
            ' </div>';
        },
        getSelectHtml:function (data) {
            var html = '<div class="field advancedparams-field-data">' +
                '<div class="name">Значения поля</div>' +
                '<div class="value">';
            var values = data.values;
            for (var k in values) {
                if(values.hasOwnProperty(k)) {
                    var value = values[k];
                    html += '<div class="advancedparams-field-value" data-id="' + value["id"] + '">' +
                        '<input type="radio"  class="advancedparams-field-value-default" name="default_value[' + value["field_id"] + ']" value="' + value["id"] + '" ' + (parseInt(value['default']) == 1 ? " checked " : "") + '> ' +
                        '<input type="text" name="value[' + value["id"] + ']" class="advancedparams-field-value-input" value="' + value["value"] + '">' +
                        '<a href="#" class="advancedparams-field-value-delete"> <i class="icon16 delete"></i></a>' +
                        '</div>';
                }
            }
            html += '<a href="#" class="advancedparams-field-value-add"><i class="icon16 add"></i>Добавить значение</a>' +
                '</div></div>';
            return html;

        },
        selectType :function (obj) {
            var field =  $(obj).closest(this.field_container_selector);
            var type = $(obj).val();
            if($.shopAdvancedparamsPluginBackend.field_types_selectable[type]) {
                if(!confirm('Внимание! При смене типа на выбираемый, все наденные значения будут записаны как варианты выбора! Продолжить?')) {
                    return false;
                }
            }
            var id = field.data('id');
            if(id) {
                var post_data = {};
                post_data['id'] = id;
                post_data['type'] = type;
                var self = this;
                var success = function(response) {
                    if (response.status == 'ok') {
                        var data = response.data;
                        var html = null;
                        if($.shopAdvancedparamsPluginBackend.field_types_selectable[data.type] && typeof (data.values) =='object') {
                            html = self.getSelectHtml(data);
                        } else if(data.type=='image') {
                            html = self.getImageSizeHtml(data);
                        }
                        field.find(self.field_values_container).detach();
                        if(html) {
                            field.append(html);
                        }
                       
                    } else {
                        alert(response.errors.join(','));
                    }
                };
                $.ajax({
                    url: this.field_change_type_url,
                    data: post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
            } else {
                alert('Не определен идентификатор поля!');
            }
        },
        valueAdd: function (obj) {
            var field =  $(obj).closest(this.field_container_selector);
            var field_id = field.data('id');
            if(field_id) {
                var post_data = {field_id: field_id};
                var success = function(response) {
                    if (response.status == 'ok') {
                        var data = response.data;
                        var html = '<div class="advancedparams-field-value" data-id="'+data.id+'">' +
                            '<input type="radio"  class="advancedparams-field-value-default" name="default_value['+data.field_id+']" value="'+data.id+'" '+(parseInt(data.default)>0?" checked ":"")+'> '+
                            '<input type="text" name="value['+data.id+']" class="advancedparams-field-value-input" value="'+data.value+'">' +
                            '<a href="#" class="advancedparams-field-value-delete"> <i class="icon16 delete"></i></a>' +
                            '</div>';
                        $(obj).before(html);
                    } else {
                        alert(response.errors.join(','));
                    }
                };
                $.ajax({
                    url: this.field_value_save_url,
                    data: post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
            } else {
                alert('Неверный идентификатор поля!');
            }
        },
        valueChange: function (obj) {
            var value_container =  $(obj).closest(this.field_value_selector);
            var value_id = value_container.data('id');
            var value = $(obj).val();
            if(value_id) {
                var post_data = {id: value_id,value: value};
                var success = function(response) {
                    if (response.status == 'ok') {
                        var data = response.data;
                        value_container.find('.advancedparams-field-value-input').val(data.value);
                    } else {
                        alert(response.errors.join(','));
                    }
                };
                $.ajax({
                    url: this.field_value_save_url,
                    data: post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
            } else {
                alert('Неверный идентификатор значения!');
            }
        },
        valueSelectDefault: function (obj) {
            var value_container =  $(obj).closest(this.field_value_selector);
            var value_id = value_container.data('id');
            if(value_id) {
                var post_data = {id: value_id, default: 1};
                var success = function(response) {
                    if (response.status == 'ok') {
                    } else {
                        alert(response.errors.join(','));
                    }
                };
                $.ajax({
                    url: this.field_value_save_url,
                    data: post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
            } else {
                alert('Неверный идентификатор значения!');
            }
        },
        valueDelete: function(obj) {
           var value =  $(obj).closest(this.field_value_selector);
           var value_id = value.data('id');
           if(value_id) {
                var post_data = {id: value_id};
                var success = function(response) {
                    if(response.status =='ok') {
                        value.detach();
                    } else {
                        alert(response.errors.join(','));
                    }
                };
                $.ajax({
                    url: this.field_value_delete_url,
                    data: post_data,
                    type: 'post',
                    dataType: 'json',
                    success: success
                });
           }
        }
    }
};
$(document).ready(function () {
    $(document).off('click','.advancedparams-action-menu a').on('click','.advancedparams-action-menu a', function () {
        $.shopAdvancedparamsPluginBackend.loadAction($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-edit').on('click','.advancedparams-field-edit', function () {
        $.shopAdvancedparamsPluginBackend.Field.Edit($(this));
        return false;
    });
    $(document).off('change','.advancedparams-field-type-edit').on('change','.advancedparams-field-type-edit', function () {
        $.shopAdvancedparamsPluginBackend.Field.selectType($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-save').on('click','.advancedparams-field-save', function () {
        $.shopAdvancedparamsPluginBackend.Field.Save($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-add').on('click','.advancedparams-field-add', function () {
        $.shopAdvancedparamsPluginBackend.Field.Add($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-delete').on('click','.advancedparams-field-delete', function () {
        $.shopAdvancedparamsPluginBackend.Field.Delete($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-value-add').on('click','.advancedparams-field-value-add', function () {
        $.shopAdvancedparamsPluginBackend.Field.valueAdd($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-value-delete').on('click','.advancedparams-field-value-delete', function () {
        $.shopAdvancedparamsPluginBackend.Field.valueDelete($(this));
        return false;
    });
    $(document).off('change','.advancedparams-field-value-input').on('change','.advancedparams-field-value-input', function () {
        $.shopAdvancedparamsPluginBackend.Field.valueChange($(this));
        return false;
    });
    $(document).off('change','.advancedparams-field-value-default').on('change','.advancedparams-field-value-default', function () {
        $.shopAdvancedparamsPluginBackend.Field.valueSelectDefault($(this));
        return false;
    });
    $(document).off('click','.advancedparams-field-cancel').on('click','.advancedparams-field-cancel', function () {
        if($(this).closest('.advancedparams-fields-new').hasClass('fields-group')) {
            var name = $(this).closest('.advancedparams-field-new').find('input[name=name]').val();
            $('#advancedparams-field-new-'+name).show();
           
        } 
        $(this).closest('.advancedparams-field-new').detach();
     
        return false;
    });
    $(document).off('change','.advancedparams-field-image-size input[name=size_type]').on('change','.advancedparams-field-image-size input[name=size_type]', function () {
        $.shopAdvancedparamsPluginBackend.Field.imageSizeTypeChange($(this));
    });
    $(document).off('change','.advancedparams-field-image-size input[type=text]').on('change','.advancedparams-field-image-size input[type=text]', function () {
        $.shopAdvancedparamsPluginBackend.Field.imageSizeChange($(this));
    });
    $(document).off('submit','.advancedparams-field-image-size form').on('submit','.advancedparams-field-image-size form', function () {
        $.shopAdvancedparamsPluginBackend.Field.imageSizeTypeSave($(this));
        return false;
    });
});
})(jQuery);