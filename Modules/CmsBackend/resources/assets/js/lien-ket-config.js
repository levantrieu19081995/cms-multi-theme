'use strict';

(function () {
    function lfm(id, options) {

        let button = document.getElementById(id);
        if (!button) return;
        button.addEventListener('click', function () {
            var route_prefix = (options && options.prefix) ? options.prefix : '/laravel-filemanager';
            var target_input = document.getElementById(button.getAttribute('data-input'));
            var target_preview = document.getElementById(button.getAttribute('data-preview'));
            var type = button.getAttribute('data-type') ?? 'image';
            let popupWinWidth = 900;
            let popupWinHeight = 600;
            let left = (screen.width - popupWinWidth) / 2;
            let top = (screen.height - popupWinHeight) / 4;
            window.open(route_prefix + '?type=' + type || 'file', 'FileManager', 'width=' + popupWinWidth +
                ',height=' + popupWinHeight + ',left=' + left + ',top=' + top);
            window.SetUrl = function (items) {
                var file_path = items.map(function (item) {
                    return item.url;
                }).join(',');
                target_input.value = file_path;
                target_input.dispatchEvent(new Event('change'));
                target_preview.innerHTML = '';
                // set or change the preview image src
                items.forEach(function (item) {
                    let img = document.createElement('img')
                    // img.setAttribute('style', 'height: 5rem')
                    img.setAttribute('src', item.url)
                    target_preview.appendChild(img);
                });
                // trigger change event
                target_preview.dispatchEvent(new Event('change'));

            };
        });
    };

    lfm('lfm', {
        prefix: '/laravel-filemanager'
    });
})();