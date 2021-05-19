var InstallerLicense = ( function($) {

    InstallerLicense = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options.$wrapper;

        // VAR
        that.license = options.license || null;

        var licenses = {};
        if (that.license) {
            licenses[that.license.license_id] = that.license;
        }

        that.is_auto_install = options.is_auto_install;

        /**
         * @param {InstallerLicenses}
         */
        that.installerLicenses = new InstallerLicenses($.extend({ licenses: licenses }, options));
        that.init();
    };

    InstallerLicense.prototype.init = function() {
        var that = this;

        // remove from history 'install' parameter, so when user click back we returns to url without install parameter
        var href = window.location.href || '';
        if (href.indexOf('install=')) {
            href = href.replace(/install=(.*?)(&|$)/, '');
            href = href.replace(/\?$/, '');
            window.history.pushState({}, '', href)
        }

        // make sense only for one license list
        if (that.is_auto_install) {
            that.$wrapper.find('.js-install-button:first').trigger('click');
        }
    };

    return InstallerLicense;

})(jQuery);
