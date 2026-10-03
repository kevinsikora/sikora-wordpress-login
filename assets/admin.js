(function ($) {
    'use strict';

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

    /**
     * Binds a Media Library picker to choose/remove buttons for an attachment ID field.
     *
     * @param {Object} opts
     * @param {string} opts.chooseBtn
     * @param {string} opts.removeBtn
     * @param {string} opts.field
     * @param {string} opts.preview
     * @param {string} opts.title
     * @param {string} opts.previewAlt
     */
    function bindImagePicker(opts) {
        var mediaUploader;
        var $preview = $(opts.preview);
        var $remove = $(opts.removeBtn);
        var $field = $(opts.field);

        function showPreview(url) {
            if (!url) {
                return;
            }
            $preview.attr('src', url).attr('alt', opts.previewAlt).css('display', 'block');
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

        $(opts.chooseBtn).on('click', function (e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: opts.title,
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
    }

    bindImagePicker({
        chooseBtn: '#sikora-choose-logo',
        removeBtn: '#sikora-remove-logo',
        field: '#sikora-logo-image-id',
        preview: '#sikora-logo-preview',
        title: 'Sikora WordPress Login — Choose Business Logo',
        previewAlt: 'Current Sikora WordPress Login business logo'
    });

    bindImagePicker({
        chooseBtn: '#sikora-choose-image',
        removeBtn: '#sikora-remove-image',
        field: '#sikora-bg-image-id',
        preview: '#sikora-image-preview',
        title: 'Sikora WordPress Login — Choose Background Image',
        previewAlt: 'Current Sikora WordPress Login background'
    });

    // Background color picker (Iris); clearing it falls back to the WordPress default.
    if ($.fn.wpColorPicker) {
        $('.sikora-color-field').wpColorPicker();
    }
})(jQuery);
