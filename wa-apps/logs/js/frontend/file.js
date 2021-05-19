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

                var new_height = $(window).height()
                    - parseFloat($('body').css('padding-top'), 10)
                    - parseFloat($('body').css('padding-bottom'), 10)

                    - $('#wa-header').height()

                    - parseFloat($('#wa-app').css('padding-top'), 10)
                    - parseFloat($('#wa-app').css('padding-bottom'), 10)

                    - parseFloat($('#wa-app > .content').css('margin-top'), 10)
                    - $('.item-data .pagination').outerHeight()

                    - $('.item-data .item-lines.previous').outerHeight()
                    - $('.item-data .item-lines.next').outerHeight()

                    - parseFloat($('.item-contents').css('padding-top'), 10)
                    - parseFloat($('.item-contents').css('padding-bottom'), 10);

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
            //download more file contents via AJAX
            $(document).on('click', '.item-lines', function () {
                var button = $(this);

                if (button.hasClass('disabled')) {
                    return;
                }

                var arrow = button.find('.arrow');
                arrow.addClass('hidden');

                var $loading = $('<i class="icon16 loading"></i>');
                button.append($loading);

                var direction = button.hasClass('previous') ? 'previous' : 'next';
                var form = $('.item-lines-form');
                form.find('[name="direction"]').val(direction);

                $.post('', form.serialize(), function (response) {
                    $loading.remove();

                    if (response.status == 'fail') {
                        $('<p class="error">' + response.errors.join('<br>') + '</p>').waDialog({
                            buttons: '<input type="submit" value="' + $.loc['OK'] + '" class="button blue">',
                            width: '500px',
                            height: '100px',
                            esc : false,
                            onSubmit: function (d) {
                                d.find('.loading').show();
                                location.href = response.data.redirect_url;
                                return false;
                            },
                            onClose: function () {
                                $(this).remove();
                            }
                        });
                    } else {
                        var item_contents = $('.item-contents');
                        if (response.data.contents.length) {
                            $('<i class="icon16 yes-bw"></i>')
                            .appendTo(button)
                            .animate({opacity: 0}, 700, function () {
                                $(this).remove();
                                if (response.data.first_line == 0) {
                                    button.addClass('disabled').attr('title', '');
                                }
                                arrow.removeClass('hidden');
                            });

                            if (direction == 'previous') {
                                var initial_scroll_height = item_contents.prop('scrollHeight');
                                item_contents.prepend(response.data.contents);
                                var initial_scroll_top = item_contents.prop('scrollHeight') - initial_scroll_height + item_contents.prop('scrollTop');
                                if (response.data.first_line > 0) {
                                    $('[name="first_line"]').val(response.data.first_line);
                                }
                            } else {
                                var initial_scroll_top = item_contents.prop('scrollTop');
                                item_contents.append("\n" + response.data.contents);
                                $('[name="last_line"]').val(response.data.last_line);
                            }

                            self.prettyPrint();

                            if ($.browser.msie && parseFloat($.browser.version) < 9) {
                                setTimeout(function () {
                                    self.adjustItemContentsHeight();
                                }, 0);
                            } else {
                                self.adjustItemContentsHeight();
                            }

                            if (direction == 'previous') {
                                self.scrollUp(item_contents, initial_scroll_top);
                            } else {
                                self.scrollDown(item_contents, initial_scroll_top);
                            }
                        } else {
                            var message = $('<span class="hint message">' + $('.item-data').data('item-lines-empty-message') + '</span>');
                            message.appendTo(button);
                            message.animate({opacity: 0}, 700, function () {
                                $(this).remove();
                                arrow.removeClass('hidden');
                            });
                        }
                    }
                }, 'json');
            });
        }
    };

    self.init();

});