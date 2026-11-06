{{-- Staggered reveal for the admin dashboard and side menu. All logic and styles live in CmsFrontend effects/dashboard-reveal; this file only loads them.
     The script is a plain blocking script on purpose: it must put .cms-motion on <html> before the first paint, a deferred module would flash the content. --}}
@vite('Modules/CmsFrontend/resources/assets/effects/dashboard-reveal/dashboard-reveal.css')
<script src="{{ \Illuminate\Support\Facades\Vite::asset('Modules/CmsFrontend/resources/assets/effects/dashboard-reveal/dashboard-reveal.js') }}"></script>
