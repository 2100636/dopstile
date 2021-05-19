$(function () {

    var self = {
        init: function () {
            self.events();
        },

        publishedIbutton: function (checkbox, action) {
            checkbox.iButton({
                labelOn: '',
                labelOff: '',
                className: 'mini'
            }).change(function () {
                var $checkbox = $(this);
                var $dialog = $checkbox.closest('.dialog');
                var url_container = $('.published-url-container');
                var loading = $('<i class="icon16 loading middle"></i>');
                var error_message = $('.dialog-error-message');
                var $hidden_fields = $dialog.find('.hidden-field');
                var $published_status_selector = $dialog.find('.published-status-selector');

                error_message.empty();
                $dialog.find('.published-status-selector').after(loading);

                //hack to work around a bug in waHtmlControl
                $checkbox.data('path', $checkbox.data('path').split('\\/').join('/'));

                $published_status_selector.find('.published-status-item').toggleClass('gray');

                $.post('?module=dialog&action=' + action, {
                    path: $checkbox.data('path'),
                    status: $checkbox.is(':checked') ? 1 : 0
                }, function (response) {
                    loading.remove();
                    if (response.status == 'ok') {
                        $dialog.find('.published-url').val(response.data.url);
                        $dialog.find('.published-password-value').val(response.data.password);
                        $dialog.find('.published-password-empty').hide();
                        $dialog.find('.published-password-exists').show();
                        if ($checkbox.is(':checked')) {
                            $dialog.find('.published-url').select();
                            url_container.slideDown(function () {
                                $dialog.find('.published-url').select();
                            });
                            $hidden_fields.slideDown();
                        } else {
                            url_container.slideUp();
                            $hidden_fields.slideUp();
                        }
                    } else {
                        error_message.html(response.errors.join(' '));
                    }
                }, 'json');
            });
        },

        events: function () {
          //delete item
            $(document).on('click', '.logs-action-delete', function () {
                var icon = $(this);
                var path = icon.data('path');

                $('<p><i class="icon16 loading"></i></p>').waDialog({
                    buttons: '<input type="submit" value="' + $.loc['Delete'] + '" class="button blue small">'
                        + ' <a href="" class="cancel">' + $.loc['cancel'] + '</a>',
                    height: '200px',
                    onLoad: function () {
                        var $dialog = $(this);
                        var $submit = $dialog.find('.dialog-buttons :submit');

                        $submit.attr('disabled', true);
                        $.get('?module=dialog&action=delete&path=' + path, function (html) {
                            $dialog.find('.loading').parent().replaceWith(html);
                            $submit.attr('disabled', false);
                        });
                    },
                    onSubmit: function (dialog) {
                        $dialog = $(dialog);

                        var update_size_param = $('.total-size').length > 0 ? '&update_size=1' : '';
                        var $loading = $('<i class="icon16 loading middle left-margin"></i>');

                        $dialog.find('.errormsg').empty();
                        $dialog.find('.cancel').after($loading);

                        $.post('?action=delete' + update_size_param, $(this).serialize(), function (response) {
                            $loading.remove();

                            if (response.status == 'fail') {
                                $dialog.find('.errormsg').html(response.errors.join(' '));
                            } else {
                                if (icon.data('return-url')) {
                                    location.href = icon.data('return-url');
                                } else {
                                    icon.closest('li').remove();
                                    if ($('.item-list li').length) {
                                        if (response.data.total_size !== undefined) {
                                            $('.total-size').show().text(response.data.total_size);
                                        }

                                        if (response.data.total_size_class !== undefined) {
                                            $('.total-size').attr('class', response.data.total_size_class);
                                        }

                                        if (response.data.is_large !== undefined && !response.data.is_large) {
                                            $('#wa-app-logs .indicator').remove();
                                        }
                                    } else {
                                        $('.item-list').hide();
                                        $('.total-size').hide();
                                        $('.total-size').attr('class', 'total-size');
                                        $('#wa-app-logs .indicator').remove();
                                        $('.no-logs-message').show();
                                    }
                                    $dialog.trigger('close');
                                }
                            }
                        }, 'json');

                        return false;
                    },
                    onClose: function () {
                        $(this).remove();
                    }
                });
            });

            //rename file
            $(document).on('click', '.logs-action-rename', function () {
                var $icon = $(this);
                var in_item_list = $(this).parents('.item-list-item').length > 0;

                $('<p><i class="icon16 loading"></i></p>').waDialog({
                    buttons: '<input type="submit" value="' + $.loc['Rename'] + '" class="button blue">'
                        + '&nbsp;<a href="" class="cancel">' + $.loc['cancel'] + '</a>',
                    width:   '700px',
                    height:  '200px',
                    onLoad: function () {
                        var $dialog = $(this);
                        var $submit = $dialog.find('.dialog-buttons :submit');

                        $submit.attr('disabled', true);
                        $.get('?module=dialog&action=rename&path=' + $icon.data('path'), function (html) {
                            var content = $(html);
                            $dialog.find('.loading').parent().replaceWith(content);
                            $dialog.find('[name="name"]').focus();
                            $submit.attr('disabled', false);
                        });
                    },
                    onSubmit: function (dialog) {
                        var $dialog = $(dialog);
                        var $error = $dialog.find('.errormsg');
                        var $loading = $('<i class="icon16 loading middle left-margin"></i>');
                        var $buttons_container = $dialog.find('.dialog-buttons-gradient');

                        $error.empty();
                        $buttons_container.append($loading);

                        $.post('?module=backend&action=rename', $(this).serialize(), function (response) {
                            $loading.remove();

                            if (response.status == 'fail') {
                                $error.html(response.errors.join(' '));
                            } else {
                                if (in_item_list) {
                                    var $list_item = $icon.closest('.item-list-item');
                                    $list_item.find('.rename-part').text(response.data.name);
                                    $list_item.find('.published-marker').remove();
                                    $list_item.find('.item-list-item-open-link').attr('href', response.data.redirect_url);

                                    if (response.data.download_url !== undefined) {
                                        $list_item.find('.item-list-item-download-link').attr('href', response.data.download_url);
                                    }

                                    $icon.data('path', response.data.path);
                                    $list_item.find('.logs-action-delete').data('path', response.data.path);

                                    if (location.search.indexOf('mode=') < 0) {
                                        location.reload();
                                    } else {
                                        $dialog.trigger('close');
                                    }
                                } else {
                                    location.href = response.data.redirect_url;
                                }
                            }
                        }, 'json');
                        return false;
                    },
                    onClose: function () {
                        $(this).remove();
                    }
                });

                return false;
            });

            //reload item list
            $(document).on('click', '.list-update-action', function () {
                var icon = $(this).find('.icon16');
                icon.removeClass('update').addClass('loading');
                $.get(location.href, function (html) {
                    setTimeout(function () {
                        var content = $(html);
                        content.find('.icon16.update').removeClass('update').addClass('yes');
                        $('#wa-app').replaceWith(content);
                        $('.icon16.yes').animate(
                            {opacity: 0},
                            700,
                            function () {
                                $(this).removeClass('yes').addClass('update').css({opacity: 1});
                            }
                        );
                    }, 500);
                });
            });

            //publish dialog
            $(document).on('click', '.logs-action-published', function () {
                var icon = $(this);

                $('<p><i class="icon16 loading"></i></p>').waDialog({
                    buttons: '<input type="submit" value="' + $.loc['Close'] + '" class="button blue cancel">',
                    width:   '700px',
                    height:  '350px',
                    onLoad: function () {
                        var $dialog = $(this);

                        $.get('?module=dialog&action=publishedFile&path=' + icon.data('path'), function (html) {
                            var $content = $(html);
                            $dialog.find('.loading').parent().replaceWith($content);
                            self.publishedIbutton($('.published-status-ibutton'), 'publishedFileUpdateStatus');
                            $dialog.find('.published-url').select();
                        });
                    },
                    onClose: function () {
                        $(this).remove();
                    }
                });
            });

            //phpinfo
            $(document).on('click', '.logs-action-phpinfo', function () {
                $('<p><i class="icon16 loading"></i></p>').waDialog({
                    width: '700px',
                    height: '300px',
                    buttons: '<input type="submit" value="' + $.loc['Close'] + '" class="button blue cancel">',
                    onLoad: function () {
                        var $dialog = $(this);

                        $.get('?module=dialog&action=phpinfo', function (html) {
                            $dialog.find('.loading').parent().replaceWith(html);
                            self.publishedIbutton($('.published-status-ibutton'), 'publishedPhpinfoUpdateStatus');
                            $dialog.find('.published-url').select();

                        });
                    },
                    onClose: function () {
                        $(this).remove();
                    }
                });

                return false;
            });

            //auto-select text in input
            $(document).on('click', '.auto-select', function () {
                $(this).select();
            })

            //prevent users from submitting the form manually
            $(document).on('submit', '.published-password-protection', function () {
                return false;
            });

            //set password
            $(document).on('click', '.published-set-password-link', function () {
                var $link = $(this);
                var $loading = $('<i class="icon16 loading middle left-margin"></i>');
                var $dialog = $link.closest('.dialog');
                var $form = $link.closest('form');
                var $error_message = $dialog.find('.dialog-error-message');
                var $empty_password_container = $form.find('.published-password-empty');
                var $existing_password_container = $form.find('.published-password-exists');

                $loading.insertAfter($link);
                $error_message.empty();

                $.post('?module=dialog&action=' + $form.data('setPasswordAction'), $form.serialize(), function (response) {
                    $loading.remove();

                    if (response.status == 'fail') {
                        $error_message.html(response.errors.join(' '));
                    } else {
                        $form.find('.published-password-value').val(response.data.password);
                        $empty_password_container.slideUp();
                        $existing_password_container.slideDown();
                    }
                });

                return false;
            });

            //reset password
            $(document).on('click', '.published-reset-password-link', function () {
                var $link = $(this);
                var $loading = $('<i class="icon16 loading middle left-margin"></i>');
                var $dialog = $link.closest('.dialog');
                var $form = $link.closest('form');
                var $error_message = $dialog.find('.dialog-error-message');
                var $password = $form.find('.published-password-value');

                $loading.insertAfter($password);
                $error_message.empty();

                $.post('?module=dialog&action=' + $form.data('setPasswordAction'), $form.serialize(), function (response) {
                    $loading.remove();

                    if (response.status == 'fail') {
                        $error_message.html(response.errors.join(' '));
                    } else {
                        $form.find('.published-password-value').val(response.data.password);

                        $('<i class="icon16 yes middle left-margin"></i>')
                        .insertAfter($password)
                        .animate({opacity: 0}, 700);
                    }
                });

                return false;
            });

            //remove password
            $(document).on('click', '.published-remove-password-link', function () {
                var $link = $(this);
                var $loading = $('<i class="icon16 loading middle left-margin"></i>');
                var $dialog = $link.closest('.dialog');
                var $form = $link.closest('form');
                var $error_message = $dialog.find('.dialog-error-message');
                var $empty_password_container = $form.find('.published-password-empty');
                var $existing_password_container = $form.find('.published-password-exists');
                var $password = $form.find('.published-password-value');

                $loading.insertAfter($password);
                $error_message.empty();

                $.post('?module=dialog&action=publishedRemovePassword', $form.serialize(), function (response) {
                    $loading.remove();

                    if (response.status == 'fail') {
                        $error_message.html(response.errors.join(' '));
                    } else {
                        $form.find('.published-password-value').val('');
                        $empty_password_container.slideDown();
                        $existing_password_container.slideUp();
                    }
                });

                return false;
            });

            //show & save settings
            $(document).on('click', '.logs-action-settings', function () {
                var $link = $(this);

                $('<p><i class="icon16 loading"></i></p>'
                + '<p class="error hidden"></p>').waDialog({
                    height: '500px',
                    width: '800px',
                    buttons: '<input type="submit" value="' + $.loc['Save'] + '" class="button blue">'
                        + '&nbsp;<a href="" class="cancel">' + $.loc['cancel'] + '</a>',
                    onLoad: function () {
                        var $dialog = $(this);
                        var $submit = $dialog.find('.dialog-buttons :submit');

                        $submit.attr('disabled', true);
                        $.get('?module=dialog&action=settings', function (html) {
                            $dialog.find('.loading').parent().replaceWith(html);
                            $submit.attr('disabled', false);
                        });
                    },
                    onSubmit: function (dialog) {
                        var $dialog = $(dialog);
                        var $error = $dialog.find('.errormsg');
                        var $loading = $('<i class="icon16 loading left-margin"></i>');

                        $dialog.find('.cancel').after($loading);
                        $dialog.find('.error').addClass('hidden').empty();
                        $error.empty();

                        $.post('?module=dialog&action=settingsSave', $(this).serialize(), function (response) {
                            if (response.status == 'fail') {
                                $loading.remove();
                                $error.html(response.errors.join('<br>'));
                            } else {
                                $dialog.trigger('close');

                                if (location.href.indexOf('path=') > -1 && location.href.indexOf('action=file') > -1) {
                                    var old_hide_data = $link.data('hide-data');

                                    if (old_hide_data !== undefined) {
                                        var new_hide_data = [];

                                        $dialog.find('[name^="settings[hide]"]').each(function () {
                                            var checkbox = $(this);
                                            var matches = checkbox.attr('name').match(/settings\[[^\]]+\]\[([^\]]+)\]\[([^\]]+)\]/);

                                            if (matches[2] == 'backend' && checkbox.is(':checked')) {
                                                new_hide_data.push(matches[1]);
                                            }
                                        });

                                        old_hide_data.sort();
                                        new_hide_data.sort();

                                        $link.data('hide-data', new_hide_data);

                                        if (JSON.stringify(old_hide_data) !== JSON.stringify(new_hide_data)) {
                                            location.reload();
                                        }
                                    }
                                }
                            }
                        }, 'json');
                        return false;
                    },
                    onClose: function () {
                        $(this).remove();
                    }
                });
            });

            //show/hide PHP errors selection setting
            $(document).on('change', '.php_log_setting', function () {
                var $checkbox = $(this);
                var $php_errors = $('.field-php-log-errors');
                var enabled = $checkbox.is(':checked');

                if (enabled) {
                    $php_errors.slideDown();
                } else {
                    $php_errors.slideUp();
                }
            });

            //update PHP errors setting checkboxes
            $(document).on('change', '.field-php-log-errors input[type="checkbox"]', function () {
                var $checkbox = $(this);
                var checked = $checkbox.is(':checked');
                var $container = $checkbox.closest('.value');

                var $e_all = $container.find('[value="E_ALL"]');
                var $e_other = $container.find('[type="checkbox"]').not('[value="E_ALL"]');

                if ($checkbox.is($e_all)) {
                    $e_other.attr('checked', !checked);
                } else {
                    $e_all.attr('checked', !$e_other.filter(':checked').length);
                }
            });
        }
    };

    self.init();

});