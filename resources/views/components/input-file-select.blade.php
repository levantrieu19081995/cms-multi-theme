@props(['disabled' => false, 'value' => '', 'name' => 'files', 'type' => 'file', 'multiple' => false, 'btnname' => null])
@php
    $btnname = $btnname ?? __('admin.ui.chon_tap_tin');
    $ranNum = rand(10, 1000);
@endphp

<script type="module">
    const target_input = document.querySelector('input[id_=input_{{ $ranNum }}]');
    const table_preview = document.getElementById('table_{{ $ranNum }}');

    const getNameFromUrl = (url) => {
        return url.substring(url.lastIndexOf('/')+1);
    }

    const rowEventRegister = () => {
        // Action delete
        document.querySelectorAll('#table_{{ $ranNum }} i[data-action=delete]').forEach((el) => {
            el.addEventListener('click', () => {
                // Remove element
                el.closest('tr').remove();
                // Trigger change input
                target_input.dispatchEvent(new Event('change'));
            })
        });

        // Action view
        document.querySelectorAll('#table_{{ $ranNum }} a').forEach((el) => {
            el.addEventListener('click', () => {
                let url = el.getAttribute('data-url');
                let popupWinWidth = 900;
                let popupWinHeight = 600;
                let left = (screen.width - popupWinWidth) / 2;
                let top = (screen.height - popupWinHeight) / 4;
                window.open(url, 'Preview', 'width='+popupWinWidth+',height='+ popupWinHeight+',left='+left+',top='+top);
            })
        })
    }

    const renderTable = (files) => {
        // clear previous preview
        table_preview.innerHTML = '';
        // set or change the preview image src
        var renderHtml = '';
        const templateTr = document.getElementById('template-tr').innerHTML;
        var renderFiles = files;
        if (!Array.isArray(renderFiles)) {
            renderFiles = [renderFiles];
        }

        renderFiles.forEach(function (item) {
            renderHtml += templateTr.replaceAll('#url', item.url)
                .replaceAll('#name', item.name);
        });

        table_preview.innerHTML = renderHtml;

        rowEventRegister();
    }

    const renderTableNewRow = (newFiles) => {
        console.log(target_input.value)
        var items = JSON.parse(target_input.value);
        var files = items.map((item) => {
            return { url: item, name: getNameFromUrl(item)};
        });
        for (let nf of newFiles) {
            if (!files.some(file => nf === file)) {
                    files.push(nf);
                     }
            
        }

        renderTable(files);
    }

    var lfm_{{ $ranNum }} = function(id, type, options) {
        console.log('type',type)
        let button = document.getElementById(id);
        const target_input = document.querySelector('input[id_=input_{{ $ranNum }}]');
        const table_preview = document.getElementById('table_{{ $ranNum }}');

        button.addEventListener('click', function () {
            var route_prefix = (options && options.prefix) ? options.prefix : '/laravel-filemanager';

            let popupWinWidth = 900;
            let popupWinHeight = 600;
            let left = (screen.width - popupWinWidth) / 2;
            let top = (screen.height - popupWinHeight) / 4;
            window.open(route_prefix + '?type=' + type || 'file', 'FileManager', 'width='+popupWinWidth+',height='+ popupWinHeight+',left='+left+',top='+top);
            window.SetUrl = function (items) {
                var file_path = items.map(function (item) {
                    return item.url;
                }).join(',');

                @if ($multiple)
                renderTableNewRow(items);
                @else
                renderTable(items[0]);
                @endif

                target_input.dispatchEvent(new Event('change'));
            };
        });

        // Render old data
        try {
            @if ($multiple)
            var items = JSON.parse(target_input.value);
            var files = items.map((item) => {
                return { url: item, name: getNameFromUrl(item) };
            });
            @else
            var files = [];
            if (target_input.value) {
                files.push({ url: target_input.value, name: getNameFromUrl(target_input.value) });
            }
            @endif

            if (files.length > 0) {
                @if ($multiple)
                renderTable(files);
                @else
                renderTable(files[0]);
                @endif

                target_input.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            console.log(error)
        }

        // Register event change
        target_input.addEventListener('change', function () {
            var files = [];
            for (let el of document.querySelectorAll('#table_{{ $ranNum }} a')) {
                files.push(el.getAttribute('data-url'));
            }
            @if ($multiple)
            this.value = JSON.stringify(files);
            @else
            if (files.length > 0) {
                this.value = files[0].trim();
            }
            @endif
        })
    };

    lfm_{{ $ranNum }}('lfm_{{ $ranNum }}', '{{$type}}' , { prefix: '/laravel-filemanager' });
</script>

<input id_="input_{{ $ranNum }}" type="hidden" name="{{ $name }}" value="{{ $value }}" {!! $attributes !!}>
<button id="lfm_{{ $ranNum }}" type="button" class="btn btn-primary waves-effect waves-light btn-sm">{{ $btnname }}</button>
<div>
    <table id="table_{{ $ranNum }}" style="margin-top: 10px">
        {{-- Render here --}}
    </table>
</div>
{{-- Template for row --}}
<template id="template-tr">
    <tr>
        <td>
            <input type="hidden" name="#name" value="#url">
            <a href="javascript:void(0)" data-url="#url">#name</a>
            <i class="ri-delete-bin-7-line me-2" data-action="delete" style="cursor: pointer" title="{{ __('admin.ui.xoa_tap_tin_name', ['name' => '#name']) }}"></i>
        </td>
    </tr>
</template>
{{--/ Template for row --}}

{{-- Optional content --}}
{{ $slot }}
