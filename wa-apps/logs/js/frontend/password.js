$(function () {
    $('.dialog-content').attr('class', '').waDialog({
        height: '170px',
        width: '500px',
        buttons: $('.dialog-buttons').html(),
        disableButtonsOnSubmit: true,
        esc: false,
        onLoad: function () {
            $(this).find('[type="password"]').focus();
        },
        onSubmit: function (dialog) {
            var $dialog = $(dialog);
            var $error_message = $dialog.find('.errormsg');
            var $loading = $('<i class="icon16 loading" style="vertical-align: middle;"></i>');

            $error_message.empty();
            $dialog.find('.dialog-buttons-gradient').append($loading);

            $.post('', $(this).serialize(), function (response) {
                $loading.remove();
                if (response.status == 'fail') {
                    if (response.errors !== undefined) {
                        $error_message.html(response.errors.join(' '));
                    }
                    dialog.find('input[type="submit"]').removeAttr('disabled');
                    dialog.find('[type="password"]').focus();
                } else {
                    dialog.trigger('close');
                    location.reload();
                }
            });

            return false;
        }
    });
});