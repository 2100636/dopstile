/*********
 * This file is part of Webasyst menu app
 *
 * Licensed under the terms of Webasyst License
 * https://www.webasyst.com/legal/terms/#eula
 *
 * @modified Wed Oct 05 2016 17:05:19
 * @link http://hardmandev.com/
 * @author Hardman LLC
 * @copyright 2016 Hardman LLC
 * @package webasyst-menu
 *********/
(function ($) {
    "use strict";
    $.storage = new $.store();
    $.wa.menu = {
        options: {
            lang: 'en'

        },
        init: function (options) {
            if ("undefined" !== typeof($.History)) {
                $.History.bind(function () {
                    $.wa.menu.dispatch();
                });
            }
            this.options = $.extend(this.options, options);
            var hash = window.location.hash;
            if (hash === '#/' || !hash) {
                hash = $.storage.get('menu/hash');
                if (hash && hash != null) {
                    $.wa.setHash('#/' + hash);
                } else {
                    this.dispatch();
                }
            } else {
                $.wa.setHash(hash);
            }

            $(window).bind('wa.dispatched', function () {
                // Highlight current item in sidebar, if exists
                $.wa.menu.highlightSidebar();

                // Remove all non-persistent dialogs
                $('.dialog:not(.persistent)').empty().remove();
            });

            // Set up AJAX error handler
            $.wa.errorHandler = function () {
            };
            $(document).ajaxError(function (e, xhr, settings, exception) {

                // Never save pages causing an error as last hashes
                $.storage.del('menu/hash');
                $.wa.menu.stopDispatch(1);
                window.location.hash = '';

                // Show error in a nice safe iframe
                if (xhr.responseText) {
                    // Close all existing dialogs
                    $('.dialog:visible').trigger('close').remove();
                    $('<h1>'+$.wa.locale['Error']+'</h1><div>'+xhr.responseText+'</div>').waDialog({
                        'buttons': '<input type="button" value="'+$.wa.locale['Close']+'" class="button cancel" />',
                        onSubmit: function (d) {
                            d.trigger('close');
                            return false;
                        }
                    });
                }
            });


            // development hotkeys for redispatch and sidebar reloading
            $(document).keypress(function (e) {
                if ((e.which == 10 || e.which == 13) && e.shiftKey) {
                    $('#wa-app').find('.sidebar .icon16').first().attr('class', 'icon16 loading');
                    $.wa.menu.reloadSidebar();
                }
                if ((e.which == 10 || e.which == 13) && e.ctrlKey) {
                    $.wa.menu.redispatch();
                }
            });

            this.bindEvents();
        },

        // if this is > 0 then this.dispatch() decrements it and ignores a call
        skipDispatch: 0,

        /** Cancel the next n automatic dispatches when window.location.hash changes */
        stopDispatch: function (n) {
            this.skipDispatch = n;
        },

        /** Force reload current hash-based 'page'. */
        redispatch: function () {
            this.currentHash = null;
            this.dispatch();
        },

        // last hash processed by this.dispatch()
        currentHash: null,

        /**
         * Called automatically when window.location.hash changes. Should not be called directly.
         * Call a corresponding handler by concatenating leading non-int parts of hash,
         * e.g. for #/aaa/bbb/ccc/111/dd/12/ee/ff
         * a method $.wa.controller.AaaBbbCccAction(['111', 'dd', '12', 'ee', 'ff']) will be called.
         */
        dispatch: function (hash) {
            if (this.skipDispatch > 0) {
                this.skipDispatch--;
                this.currentHash = null;
                return false;
            }
            if (hash == undefined) {
                hash = this.getHash();
            } else {
                hash = this.cleanHash(hash);
            }

            if (this.currentHash == hash) {
                return;
            }
            var old_hash = this.currentHash;
            this.currentHash = hash;

            var e = new $.Event('wa.before_dispatched');
            $(window).trigger(e);
            if (e.isDefaultPrevented()) {
                this.currentHash = old_hash;
                window.location.hash = old_hash;
                return false;
            }

            hash = hash.replace(/^[^#]*#\/*/, '');
            /* */
            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    var i = 0;
                    for (i; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h && h.indexOf('=') == -1 && actionName != 'plugins') {
                                actionName += h.substr(0, 1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }

                    var attr = hash.slice(attrMarker);

                    if (this[actionName + 'Action']) {
                        this[actionName + 'Action'].apply(this, attr);
                        // save last page to return to by default later
                        $.storage.set('menu/hash', hash.join('/'));
                    } else {
                        if (console) {
                            console.log('Invalid action name:', actionName + 'Action');
                        }
                    }
                } else {
                    this.defaultAction();
                }
            } else {
                this.defaultAction();
            }

            $(window).trigger('wa.dispatched');
        },

        //
        // Actions called from this.dispatch()
        //

        defaultAction: function () {
            window.location.href = $('.m-sidebar').find('a:first').prop('href');
        },

        menuAction: function (id, params) {
            if(params) {
                var $m = $('[data-menu-id]');
                if($m.length && ($m.data('menu-id') === id)) {
                    $('#menu-item').html('<i class="icon16 loading"></i>').load('?action=item&id=' + params);
                } else {
                    this.load('?action=menu&id=' + id, function () {
                        $('#menu-item').html('<i class="icon16 loading"></i>').load('?action=item&id=' + params);
                    });
                }
            } else {
                this.load('?action=menu&id=' + id);
            }
        },

        settingsAction: function () {
            this.load("?module=settings", function(){
                var $f = $('#settings-form');
                $('input,select', $f).change(function(){
                    $('input[type="submit"]', $f).removeClass('green').addClass('yellow');
                });
                $('.ibutton', $f).iButton();
                $f.submit(function (e) {
                    var $s = $f.find('.status');
                    e.preventDefault();
                    $s.html('<i class="icon16 loading"></i>').show();
                    $.post($f.prop('action'), $f.serialize(), function(resp){
                        $s.html(resp.status).delay(3000).fadeOut();
                        $('input[type="submit"]', $f).removeClass('yellow').addClass('green');
                    }, 'json');
                })
            });
        },


        pluginsAction: function (params) {
            if ($('#wa-plugins-container').length) {
                $.plugins.dispatch(params);
            } else {
                this.load('?module=plugins', function () {
                    $.plugins.dispatch(params);
                });
            }
        },

        infoAction: function (params) {
            this.load('?action=info', function () {
            });
        },

        setTitle: function (title) {
            var $h1 = $('#wa-app').find('.content h1:first');
            if ((typeof title === 'undefined' || !title.length) && $h1.length > 0) {
                var $h1_input = $h1.find('input');
                if ($h1_input.length > 0) {
                    title = $h1_input.val().trim();
                }
                else {
                    title = $h1.contents().filter(function () {
                        return this.nodeType == 3 && this.nodeValue.trim().length > 0;
                    })[0].nodeValue.trim()
                }
            }

            if (title) {
                $('title').html(title + " &mdash; Webasyst");
            }
        },



        /** Current hash. No URI decoding is performed. */
        getHash: function () {
            return this.cleanHash();
        },

        /** Make sure hash has a # in the begining and exactly one / at the end.
         * For empty hashes (including #, #/, #// etc.) return an empty string.
         * Otherwise, return the cleaned hash.
         * When hash is not specified, current hash is used. No URI decoding is performed. */
        cleanHash: function (hash) {
            if (typeof hash == 'undefined') {
                // cross-browser way to get current hash as is, with no URI decoding
                hash = window.location.toString().split('#')[1] || '';
            }

            if (!hash) {
                return '';
            } else if (!hash.length) {
                hash = '' + hash;
            }
            while (hash.length > 0 && hash[hash.length - 1] === '/') {
                hash = hash.substr(0, hash.length - 1);
            }
            hash += '/';

            if (hash[0] != '#') {
                if (hash[0] != '/') {
                    hash = '/' + hash;
                }
                hash = '#' + hash;
            } else if (hash[1] && hash[1] != '/') {
                hash = '#/' + hash.substr(1);
            }

            if (hash == '#/') {
                return '';
            }

            return hash;
        },


        /** Helper to initialize iButtons */
        iButton: function ($checkboxes, options) {
            options = options || {};
            return $checkboxes.each(function () {
                var cb = $(this);
                var id = cb.attr('id');
                if (!id) {
                    do {
                        id = 'cb' + Date.now() + '-' + (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
                    } while (document.getElementById(id));
                    cb.attr('id', id);
                }
                if (!options.inside_labels) {
                    $($.parseHTML(
                        '<ul class="menu-h ibutton-wrapper">' +
                        '<li><label class="gray" for="' + id + '">' + (options.labelOff || '') + '</label></li>' +
                        '<li class="ibutton-li"></li>' +
                        '<li><label for="' + id + '">' + (options.labelOn || '') + '</label></li>' +
                        '</ul>'
                    )).insertAfter(cb).find('.ibutton-li').append(cb);
                }
            }).iButton($.extend({
                className: 'mini'
            }, options, options.inside_labels ? {} : {
                labelOn: "",
                labelOff: ""
            }));
        },


        /** Shows a confirmation dialog when user tries to navigate away from current page or current hash. */
        confirmLeave: function (is_relevant, warning_message, confirm_question) {
            var h, h2, $window = $(window);

            $window.on('beforeunload', h = function (e) {
                if (is_relevant()) {
                    return warning_message;
                }
            });

            $window.on('wa_before_dispatched', h2 = function (e) {
                if (!is_relevant()) {
                    $window.off('unload', h).off('wa_before_dispatched', h2);
                    return;
                }
                if (!confirm(warning_message + " " + confirm_question)) {
                    e.preventDefault();
                }
            });
        },

        /** Load HTML content from url and put it into main #content div */
        load: function (url, callback, params) {
            this.showLoading();
            params = params || null;

            var r = Math.random();
            $.wa.menu.random = r;
            $.get(url, params, function (result) {
                if ($.wa.menu.random != r) {
                    // too late: user clicked something else.
                    return;
                }
                $("#m-content").addClass('shadowed').html(result);
                $.wa.menu.hideLoading();
                $('html, body').animate({scrollTop: 0}, 200);
                if (callback) {
                    callback.call(this);
                }
            });
        },

        /** Show loading indicator in the header */
        showLoading: function () {
            var h1 = $('h1:visible').first();
            if (h1.size() <= 0) {
                $('#c-corm-content').find('.block').first().prepend('<i class="icon16 loading"></i>');
                return;
            }
            if (h1.find('.loading').show().size() > 0) {
                return;
            }
            h1.append('<i class="icon16 loading"></i>');
        },

        /** Hide all loading indicators in h1 headers */
        hideLoading: function () {
            $('h1 .loading').hide();
        },


        /** Add .selected css class to li with <a> whose href attribute matches current hash.
         * If no such <a> found, then the first partial match is highlighted.
         * Hashes are compared after this.cleanHash() applied to them. */
        highlightSidebar: function (sidebar) {
            var currentHash = this.cleanHash(location.hash);
            var partialMatch = false;
            var partialMatchLength = 2;
            var match = false;
            var index = 0;

            if (!sidebar) {
                sidebar = $('#wa-app > .sidebar');
            }

            sidebar.find('li a').each(function (k, v) {
                v = $(v);
                index = k;
                if (!v.attr('href')) {
                    return;
                }
                var h = $.wa.menu.cleanHash(v.attr('href'));

                // Perfect match?
                if (h == currentHash) {
                    match = v;
                    return false;
                }

                // Partial match? (e.g. for urls that differ in paging only)
                if (h.length > partialMatchLength && currentHash.substr(0, h.length) === h) {
                    partialMatch = v;
                    partialMatchLength = h.length;
                }
            });

            if (!match && partialMatch) {
                match = partialMatch;
            }

            if (match) {
                sidebar.find('.selected').removeClass('selected');

                // Only highlight items that are outside of dropdown menus
                if (match.parents('ul.dropdown').size() <= 0) {
                    var p = match.parent();
                    while (p.size() > 0 && p[0].tagName.toLowerCase() != 'li') {
                        p = p.parent();
                    }
                    if (p.size() > 0) {
                        p.addClass('selected');
                    }
                }
            }
        },

        bindEvents: function () {
            var $content = $('#m-content');

            $content.on('submit', 'form[data-ajax]', function (e) {
                e.preventDefault();
                var $this = $(this),
                    $status = $this.find('[data-status]'),
                    method = $this.prop('method') == 'post' ? 'post' : 'get';

                $status.show().html('<i class="icon16 loading"></i>');
                $this.prop('disabled', true);

                $[method]($this.prop('action'), $this.serialize(), function (resp) {
                    $this.prop('disabled', false);
                    if(resp.status == 'ok') {
                        $status.html('<em class="status-ok"><i class="icon16 status-green-tiny"></i> '+$.wa.locale['Saved']+'</em>');

                        var s = $this.data('success');
                        if(s && (typeof $.wa.menu[s] == 'function')) {
                            $.wa.menu[s](resp.data, $this);
                        }
                    } else {
                        var err = $.isArray(resp.errors) ? resp.errors.join(', ') : resp.errors;
                        $status.html('<em class="status-error"><i class="icon16 status-red-tiny"></i> '+err+'</em>');
                    }
                    $status.delay(3000).fadeOut();
                }, 'json').fail(function () {
                    $this.prop('disabled', false);
                    alert($.wa.locale['An error occurred.']);
                })

            });

            $content.on('click', 'a[data-ajax]', function (e) {
                e.preventDefault();

                var $this = $(this),
                    conf = $this.data('confirm');

                if(conf && confirm(conf)) {
                    $.get($this.prop('href'), function (resp) {
                        if(resp.status == 'ok') {

                            var s = $this.data('success');
                            if(s && (typeof $.wa.menu[s] == 'function')) {
                                $.wa.menu[s](resp.data, $this);
                            }
                        } else {
                            var err = $.isArray(resp.errors) ? resp.errors.join('\n') : resp.errors;
                            alert($.wa.locale['An error occurred.']+'\n'+err);
                        }
                    }, 'json').fail(function () {
                        alert($.wa.locale['An error occurred.']);
                    })
                }

            });

            $('#wa-app').on('click', '[data-menu-edit]', function (e) {
                e.preventDefault();
                var $t = $(this),
                    id = $t.closest('[data-menu-id]').data('menu-id');
                $('.dialog').remove();
                $.get('?module=menu&action=add&id='+id, function (r) {
                    var delete_button = id !== 'new' ? '<a href="#" data-menu-delete data-menu-id="'+id+'">' +
                        '<i class="icon16 delete"></i>'+$.wa.locale['delete']+'</a>' : '';
                    $(r).waDialog({
                        'buttons':
                            delete_button +
                            '<input class="button green" type="submit" value="'+$.wa.locale['Save']+'" />' +
                            ' '+$.wa.locale['or']+' ' +
                            '<a href="#" class="cancel">'+$.wa.locale['cancel']+'</a>',
                        onLoad:function (d) {
                            $('.m-menu-icon').find('a').click(function (e) {
                                e.preventDefault();
                                var icon = $(this).find('.icon16').prop('class').replace('icon16 ', '');
                                $(this).parent().addClass('selected').siblings().removeClass('selected');
                                $(this).closest('.value').find('[name="menu[type]"]').val(icon);
                            });
                            var icon = $('[name="menu[type]"]').val()
                            $('.m-menu-icon .icon16.'+icon).closest('.m-menu-icon').addClass('selected');

                            $(this).find('[data-menu-delete]').click(function (e) {
                                e.preventDefault();
                                var $del = $(this),
                                    $icon = $del.find('.icon16'),
                                    id = $del.data('menu-id'),
                                    msg = 'This will permanently delete menu and all its items without the ability ' +
                                        'to recover. Are you sure?';

                                if($icon.hasClass('loading')) {
                                    return;
                                }


                                if(confirm($.wa.locale[msg])) {
                                    $icon.removeClass('delete').addClass('loading');
                                    $.get('?module=menu&action=delete&id='+id, function () {
                                        var $s = $('.m-sidebar'), $a;

                                        $s.find('[data-menu-id="'+id+'"]').remove();

                                        $a = $s.find('a:first');

                                        if($a.parent().data('menu-id') === "new") {
                                            $a = $s.find('[href="#/settings/"]');
                                        }
                                        window.location.href = $a.prop('href');
                                    });
                                }
                            })

                        },
                        onSubmit: function (d) {
                            $.post('?module=menu&action=save', $(this).serialize(), function (r) {
                                $.wa.menu.menuAddCallback(r, $(d).find('[name="menu[id]"]').val() === 'new');
                                d.trigger('close');
                            }, 'json');
                            return false;
                        }
                    });
                })
            });

            var $search_w = $('#menu-search-container'),
                $menu_w = $('#menu-sidebar-container');
            if($search_w.length) {
                $search_w.find('input').keyup(function (e) {
                    var v = $(this).val(), r;
                    if(v) {
                        r = new RegExp(v, 'i');
                        $menu_w.find('li').each(function (i, li) {
                            var $li = $(li);
                            if(r.test($li.text())) {
                                $li.show();
                            } else {
                                $li.hide();
                            }
                        })
                    } else {
                        $menu_w.find('li').show();
                    }
                });
                $(window).resize(function () {
                    var h = $(this).height() - 390;
                    if(h < 200) h = 200;

                    $menu_w.css('max-height', h);
                })
            }
        },

        menuItemSuccessCallback: function (data, $form) {
            var old_id = $form.find('[name="item[id]"]').val();
            if(old_id === 'new') {
                var menu_id = $('#m-content').find('h1[data-menu-id]').data('menu-id');
                window.location.href='#/menu/'+menu_id+'/'+data.id;
                return;
            }
            if(data && data.id) {
                $form.find('[name="item[id]"]').val(data.id);
                var $w = $("#item-"+data.id);
                if(data.status) {
                    $w.find('>a').removeClass('gray')
                } else {
                    $w.find('>a').addClass('gray')
                }
                $w.find('>a>.icon16').attr('class', 'icon16 '+data.icon);
                $w.find('>a>.name').text(data.name);
            }
        },

        menuAddCallback: function (r, is_new) {
            if(r.status === 'ok') {
                var $w = $('[data-menu-id="'+r.data.id+'"]');
                $w.find('[data-menu-icon]').prop('class', 'icon16 '+r.data.type);
                $w.find('[data-menu-name]').text(r.data.name);

                if(is_new) {
                    var $li = $('<li/>')
                        .attr('data-menu-id', r.data.id).append(
                            $('<a/>')
                                .prop('href', '#/menu/'+r.data.id+'/')
                                .addClass('bold')
                                .append(
                                    $('<i/>')
                                        .addClass('icon16').addClass(r.data.type)
                                        .attr('data-menu-icon', true)
                                )
                                .append(
                                    $('<span/>').attr('data-menu-name', true).text(r.data.name)
                                )
                        )
                    ;
                    $li.insertBefore($('[data-menu-id="new"]'));
                    window.location.href = $li.find('a').prop('href');
                }
            } else {
                alert(r.errors.join(','));
            }
        }


    };
})(jQuery);