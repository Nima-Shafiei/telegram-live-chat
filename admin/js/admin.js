(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAppearanceControls();
        var connectButton = document.getElementById('tlcwt-connect-telegram');

        if (!connectButton) {
            return;
        }

        connectButton.addEventListener('click', function () {
            var form = document.getElementById('tlcwt-settings-form');

            if (!form) {
                return;
            }

            var connectUrl = connectButton.getAttribute('data-connect-url');

            if (!connectUrl) {
                return;
            }

            /**
             * Point the form at the connect handler instead of options.php,
             * and switch the hidden "action" field (printed by
             * settings_fields()) from "update" to our connect action, so
             * the whole form -- including any field the admin just typed
             * but never explicitly saved -- is submitted and saved before
             * the plugin attempts to connect to Telegram.
             */
            var actionField = form.querySelector('input[name="action"]');

            if (actionField) {
                actionField.value = 'tlcwt_connect_telegram';
            }

            connectButton.disabled = true;

            form.action = connectUrl;
            form.method = 'post';
            form.submit();
        });
    });

    function initAppearanceControls() {
        if (window.jQuery && jQuery.fn.wpColorPicker) {
            jQuery('.tlcwt-color').wpColorPicker();
        }

        var mediaFrame;
        var selectButton = document.getElementById('tlcwt-select-icon');
        var removeButton = document.getElementById('tlcwt-remove-icon');
        var iconId = document.querySelector('input[name="tlcwt_settings[appearance][icon_id]"]');
        var iconPreview = document.querySelector('.tlcwt-icon-preview');
        if (!selectButton || !removeButton || !iconId || !iconPreview) return;

        function setIcon(url, id) {
            iconPreview.src = url;
            iconId.value = id || '';
            removeButton.disabled = !id;
        }
        selectButton.addEventListener('click', function (event) {
            event.preventDefault();
            if (mediaFrame) { mediaFrame.open(); return; }
            mediaFrame = wp.media({ title: 'Choose chat icon', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                setIcon(attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url, attachment.id);
            });
            mediaFrame.open();
        });
        removeButton.addEventListener('click', function (event) {
            event.preventDefault();
            var defaultIcon = removeButton.getAttribute('data-default-icon');
            setIcon(defaultIcon, '');
        });
    }
})();
