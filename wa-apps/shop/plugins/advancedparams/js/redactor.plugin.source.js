(function($) {
    version_compare = function(left, right) {
        if (typeof left + typeof right != 'stringstring')
            return false;

        var a = left.split('.')
            ,   b = right.split('.')
            ,   i = 0, len = Math.max(a.length, b.length);

        for (; i < len; i++) {
            if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
                return true;
            } else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
                return false;
            }
        }
        return true;
    };
    $.shopAdvancedparamsPluginRedactor = {
        init: function($obj, params) {
            if(!version_compare("2.8.1", $.Redactor.VERSION) && version_compare("10.0", $.Redactor.VERSION)) {
                params['imageUpload'] = '?module=pages&action=uploadimage&r=2';
                params['toolbarFixed'] = true;
                params['buttons'] = ['html', 'format', 'bold', 'italic', 'underline', 'deleted', 'lists',
                    'image', 'video', 'table', 'link', 'alignment',
                    'horizontalrule',  'fontcolor', 'fontsize', 'fontfamily'];
                params['plugins'] = ['fontcolor', 'fontsize', 'fontfamily', 'table', 'video','source'];
            } else {
                params['imageUpload'] = '?module=pages&action=uploadimage&filelink=1';
                params['toolbarFixed'] = false;
                params['plugins'] = ['fontcolor', 'fontsize', 'fontfamily', 'table', 'video'];
                params['buttons'] = ['html', 'formatting', 'bold', 'italic', 'underline', 'deleted', 'unorderedlist', 'orderedlist',
                    'outdent', 'indent', 'image', 'video', 'table', 'link', 'alignment', '|',
                    'horizontalrule'];

            }
            $obj.redactor(params);
        }
    };
    if(version_compare("2.8.1", $.Redactor.VERSION)) {
        $.Redactor.prototype.source = function()
        {
            return {
                init: function()
                {
                    var button = this.button.addFirst('html', 'HTML');
                    this.button.setIcon(button, '<i class="re-icon-html"></i>');
                    this.button.addCallback(button, this.source.toggle);

                    var style = {
                        'width': '100%',
                        'margin': '0',
                        'background': '#1d1d1d',
                        'box-sizing': 'border-box',
                        'color': '#ccc',
                        'font-size': '15px',
                        'outline': 'none',
                        'padding': '20px',
                        'line-height': '24px',
                        'font-family': 'Consolas, Menlo, Monaco, "Courier New", monospace'
                    };

                    this.source.$textarea = $('<textarea />');
                    this.source.$textarea.css(style).hide();

                    if (this.opts.type === 'textarea')
                    {
                        this.core.box().append(this.source.$textarea);
                    }
                    else
                    {
                        this.core.box().after(this.source.$textarea);
                    }

                    this.core.element().on('destroy.callback.redactor', $.proxy(function()
                    {
                        this.source.$textarea.remove();

                    }, this));

                },
                toggle: function()
                {
                    if (this.source.$textarea.hasClass('open'))
                    {
                        this.source.hide();
                    }
                    else
                    {
                        this.source.show();
                        this.source.$textarea.on('keyup.redactor-source', $.proxy(function()
                        {
                            var html = this.source.$textarea.val();
                            this.core.callback('change', html);

                        }, this));
                    }
                },
                setCaretOnShow: function()
                {
                    this.source.offset = this.offset.get();
                    var scroll = $(window).scrollTop();

                    var	width = this.core.editor().innerWidth();
                    var height = this.core.editor().innerHeight();

                    // caret position sync
                    this.source.start = 0;
                    this.source.end = 0;
                    var $editorDiv = $("<div/>").append($.parseHTML(this.core.editor().html(), document, true));
                    var $selectionMarkers = $editorDiv.find("span.redactor-selection-marker");

                    if ($selectionMarkers.length > 0)
                    {
                        var editorHtml = $editorDiv.html().replace(/&amp;/g, '&');

                        if ($selectionMarkers.length === 1)
                        {
                            this.source.start = this.utils.strpos(editorHtml, $editorDiv.find("#selection-marker-1").prop("outerHTML"));
                            this.source.end = this.source.start;
                        }
                        else if ($selectionMarkers.length === 2)
                        {
                            this.source.start = this.utils.strpos(editorHtml, $editorDiv.find("#selection-marker-1").prop("outerHTML"));
                            this.source.end = this.utils.strpos(editorHtml, $editorDiv.find("#selection-marker-2").prop("outerHTML")) - $editorDiv.find("#selection-marker-1").prop("outerHTML").toString().length;
                        }
                    }
                },
                setCaretOnHide: function(html)
                {
                    this.source.start = this.source.$textarea.get(0).selectionStart;
                    this.source.end = this.source.$textarea.get(0).selectionEnd;

                    // if selection starts from end
                    if (this.source.start > this.source.end && this.source.end > 0)
                    {
                        var tempStart = this.source.end;
                        var tempEnd = this.source.start;

                        this.source.start = tempStart;
                        this.source.end = tempEnd;
                    }

                    this.source.start = this.source.enlargeOffset(html, this.source.start);
                    this.source.end = this.source.enlargeOffset(html, this.source.end);

                    html = html.substr(0, this.source.start) + this.marker.html(1) + html.substr(this.source.start);

                    if (this.source.end > this.source.start)
                    {
                        var markerLength = this.marker.html(1).toString().length;

                        html = html.substr(0, this.source.end + markerLength) + this.marker.html(2) + html.substr(this.source.end + markerLength);
                    }


                    return html;

                },
                hide: function()
                {
                    this.source.$textarea.removeClass('open').hide();
                    this.source.$textarea.off('.redactor-source');

                    var code = this.source.$textarea.val();

                    code = this.paragraphize.load(code);
                    code = this.source.setCaretOnHide(code);
                    code = code.replace('&amp;<span id="selection-marker-1" class="redactor-selection-marker">​</span>', '<span id="selection-marker-1" class="redactor-selection-marker">​</span>&amp;');

                    this.code.start(code);
                    this.button.enableAll();
                    this.core.editor().show().focus();
                    this.selection.restore();
                    this.placeholder.enable();

                    this.core.callback('visual');
                },
                show: function()
                {
                    this.selection.save();
                    this.source.setCaretOnShow();

                    var height = this.core.editor().height();
                    var code = this.code.get();

                    // callback
                    code = this.core.callback('source', code);

                    this.core.editor().hide();
                    this.button.disableAll('html');

                    this.source.$textarea.val(code).height(height).addClass('open').show();
                    this.source.$textarea.on('keyup.redactor-source', $.proxy(function()
                    {
                        if (this.opts.type === 'textarea')
                        {
                            this.core.textarea().val(this.source.$textarea.val());
                        }

                    }, this));

                    this.marker.remove();

                    $(window).scrollTop(scroll);

                    if (this.source.$textarea[0].setSelectionRange)
                    {
                        this.source.$textarea[0].setSelectionRange(this.source.start, this.source.end);
                    }

                    this.source.$textarea[0].scrollTop = 0;

                    setTimeout($.proxy(function()
                    {
                        this.source.$textarea.focus();

                    }, this), 0);
                },
                enlargeOffset: function(html, offset)
                {
                    var htmlLength = html.length;
                    var c = 0;

                    if (html[offset] === '>')
                    {
                        c++;
                    }
                    else
                    {
                        for(var i = offset; i <= htmlLength; i++)
                        {
                            c++;

                            if (html[i] === '>')
                            {
                                break;
                            }
                            else if (html[i] === '<' || i === htmlLength)
                            {
                                c = 0;
                                break;
                            }
                        }
                    }

                    return offset + c;
                }
            };
        };
    }
})(jQuery);