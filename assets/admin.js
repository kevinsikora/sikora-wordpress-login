(function ($) {
    'use strict';

    var mediaUploader;
    var $preview = $('#sikora-image-preview');
    var $remove = $('#sikora-remove-image');
    var $field = $('#sikora-bg-image-id');
    var allowedMimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    function isAllowedMime(mime) {
        return mime && allowedMimes.indexOf(mime) !== -1;
    }

    function showPreview(url) {
        if (!url) {
            return;
        }
        $preview.attr('src', url).attr('alt', 'Current Sikora WordPress Login background').css('display', 'block');
        $remove.css('display', '');
    }

    function hidePreview() {
        $field.val('');
        $preview.attr('src', '').attr('alt', '').css('display', 'none');
        $remove.css('display', 'none');
    }

    // Keep a saved/selected preview visible on load.
    if ($field.val() && $preview.attr('src')) {
        showPreview($preview.attr('src'));
    }

    $('#sikora-choose-image').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Sikora WordPress Login — Choose Background Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: {
                type: ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
            }
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var mime = attachment.mime || '';
            var url = attachment.url || (attachment.sizes && attachment.sizes.full && attachment.sizes.full.url) || '';

            if (!attachment.id || !url) {
                return;
            }

            // Only JPEG, PNG, GIF, or WebP (server re-validates on save).
            if (!isAllowedMime(mime)) {
                window.alert('Please select a JPEG, PNG, GIF, or WebP image.');
                return;
            }

            $field.val(attachment.id);
            showPreview(url);
        });

        mediaUploader.open();
    });

    $remove.on('click', function (e) {
        e.preventDefault();
        hidePreview();
    });

    // Background color picker (Iris); clearing it falls back to the WordPress default.
    if ($.fn.wpColorPicker) {
        $('.sikora-color-field').wpColorPicker();
    }
})(jQuery);
