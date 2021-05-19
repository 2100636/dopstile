var InstallerLicenses = ( function($) {

    // private helpers
    var escapeRegex = function(string) {
        return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    };

    var updateQueryParameter = function (href, query_param, val) {
        href = decodeURI(href);
        if (href.indexOf('?') === -1) {
            // there is no any parameter
            href += '?' + query_param + '=' + val;
        } else {
            var query = escapeRegex(query_param);
            var regexp = new RegExp('(?:\\?|&)' + query + '=(.*?)(?:&|$)');
            var m = href.match(regexp);
            if (m) {
                // if found parameter then replace it
                href = href.replace(query_param + '=' + m[1], query_param + '=' + val);
            } else {
                // otherwise add new parameter
                href += '&' + query_param + '=' + val;
            }
        }
        return href;
    };

    var deleteQueryParameter = function (href, query_param) {
        href = decodeURI(href);
        var query = escapeRegex(query_param);
        var regexp = new RegExp('(?:\\?|&)' + query + '=(.*?)(&|$)');
        var m = href.match(regexp);
        if (m) {
            var q = query_param + '=' + m[1];
            if (m[2] === '&') {
                q += '&';
            }
            href = href.replace(q, '');
        }
        if (href.substr(-1, 1) === '?') {
            href = href.slice(0, -1);
        }
        return href;
    };

    InstallerLicenses = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;

        // VAR
        that.licenses = options.licenses || {}; // license_id => license
        that.app_url = options.app_url;
        that.autocomplete_url = options.autocomplete_url;

        that.init();
    };

    InstallerLicenses.prototype.init = function() {
        var that = this;

        that.initOrderIdFilter();
        that.initProductNameFilter();
        that.initResetFiltersButton();
        that.initInstallButtons();
    };

    InstallerLicenses.prototype.initResetFiltersButton = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $button = $wrapper.find('.js-reset-filters');
        $button.on('click', function () {
            var href = window.location.href;
            href = deleteQueryParameter(href, 'name[val]');
            href = deleteQueryParameter(href, 'name[op]');
            href = deleteQueryParameter(href, 'order_id');
            window.location.href = href;
        });
    };

    InstallerLicenses.prototype.initOrderIdFilter = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $input = $wrapper.find('.js-order-id-filter');

        // on press Enter
        $input.on('keydown', function (e) {
            if (e.keyCode === 13) {
                var val = $.trim($input.val());
                if (val && !isNaN(+val)) {
                    // if typed in input and press enter then filter by order id as equality matching
                    window.location.href = updateQueryParameter(window.location.href, 'order_id', val);
                } else if (!val) {
                    // if typed empty string and press enter then delete filter by order id
                    window.location.href = deleteQueryParameter(window.location.href, 'order_id');
                }
            }
        });
    };

    InstallerLicenses.prototype.initProductNameFilter = function () {
        var that = this,
            $wrapper = that.$wrapper,
            $input = $wrapper.find('.js-product-name-filter');

        var updateNameParameter = function (href, product_name, op) {
            href = updateQueryParameter(href, 'name[val]', encodeURIComponent(product_name));
            if (op !== '*=') {
                op = '=';
            }
            href = updateQueryParameter(href, 'name[op]', op);
            return href;
        };

        // autocomplete
        $input.autocomplete({
            delay: 300,
            minLength: 0,
            source: that.autocomplete_url,
            appendTo: $wrapper,
            dataType: 'json',
            select: function(event, ui) {
                var product_name = ui.item.value;
                // if select item in autocomplete then filter by product name as equality matching
                window.location.href = updateNameParameter(window.location.href, product_name);
                return false;
            }
        });

        // on press Enter
        $input.on('keydown', function (e) {
            if (e.keyCode === 13) {
                var val = $.trim($input.val());
                if (val) {
                    // if typed in input and press enter then filter by product name as substring matching
                    window.location.href = updateNameParameter(window.location.href, val, '*=');
                } else if (!val) {
                    // if typed empty string and press enter then delete filter by product name
                    var href = window.location.href;
                    href = deleteQueryParameter(href, 'name[val]');
                    href = deleteQueryParameter(href, 'name[op]');
                    window.location.href = href;
                }
                return false;
            }
        });
    };

    InstallerLicenses.prototype.initInstallButtons = function () {
        var that = this,
            licenses = that.licenses;

        var install = function (license_id) {
            var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)")),
                csrf = matches ? decodeURIComponent(matches[1]) : '',
                url = that.app_url + '?module=update&action=manager',
                license = licenses[license_id],
                fields = [];

            fields.push({ name: 'install', value: 1 });
            fields.push({ name: '_csrf', value: csrf });
            fields.push({ name: 'app_id[' + license.slug + ']', value: license.vendor });

            that.postByForm(url, fields);
        };

        var bindLicense = function (license_id, onDone, onError) {
            var url = that.app_url + '?module=licenses&action=bind';
            $.post(url, { id: license_id })
                .done(function (r) {
                    if (r && r.status === 'ok') {
                        onDone();
                    } else if (r && r.errors) {
                        onError(r.errors);
                    }
                });
        }

        var onClickInstallButton = function ($button) {
            var $item = $button.closest('.js-license-item'),
                $loading = $item.find('.js-loading'),
                license_id = $item.data('id');

            $item.find('.js-bind-error').hide();

            $loading.show();

            bindLicense(license_id,
                function () {
                    install(license_id);
                    $loading.hide();
                },
                function (errors) {
                    var error_msg = $.isArray(errors) ? errors.join('<br>', errors) : errors;
                    $item.find('.js-bind-error').show().text(error_msg);
                    $loading.hide();
                });
        };

        that.$wrapper.on('click', '.js-install-button', function () {
            var $button = $(this);
            onClickInstallButton($button);
        });
    };

    InstallerLicenses.prototype.postByForm = function (url, fields) {
        var $form = $('<form>', {
            action: url,
            method: 'post'
        });

        $.each(fields, function (i, field) {
            $('<input>').attr({
                type: "hidden",
                name: field.name,
                value: field.value
            }).appendTo($form);
        });

        $form.appendTo('body').submit();
    };

    return InstallerLicenses;

})(jQuery);
