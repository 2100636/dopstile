$(function () {

    var self = {
        init: function () {
            self.fixFirstPageLink();
            self.prettyPrint();
            self.adjustItemContentsHeight();
            self.itemInitScrollDown();
            self.events();
        },

        fixFirstPageLink: function () {
            $('.pagination a').each(function () {
                var link = $(this);
                var href = link.attr('href');
                if (href.indexOf('page=') < 0) {
                    if (href.indexOf('?') < 0) {
                        href = href + '?page=1';
                    } else {
                        href = href + '&page=1';
                    }
                    link.attr('href', href);
                }
            });
        },

        adjustItemContentsHeight: function () {
            var item_contents = $('.item-contents');
            item_contents.css('height', 'auto');  //reset to auto height for a fresh start after previous adjustment

            var document_height = $(document).height();
            if ($.browser.msie) {
                document_height -= 5; //hack for IE
            }

            var height_diff = document_height - $(window).height();

            if (height_diff > 0) {
                var enabled_button = $('.item-lines').not('.disabled').eq(0);
                var disabled_button = $('.item-lines.disabled').eq(0);

                var new_height = $(window).outerHeight()
                    - parseFloat($('body').css('padding-top'), 10)

                    - $('#wa-header').outerHeight()

                    - parseFloat($('#wa-app').css('margin-top'), 10)
                    - parseFloat($('#wa-app').css('padding-top'), 10)
                    - parseFloat($('#wa-app').css('padding-bottom'), 10)

                    - $('.navigation').outerHeight()
                    - parseFloat($('#wa-app > .content').css('margin-top'), 10)
                    - $('.item-data .pagination').outerHeight()

                    - $('.item-data .item-lines.previous').outerHeight()
                    - $('.item-data .item-lines.next').outerHeight()

                    - parseFloat($('.item-contents').css('padding-top'), 10)
                    - parseFloat($('.item-contents').css('padding-bottom'), 10)
                    - parseFloat($('body').css('padding-bottom'), 10);

                item_contents.height(new_height);
            }
        },

        scrollUp: function (element, initital_scroll_top) {
            element.prop('scrollTop', initital_scroll_top).animate({
                scrollTop: 0
            }, 1000);
        },

        scrollDown: function (element, initital_scroll_top) {
            element.prop('scrollTop', initital_scroll_top).animate({
                scrollTop: element.prop('scrollHeight')
            }, 1000);
        },

        itemInitScrollDown: function () {
            if (window.location.search.indexOf('page=') < 0) {
                var item_contents = $('.item-contents:first');
                item_contents.prop('scrollTop', item_contents.prop('scrollHeight'));
            }
        },

        prettyPrint: function () {
            prettyPrint();
        },

        events: function () {
            // download more item contents via AJAX
            $(document).on('click', '.item-lines', function () {
                var button = $(this);

                if (button.hasClass('disabled')) {
                    return false;
                }
                
                button.addClass('disabled');

                var arrow = button.find('.arrow');
                
                arrow.addClass('hidden');

                var $loading = $('<i class="icon16 loading"></i>');
                button.append($loading);

                var direction = $(this).hasClass('previous') ? 'previous' : 'next';
                var form = $('.item-lines-form:first');
                form.find('[name="direction"]').val(direction);

                $.post('', form.serialize(), function (response) {
                    $loading.remove();

                    if (response.status == 'fail') {
                        arrow.removeClass('hidden');

                        var reload = function () {
                            if (response.data.return_url.length) {
                                location.href = response.data.return_url;
                            } else {
                                location.reload();
                            }
                        };

                        if (response.errors !== undefined && response.errors.length) {
                            $('<p class="errormsg">' + response.errors.join(' ') + '</p>').waDialog({
                                buttons: '<input type="submit" value="' + $('.item-data').data('item-lines-dialog-ok') + '" class="button blue">'
                                    + '<i class="icon16 loading hidden" style="vertical-align: middle;"></i>',
                                width: '500px',
                                height: '100px',
                                esc : false,
                                onSubmit: function (d) {
                                    if (response.data.return_url !== undefined) {
                                        d.find('.loading').removeClass('hidden');
                                        reload();
                                    }
                                    return false;
                                },
                                onClose: function () {
                                    $(this).remove();
                                }
                            });
                        } else {
                            reload();
                        }
                    } else {
                        //status = ok
                        
                        var item_contents = $('.item-contents');

                        if (response.data.contents) {
                            $('<i class="icon16 yes-bw"></i>')
                            .appendTo(button)
                            .animate({opacity: 0}, 700, function () {
                                $(this).remove();
                                if (direction == 'previous' && response.data.first_line == 0) {
                                    button.attr('title', '');
                                } else {
                                    button.removeClass('disabled');
                                }
                                arrow.removeClass('hidden');
                            });

                            if (direction == 'previous') {
                                var initial_scroll_height = item_contents.prop('scrollHeight');
                                item_contents.prepend(response.data.contents);
                                var initial_scroll_top = item_contents.prop('scrollHeight') - initial_scroll_height + item_contents.prop('scrollTop');
                                $('[name="first_line"]').val(response.data.first_line);
                            } else {
                                var initial_scroll_top = item_contents.prop('scrollTop');
                                item_contents.append(response.data.contents);
                                $('[name="last_line"]').val(response.data.last_line);

                                if (response.data.last_eol !== undefined) {
                                    form.find('[name="last_eol"]').val(response.data.last_eol);
                                }

                                if (response.data.file_end_eol !== undefined) {
                                    form.find('[name="file_end_eol"]').val(response.data.file_end_eol);
                                }
                            }

                            if (response.data.file_size !== undefined) {
                                $('.total-size-file').text(response.data.file_size);
                            }

                            self.prettyPrint();
                            self.adjustItemContentsHeight();

                            if (direction == 'previous') {
                                self.scrollUp(item_contents, initial_scroll_top);
                            } else {
                                self.scrollDown(item_contents, initial_scroll_top);
                            }
                        } else {
                            $('<span class="hint message">' + $('.item-data').data('item-lines-empty-message') + '</span>')
                                .appendTo(button)
                                .animate({opacity: 0}, 700, function () {
                                    $(this).remove();
                                    arrow.removeClass('hidden');
                                    button.removeClass('disabled');
                                });
                        }
                    }
                }, 'json');
            });
        }
    };

    self.init();

});