(function($) {

    $.fn.liveDraggable = function (opts) {
        this.each(function(i,el) {
            var self = $(this);
            if (self.data('init_draggable')) {
                self.die("mouseover", self.data('init_draggable'));
            }
        });
        this.die("mouseover").live("mouseover", function() {
            var self = $(this);
            if (!self.data("init_draggable")) {
                self.data("init_draggable", arguments.callee).draggable(opts);
            }
        });
    };
    $.fn.liveDroppable = function (opts) {
        this.each(function(i,el) {
            var self = $(this);
            if (self.data('init_droppable')) {
                self.die("mouseover", self.data('init_droppable'));
            }
        });
        var init = function() {
            var self = $(this);
            if (!self.data("init_droppable")) {
                self.data("init_droppable", arguments.callee).droppable(opts);
                self.mouseover();
            }
        };
        init.call(this);
        this.die("mouseover", init).live("mouseover", init);
    };

    $.dragndrop = {
        handlers: {},
        options: {},
        init: function(options) {
            this.options = options;
            this.initDragCollections();
            this.initDropCollections();
            return this;
        },

        bind: function(event, handler) {
            this.handlers[event] = handler;
            return this;
        },

        trigger: function(event) {
            if (typeof this.handlers[event] === 'function') {
                return this.handlers[event].apply(this, Array.prototype.slice.call(arguments, 1));
            }
        },

        initDragCollections: function() {
            var containment = $('#m-content .sidebar:first');
            var containment_pos = containment.position();
            var containment_metrics = { width: containment.width(), height: containment.height() };

            $(".s-collection-list li.dr").liveDraggable({
                containment: [
                      containment_pos.left,
                      containment_pos.top,
                      containment_pos.left + containment_metrics.width * 1.25,
                      containment_pos.top + containment_metrics.height * 1.25
                ],
                refreshPositions: true,
                revert: 'invalid',
                helper: function() {
                    var self = $(this);
                    var parent = self.parents('.s-collection-list:first').find('ul:first');
                    var clone = self.clone().addClass('ui-draggable dr-helper').css({
                        position: 'absolute'
                    }).prependTo(parent);
                    clone.find('a:first').append('<i class="icon10 no-bw" style="margin-left: 0; margin-right: 0; display:none;"></i>');
                    return clone;
                },
                cursor: 'move',
                cursorAt: { left: 5 },
                opacity: 0.75,
                zIndex: 9999,
                distance: 5,
                start: function(event, ui) {
                    document.ondragstart = function() {
                        return false;
                    };
                },
                stop: function() {
                    document.ondragstart = null;
                }
            });
        },

        initDropCollections: function() {
            this.initDropBetweenCollections();
            this.initDropInsideCollections();
        },

        initDropBetweenCollections: function() {
            $('.s-collection-list li.drag-newposition').liveDroppable({
                greedy: true,
                tolerance: 'pointer',
                over: function(event, ui) {
                    var self = $(this);
                    if (ui.draggable.attr('data-type') != self.attr('data-type')) {
                        return false;
                    }
                    self.addClass('active').parent().parent().addClass('drag-active');
                },
                out: function(event, ui) {
                    var self = $(this);
                    if (ui.draggable.attr('data-type') != self.attr('data-type')) {
                        return false;
                    }
                    self.removeClass('active').parent().parent().removeClass('drag-active');
                },
                deactivate: function(event, ui) {
                    var self = $(this);
                    if (ui.draggable.attr('data-type') != self.attr('data-type')) {
                        return false;
                    }
                    if (self.is(':animated') || self.hasClass('dragging')) {
                        self.stop().animate({ height: '2px' }, 300, null,
                                function() {
                                    self.removeClass('dragging');
                                }
                        );
                    }
                    self.removeClass('active').parent().parent().removeClass('drag-active');
                },
                drop: function(event, ui) {
                    var self = $(this);
                    var dr = ui.draggable;
                    var type = dr.attr('data-type');
                    if (type != self.attr('data-type')) {
                        return false;
                    }

                    var getRawId = function(id) {
                        var parts = id.split('-');
                        parts.shift();
                        return parts.join('-');
                    };


                    var id = getRawId(dr.attr('id'));
                    var prev = self.prev('li');
                    var sep  = dr.next();
                    var home = dr.prev();

                    if (prev.length && prev.attr('id') == 'item-'+id && !prev.hasClass('dr-helper')) {
                        return false;
                    }
                    if (this == dr.next().get(0)) {
                        return false;
                    }

                    var parent_list = dr.parent('ul');
                    var li_count = parent_list.children('li.dr[id!=item-'+id+']').length;
                    var old_parent_id = parseInt(getRawId(parent_list.parent().attr('id')), 10) || 0;

                    self.after(sep).after(dr);

                    var parent = dr.parent().parent();
                    if (parent.is('li.dr') || parent.is('.s-collection-list')) {
                        var parent_id = 0;
                        if (!parent.is('.s-collection-list')) {
                            parent_id = parseInt(getRawId(parent.attr('id')), 10) || 0;
                        }
                        var next = dr.nextAll('li.dr:first');
                        var before_id = null;
                        if (next.length) {
                            before_id = getRawId(next.attr('id')) || null;
                        }

                        if (!li_count && old_parent_id !== parent_id) {
                            parent_list.parent('li').children('i').hide();
                            parent_list.hide();
                        }

                        $.dragndrop.trigger('move_list', {
                            id: id, type: type, before_id: before_id, parent_id: parent_id,
                            success: function(r) {
                                if (!li_count && old_parent_id !== parent_id) {
                                    parent_list.parent('li').children('i').remove();
                                    parent_list.remove();
                                    $.categories_tree.setCollapsed(old_parent_id);
                                }
                            },
                            error: function(r) {
                                if (r && console) {
                                    console.log(typeof r.errors !== 'undefined' ? r.errors : r);
                                }
                                // restore
                                home.after(dr.next()).after(dr);
                                if (!li_count) {
                                    parent_list.parent('li').children('i').show();
                                    parent_list.show();
                                }
                            }
                        });
                    }
                }
            });
        },

        initDropInsideCollections: function() {
            $('.s-collection-list li.dr a').liveDroppable({
                tolerance: 'pointer',
                greedy: true,
                out: function(event, ui) {
                    var dr = ui.draggable;
                    var self = $(this).parent();
                    if (!dr.hasClass('product') && self.attr('data-type') != dr.attr('data-type')) {
                        return false;
                    }
                    if (dr.hasClass('product')) {
                        ui.helper.find('span').show().end().find('i').hide();       // show 'circle'-icon
                    }
                    self.removeClass('drag-newparent');
                },
                over: function(event, ui) {
                    var dr = ui.draggable;
                    var self = $(this).parent();
                    var type = dr.attr('data-type');
                    if (!dr.hasClass('product') && type != self.attr('data-type')) {
                        return false;
                    }
                    if (type != 'set') {
                        self.addClass('drag-newparent');
                    }
                    if (dr.hasClass('product')) {
                        if (self.hasClass('dynamic')) {
                            ui.helper.find('span').hide().end().find('i').show();   // show 'cross'-icon
                        } else {
                            ui.helper.find('span').show().end().find('i').hide();   // show 'circle'-icon
                        }
                        return false;
                    }

                    if (!dr.hasClass('dynamic') && self.hasClass('dynamic')) {
                        ui.helper.find('i.no-bw').show();
                        return false;
                    } else {
                        ui.helper.find('i.no-bw').hide();
                    }

                    var drSelector = '.dr[id!="'+dr.attr('id')+'"]';
                    var nearby = $();

                    // helper to widen all spaces below the current li and above next li (which may be on another tree level, but not inside current)
                    var addBelow = function(nearby, current) {
                        if (!current.length) {
                            return nearby;
                        }
                        nearby = nearby.add(current.nextUntil(drSelector).filter('li.drag-newposition'));
                        if (current.nextAll(drSelector).length > 0) {
                            return nearby;
                        }
                        return arguments.callee(nearby, current.parent().closest('li'));
                    };

                    // widen all spaces above the current li and below the prev li (which may be on another tree level)
                    var above = self.prevAll(drSelector+':first');
                    if(above.length > 0) {
                        var d = above.find(drSelector);
                        if (d.length > 0) {
                            nearby = addBelow(nearby, d.last());
                        } else {
                            nearby = addBelow(nearby, above);
                        }
                    } else {
                        nearby = nearby.add(self.prevUntil(drSelector).filter('li.drag-newposition'));
                    }

                    // widen all spaces below the current li and above the next li (which may be on another tree level)
                    if (self.children('ul').children(drSelector).length > 0) {
                        nearby = nearby.add(self.children('ul').children('li.drag-newposition:first'));
                    } else {
                        nearby = addBelow(nearby, self);
                    }

                    var old = $('.drag-newposition:animated, .drag-newposition.dragging').not(nearby);

                    old.stop().animate({height: '2px'}, 300, null, function() {
                        old.removeClass('dragging');
                    });
                    nearby.stop().animate({height: '10px'}, 300, null, function() {
                        nearby.addClass('dragging');
                    });
                },
                drop: function(event, ui) {
                    var dr = ui.draggable;
                    var self = $(this).parent();
                    var type = dr.attr('data-type');
                    if (!dr.hasClass('product')) {
                        if (type == 'set') {
                            return false;
                        }
                        if (self.attr('id') == dr.attr('id')) {
                            return false;
                        }
                        if (type != self.attr('data-type')) {
                            return false;
                        }
                    }
                    self.removeClass('drag-newparent');

                    // coping product to item section
                    if (dr.hasClass('product')) {
                        if (self.hasClass('dynamic')) {
                            return false;
                        }
                        var product_list = $('#product-list');

                        var data = {};
                        if (product_list.find('.s-select-all').is(':checked')) {
                            data.whole_list = true;
                        } else {
                            var products = product_list.find('.product.selected');
                            data.product_ids = products.map(function() {
                                return $(this).attr('data-product-id');
                            }).toArray();
                        }

                        $.dragndrop.trigger('add_to_list', $.extend(data, {
                            collection_param: self.attr('id').replace('-', '_id='),
                            success: function(r) {
                                if (data.whole_list) {
                                    product_list.find('.s-select-all').trigger('select', false);
                                } else if (data.product_ids && data.product_ids.length) {
                                    products.trigger('select', false);
                                }
                                if (r.data) {
                                    self.find('>.counters .count:not(.subtree)').text(r.data.count);
                                    self.find('>.counters .subtree').text(r.data.total_count);
                                }
                            }
                        }));
                        return false;
                    } else {
                        if (!dr.hasClass('dynamic') && self.hasClass('dynamic')) {
                            return false;
                        }
                    }

                    // sorting categories
                    var id = dr.attr('id').split('-')[1];
                    if (self.attr('id') == 'item-'+id) {
                        return false;
                    }

                    if (dr.hasClass('product')) {
                        var selected = $('#product-list').find('.product.selected');
                        selected.trigger('select', false);
                        return false;
                    }

                    var parent_id = parseInt(self.attr('id').split('-')[1], 10) || 0;

                    var list = null;
                    var sep  = dr.next();
                    var home = dr.prev();

                    // for tracking situation when list acceptor has no children
                    // and inserted item will be first
                    var first_child = false;

                    if (self.hasClass('drag-newposition')) {
                        list = self.parent('ul');
                    } else {
                        // has loaded (via ajax) children items
                        if (self.children('ul').length) {
                            list =  self.children('ul');
                        } else if (!self.find('>i.collapse-handler-ajax').length) { // no children

                            list = $(
                                '<ul class="menu-v with-icons dr unapproved">' +
                                    '<li class="drag-newposition unapproved" data-type="'+type+'"></li>' +
                                '</ul>'
                            ).appendTo(self);
                            list.find('.drag-newposition').mouseover(); // init droppable
                            $('<i class="icon16 darr overhanging collapse-handler-ajax unapproved" id="' +
                                    type + '-' + parent_id + '-handler' +
                                '"></i>').insertBefore(self.children('a'));

                            first_child = true;
                        }
                    }

                    var parent_list = dr.parent('ul');
                    var li_count = parent_list.children('li.dr[id!=item-'+id+']').length;
                    var old_parent_id = parseInt(parent_list.parent().attr('id').split('-')[1], 10) || 0;

                    if (list) {
                        list.append(dr).append(sep);
                    }

                    if (!li_count && old_parent_id !== parent_id) {
                        parent_list.parent('li').children('i').hide();
                        parent_list.hide();
                    }

                    var parent = self;
                    if (parent.is('li.dr')) {
                        $.dragndrop.trigger('move_list', {
                            id: id, type: type, parent_id: parent_id,
                            success: function(r) {
                                if (!li_count && old_parent_id !== parent_id) {
                                    parent_list.parent('li').children('i').remove();
                                    parent_list.remove();
                                    $.categories_tree.setCollapsed(old_parent_id);
                                }
                                // has children, but not loaded yet
                                if (!list) {
                                    dr.remove();
                                    sep.remove();
                                    // Because children is not loaded yet, calling of count_subtree (see below)
                                    // will not have effect.
                                    // So counters is returned by server
                                    self.trigger('update_counters',
                                        r.data.count || {}
                                    );
                                } else {
                                    $('.s-collection-list .unapproved').removeClass('unapproved');
                                    if (first_child) {
                                        $.categories_tree.setExpanded(parent_id);
                                    } else {
                                        self.trigger('count_subtree');
                                    }
                                }
                            },
                            error: function(r) {
                                if (r && console) {
                                    console.log(typeof r.errors !== 'undefined' ? r.errors : r);
                                }
                                // restore
                                home.after(dr).after(sep);
                                if (!li_count) {
                                    parent_list.parent('li').children('i').show();
                                    parent_list.show();
                                }
                                $('.s-collection-list .unapproved').remove();
                            }
                        });
                    }
                }
            });
        },

    };
    
})(jQuery);
