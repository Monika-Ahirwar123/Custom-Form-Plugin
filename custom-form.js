jQuery(document).ready(function($) {
    $('#customForm').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'submit_form',
            name: $('#name').val(),
            email: $('#email').val(),
            message: $('#message').val(),
        };

        $.post(customFormAjax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#formResponse').html('<p style="color: green;">' + response.data.message + '</p>');
                $('#customForm')[0].reset();
            } else {
                $('#formResponse').html('<p style="color: red;">' + response.data.message + '</p>');
            }
        });
    });
});
