// admin/js/admin.js

jQuery(document).ready(function($) {
    $('.generate-alt-text').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var imageId = button.data('image-id');
        var row = button.closest('tr');
        var altInput = row.find('.alt-text-input');

        button.prop('disabled', true).text('Generating...');

        $.ajax({
            url: accessibilityAI.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_alt_text',
                nonce: accessibilityAI.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    altInput.val(response.data.alt_text);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An unexpected error occurred.');
            },
            complete: function() {
                button.prop('disabled', false).text('Generate Alt Text');
            }
        });
    });

    $('.save-alt-text').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var imageId = button.data('image-id');
        var row = button.closest('tr');
        var altInput = row.find('.alt-text-input');
        var altText = altInput.val();

        button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: accessibilityAI.ajax_url,
            method: 'POST',
            data: {
                action: 'save_alt_text',
                nonce: accessibilityAI.nonce,
                image_id: imageId,
                alt_text: altText
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An unexpected error occurred.');
            },
            complete: function() {
                button.prop('disabled', false).text('Save');
            }
        });
    });
});
