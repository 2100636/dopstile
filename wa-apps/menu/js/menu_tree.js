(function($) {

    // private helper methods in closure

    var getId = function(el) {
        var regexp = /^item-(.*?)-handler$/;
        return regexp.test(el.attr('id')) ?
            el.attr('id').replace(regexp, function() {
                return parseInt(arguments[1], 10) || 0;
            }) : 0;
    };

    var getContext = function(el) {
        if (!getId(el)) {
            var p = el.parent(), t = p.next(), u = t.find('ul:first');
        } else {
            var p = el.parents('li:not(.drag-newposition):first'), t = p.find('ul:first'), u = t;
        }
        return {
            parent: p,
            target: t,
            ul: u
        };
    };

    var onCollapse = function(el, func) {
        var context = getContext(el);
        if (context.parent.attr('data-type') == 'item' && !context.parent.hasClass('dynamic')) {
            context.parent.trigger('count_subtree', true);
        }
        el.removeClass('darr').addClass('rarr');
        context.target.hide();
        if (typeof func === 'function') {
            func(el);
        }
    };

    var onExpand = function(el, func) {
        var context = getContext(el);
        if (context.parent.attr('data-type') == 'item') {
            context.parent.trigger('count_subtree', false);
        }
        el.removeClass('rarr').addClass('darr');
        context.target.show();
        if (typeof func === 'function') {
            func(el);
        }
    };

    /**
     * @param context
     * @param {Boolean} status
     */
    var setLoadingIcon = function(context, status) {
        var icon = context.parent.find('.loading:first');
        var counters = context.parent.find('.counters');
        if (status) {
            icon.show();
            counters.hide();
        } else {
            icon.hide();
            counters.show();
        }
    };

    var collapse = function(el, func) {
        onCollapse(el, func);
        $.get('?module=item&action=expand&id=' + getId(el) + '&collapsed=1');
    };

    var expand = function(el, onExpandFunc, afterExpandFunc) {
        if (el.data('loading_content')) {
            return;
        }
        var context = getContext(el);
        if (!context.ul.length) {
            setLoadingIcon(context, true);
        } else {
            onExpand(el, onExpandFunc);
        }

        var loading_content = !context.ul.length;
        el.data('loading_content', loading_content);
        $.get('?module=item&action=expand&id=' + getId(el) + (loading_content ? '&tree=1' : ''),
            function(html) {
                if (loading_content) {
                    if (context.target.length) {
                        context.target.append(html);
                    } else {
                        context.parent.append(html);
                    }
                    setLoadingIcon(context, false);
                    onExpand(el, onExpandFunc);
                    el.data('loading_content', false);
                    if (typeof afterExpandFunc === 'function') {
                        afterExpandFunc();
                    }
                } else {
                    if (typeof afterExpandFunc === 'function') {
                        afterExpandFunc();
                    }
                }
            }
        );
    };

    $.menu_tree = {

        init: function() {
            var $tree = $('#menu-tree');

            $tree.off('click', '.collapse-handler-ajax').on('click', '.collapse-handler-ajax', function() {
                var self = $(this);
                if (self.hasClass('darr')) {
                    collapse(self);
                } else {
                    expand(self);
                }
            }).off('click', 'a').on('click', 'a', function () {
                $tree.find('a.selected').removeClass('selected');
                $(this).addClass('selected');
            });


            $.dragndrop.init().bind('move_list', function (options) {
                if (!options.type) {
                    if (typeof options.error === 'function') {
                        options.error('Unknown list type');
                    }
                    return;
                }
                var data = {
                    id: options.id,
                    type: options.type,
                    parent_id: options.parent_id || $tree.data('menu_id')
                };
                if (options.before_id) {
                    data.before_id = options.before_id;
                }

                $.post('?module=item&action=move', data, function (r) {

                }, 'json');
            });

            $tree.off('click', '.m-new-list').on('click', '.m-new-list', function (e) {
                e.preventDefault();
                var self = $(this);
                var id = self.attr('id');
                var parent_id = $tree.data('menu_id');
                var type;
                if (id) {
                    type = id.replace('m-new-', '');
                } else {
                    var splited = self.parents('li.dr:first').attr('id').split('-');
                    type = splited[0];
                    parent_id = splited[1];
                }
                $('#menu-item').html('<i class="icon16 loading"></i>').load('?action=item&parent_id='+parent_id);

                return false;
            });

            $tree.off('add', 'ul').on('add', 'ul',
                /**
                 * @param {Object} e jquery event
                 * @param {Object} item describes inserting item. Will be passed to template
                 * @param {String} type 'category', 'set'
                 * @param {Boolean} replace if item exists already replace it or not?
                 */
                function (e, item, type, replace) {
                    var self = $(this), parent = self.parents('.s-collection-list:first');
                    var tmp = $('<ul></ul>');
                    tmp.append(
                        '<li class="dr" id="item-' + item.id + '" data-type="item">' +
                        '    <span class="counters"></span>' +
                        '    <a href="#/menu/' + $tree.data('menu_id') + '/' + item.id + '">' +
                        '         <i class="icon16 ' + item.icon + '"></i><span class="name">' + item.name + '</span>' +
                        '		 <span class="count"><i class="icon10 add m-new-list"></i></span>' +
                        '    </a>' +
                        '</li>' +
                        '<li class="drag-newposition" data-type="item"></li>'
                    );

                    var new_item = tmp.children(':not(.drag-newposition):first');
                    var id = new_item.attr('id');
                    var old_item = self.find('#' + id);
                    var children = tmp.children();

                    if (old_item.length) {
                        if (replace) {
                            old_item.replaceWith(new_item);
                        }
                    } else {
                        self.append(children).show();
                    }

                    children.each(function () {
                        var item = $(this);
                        if (item.hasClass('dr')) {
                            item.find('a').mouseover();
                        } else {
                            item.mouseover();
                        }
                    });
                    self.find('.drag-newposition').css({
                        height: '2px'
                    }).removeClass('dragging');

                    parent.find('.s-empty-list').hide();

                    tmp.remove();

                    return false;
                }
            );
        },

        collapse: function(handler, func) {
            handler = $(handler);
            if (handler.hasClass('darr')) {
                collapse(handler, func);
            } else if (typeof func === 'function') {
                func(handler);
            }
        },

        expand: function(handler, onExpand, afterExpand) {
            handler = $(handler);
            if (handler.hasClass('rarr')) {
                expand(handler, onExpand, afterExpand);
            } else {
                if (typeof onExpand === 'function') {
                    onExpand(handler);
                }
                if (typeof afterExpand === 'function') {
                    afterExpand(handler);
                }
            }
        },

        isCollapsed: function(handler) {
            return $(handler).hasClass('rarr');
        },

        setExpanded: function(item_id) {
            $.get('?module=item&action=expand&id=' + item_id);
        },

        setCollapsed: function(item_id) {
            $.get('?module=item&action=expand&id=' + item_id + '&collapsed=1');
        },

        getHandlerByItemId: function(item_id) {
            var handler = $();
            item_id = parseInt(item_id, 10) || 0;
            if (!item_id) {
                //handler = $('#m-item-list-handler');
            } else {
                handler = $('#item-' + item_id + '-handler');
            }
            return handler;
        },


        onCreate: function (new_item, type) {
            var $tree = $('#menu-tree');
            var list = $tree.find('ul:first');
            if (!list.length) {
                $tree.prepend(
                    '<ul class="menu-v with-icons"><li class="drag-newposition" data-type="' + type + '"></li></ul>'
                );
                $tree.find('.drag-newposition').mouseover();  // init droppable
                list = $tree.find('ul:first');
            }
            var parent_id = parseInt(new_item.parent_id, 10) || 0;
            var handler = $.menu_tree.getHandlerByItemId(parent_id);

            var add = function () {
                if (parent_id != $tree.data('menu_id')) {
                    var parent = list.find('#' + type + '-' + new_item.parent_id);

                    if (!parent.find('>.collapse-handler-ajax').length) {
                        parent.append('<ul class="menu-v with-icons dr"><li class="drag-newposition" data-type="' + type + '"></li></ul>');
                        parent.find('.drag-newposition').mouseover(); // init droppable
                        parent.find('>a').before(
                            '<i class="icon16 darr overhanging collapse-handler-ajax" id="' +
                            type + '-' + parent_id + '-handler' +
                            '"></i>'
                        );
                        $.menu_tree.setExpanded(parent_id);
                    }
                    list = parent.find('ul:first');
                }
                list.trigger('add', [new_item, type]);
            };


            $.menu_tree.expand(handler, function () {
                add();
            });

        }
    };
})(jQuery);
