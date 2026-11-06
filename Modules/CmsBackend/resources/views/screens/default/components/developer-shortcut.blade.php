@php
    // Hidden shortcut: 5 clicks within 3 seconds on a logo open the Developer password form. Only present when a Developer
    // hash is configured; it grants nothing by itself (the form still needs the password).
    $developerUrl = app(\Modules\CmsBackend\Services\Developer\DeveloperAccess::class)->enabled()
        ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.dashboard.developer-settings')
        : null;
@endphp
@if ($developerUrl)
<script>
    (function () {
        var links = document.querySelectorAll(@js($selector));
        if (!links.length) { return; }
        var URL_DEV = @js($developerUrl), NEEDED = 5, WINDOW_MS = 3000, IDLE_MS = 700, clicks = [], timer = null;
        // A plain click still ends at the logo's normal link, after a short pause (so the next click is not lost to a page
        // reload). Modified clicks (Ctrl, Shift, middle button) are left alone.
        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.shiftKey || event.altKey || event.metaKey) { return; }
                event.preventDefault();
                var now = Date.now();
                clicks = clicks.filter(function (t) { return now - t <= WINDOW_MS; });
                clicks.push(now);
                clearTimeout(timer);
                if (clicks.length >= NEEDED) { clicks = []; window.location.href = URL_DEV; return; }
                timer = setTimeout(function () { clicks = []; window.location.href = link.href; }, IDLE_MS);
            });
        });
    })();
</script>
@endif
