<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('survey.page_title')) | {{ config('cmsfrontend.brand.name', config('app.name')) }}</title>
    @include('cmsfrontend::screens.pages.khao-sat.theme-vars')
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: var(--sv-bg); color: var(--sv-text); font-family: var(--sv-font); line-height: 1.55; }
        .sv-header { background: var(--sv-primary); color: #fff; }
        .sv-header .sv-wrap { display: flex; align-items: center; gap: 12px; padding: 14px 16px; }
        .sv-header a { color: #fff; text-decoration: none; font-weight: 700; }
        .sv-wrap { max-width: 820px; margin: 0 auto; padding: 0 16px; }
        main.sv-wrap { padding-top: 24px; padding-bottom: 48px; }
        .sv-card { background: var(--sv-card); border: 1px solid var(--sv-border); border-radius: var(--sv-radius); padding: 24px; margin-bottom: 20px; }
        h1 { font-size: 1.6rem; margin: 0 0 8px; color: var(--sv-primary); }
        h2 { font-size: 1.2rem; margin: 0 0 4px; color: var(--sv-primary); }
        .sv-muted { color: var(--sv-muted); }
        .sv-btn { display: inline-block; border: 0; border-radius: var(--sv-radius); padding: 11px 22px; font: inherit; font-weight: 600; cursor: pointer; background: var(--sv-primary); color: #fff; text-decoration: none; }
        .sv-btn:hover { background: var(--sv-primary-dark); }
        .sv-btn[disabled] { opacity: .55; cursor: not-allowed; }
        .sv-btn-ghost { background: transparent; color: var(--sv-primary); border: 1px solid var(--sv-primary); }
        .sv-btn-ghost:hover { background: rgba(0, 0, 0, .05); }
        a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible, select:focus-visible { outline: 3px solid var(--sv-accent); outline-offset: 2px; }
        .sv-field { margin-bottom: 14px; }
        .sv-field label.sv-label { display: block; font-weight: 600; margin-bottom: 4px; }
        input[type=text], input[type=tel], input[type=email], textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--sv-border); border-radius: var(--sv-radius); font: inherit; background: #fff; color: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        fieldset.sv-q { border: 0; padding: 0; margin: 0 0 26px; min-width: 0; }
        fieldset.sv-q legend { font-weight: 600; padding: 0; margin-bottom: 6px; }
        .sv-req { color: var(--sv-danger); }
        .sv-opt { display: flex; align-items: flex-start; gap: 10px; padding: 9px 12px; border: 1px solid var(--sv-border); border-radius: var(--sv-radius); margin-bottom: 8px; cursor: pointer; }
        .sv-opt:hover { border-color: var(--sv-primary); }
        .sv-opt input { margin-top: 4px; accent-color: var(--sv-primary); }
        .sv-scale { display: flex; flex-wrap: wrap; gap: 8px; }
        .sv-scale label { min-width: 46px; text-align: center; padding: 8px 6px; border: 1px solid var(--sv-border); border-radius: var(--sv-radius); cursor: pointer; }
        .sv-scale label:has(input:checked), .sv-opt:has(input:checked) { border-color: var(--sv-primary); background: color-mix(in srgb, var(--sv-primary) 8%, #fff); }
        .sv-scale input { position: absolute; opacity: 0; }
        .sv-scale label:has(input:focus-visible) { outline: 3px solid var(--sv-accent); }
        .sv-scale-labels { display: flex; justify-content: space-between; font-size: .85rem; margin-top: 4px; }
        .sv-error { color: var(--sv-danger); font-size: .9rem; margin-top: 4px; }
        .sv-alert { border-radius: var(--sv-radius); padding: 12px 16px; margin-bottom: 16px; }
        .sv-alert-error { background: #fdecea; border: 1px solid #f5c2be; color: #7a1812; }
        .sv-alert-info { background: #fff8e1; border: 1px solid #ffe08a; color: #5c4400; }
        .sv-progress { margin-bottom: 18px; }
        .sv-bar { height: 8px; background: var(--sv-border); border-radius: 99px; overflow: hidden; }
        .sv-bar > span { display: block; height: 100%; width: 0; background: var(--sv-primary); transition: width .25s; }
        .sv-dots { display: flex; gap: 6px; }
        .sv-dots > span { flex: 1; height: 6px; background: var(--sv-border); border-radius: 3px; }
        .sv-dots > span.is-done { background: var(--sv-primary); } .sv-dots > span.is-current { background: var(--sv-accent); }
        .sv-progress-text { font-size: .9rem; margin-top: 6px; color: var(--sv-muted); }
        .sv-js [data-step] { display: none; } .sv-js [data-step].is-current { display: block; }
        .sv-nav { display: flex; justify-content: space-between; gap: 12px; margin-top: 8px; }
        .sv-hp { position: absolute; left: -9999px; }
        footer.sv-footer { text-align: center; color: var(--sv-muted); font-size: .85rem; padding: 0 16px 32px; }
        @media (max-width: 520px) { .sv-card { padding: 16px; } .sv-nav { flex-direction: column-reverse; } .sv-nav .sv-btn { width: 100%; } }
        @media (prefers-reduced-motion: reduce) { .sv-bar > span { transition: none; } }
    </style>
    @yield('head')
</head>
<body>
<header class="sv-header">
    <div class="sv-wrap"><a href="{{ url('/') }}">{{ config('cmsfrontend.brand.name', config('app.name')) }}</a></div>
</header>
<main class="sv-wrap" id="main">
    @yield('content')
</main>
<footer class="sv-footer">&copy; {{ now()->year }} {{ config('cmsfrontend.brand.name', config('app.name')) }}</footer>
@yield('scripts')
</body>
</html>
