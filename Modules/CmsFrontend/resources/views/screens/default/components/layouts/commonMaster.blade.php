<!DOCTYPE html>
<!-- © Trung tâm Khuyến nông và Dịch vụ Nông nghiệp thành phố Cần Thơ. Nghiêm cấm sao chép khi chưa được phép. -->
@php
    $menuFixed =
        $configData['layout'] === 'vertical'
            ? $menuFixed ?? ''
            : ($configData['layout'] === 'front'
                ? ''
                : $configData['headerType']);
    $navbarType =
        $configData['layout'] === 'vertical'
            ? $configData['navbarType']
            : ($configData['layout'] === 'front'
                ? 'layout-navbar-fixed'
                : '');
    $isFront = ($isFront ?? '') == true ? 'Front' : '';
    $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

    $cmsMetaTitle = trim($__env->yieldContent('title', \Modules\CmsFrontend\Support\Brand::name()))
        ?: \Modules\CmsFrontend\Support\Brand::name();
    $cmsMetaDescription = trim(strip_tags($__env->yieldContent('description', \Modules\CmsFrontend\Support\Brand::unit())))
        ?: \Modules\CmsFrontend\Support\Brand::unit();
    // Article pages: the SEO title/description entered for the language of the page (post_translations) win over the defaults.
    $cmsSeoPost = isset($post) && $post instanceof \App\Models\Post ? $post : null;
    $cmsSeoText = $cmsSeoPost?->translation(app()->getLocale());
    if (filled($cmsSeoText?->seo_title)) { $cmsMetaTitle = trim($cmsSeoText->seo_title); }
    if (filled($cmsSeoText?->seo_description)) { $cmsMetaDescription = trim(strip_tags($cmsSeoText->seo_description)); }
    $cmsMetaImage = trim($__env->yieldContent('meta-image', asset(config('cmsfrontend.brand.banner'))))
        ?: asset(config('cmsfrontend.brand.banner'));
    $cmsMetaType = trim($__env->yieldContent('meta-type', 'website')) ?: 'website';
    $cmsMetaUrl = \Modules\CmsBackend\Support\ModuleUrl::url()->current();
@endphp

<html lang="{{ app()->getLocale() }}"
    class="cms-scrollbars-hidden {{ $configData['style'] }}-style {{ $contentLayout ?? '' }} {{ $navbarType ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
    dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}"
    data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{ \Modules\CmsBackend\Support\ModuleUrl::url('/') }}" data-framework="laravel"
    data-template="{{ $configData['layout'] . '-menu-' . $configData['themeOpt'] . '-' . $configData['styleOpt'] }}"
    data-style="{{ $configData['styleOptVal'] }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1" />

    <title>{{ $cmsMetaTitle }} |
        {{ \Modules\CmsFrontend\Support\Brand::name() }}
    </title>
    <meta name="description" content="{{ $cmsMetaDescription }}">
    <meta name="keywords" content="{{ $__env->yieldContent('keywords', __('pages.khuyen_nong_can_tho_nong_nghiep_ky_thuat_san_xua')) }}">
    <meta name="author" content="{{ \Modules\CmsFrontend\Support\Brand::unit() }}">

    <meta name="DC.Title" content="{{ $cmsMetaTitle }}">
    <meta name="DC.Creator" content="{{ \Modules\CmsFrontend\Support\Brand::unit() }}">
    <meta name="DC.Date" scheme="W3CDTF" content="{{ $__env->yieldContent('date', now()->format('Y-m-d\TH:i:s')) }}">
    <meta name="DC.Publisher"
        content="{{ \Modules\CmsFrontend\Support\Brand::unit() }}">
    <meta name="DC.Description" content="{{ $cmsMetaDescription }}">
    <meta name="DC.Identifier" content="{{ $cmsMetaUrl }}">
    <meta name="DC.Language" content="{{ config('locales.dc_language.'.app()->getLocale(), app()->getLocale()) }}">
    {{-- Dữ liệu xem trước khi chia sẻ qua Zalo, Facebook và ứng dụng nhắn tin. --}}
    <meta property="og:locale" content="{{ config('locales.og_locale.'.app()->getLocale(), 'vi_VN') }}">
    <meta property="og:title" content="{{ $cmsMetaTitle }}">
    <meta property="og:description" content="{{ $cmsMetaDescription }}">
    <meta property="og:image" content="{{ $cmsMetaImage }}">
    <meta property="og:image:url" content="{{ $cmsMetaImage }}">
    @if (str_starts_with($cmsMetaImage, 'https://'))
        <meta property="og:image:secure_url" content="{{ $cmsMetaImage }}">
    @endif
    <meta property="og:image:alt" content="{{ $cmsMetaTitle }}">
    @hasSection('meta-image-width')
        <meta property="og:image:width" content="@yield('meta-image-width')">
    @endif
    @hasSection('meta-image-height')
        <meta property="og:image:height" content="@yield('meta-image-height')">
    @endif
    @hasSection('meta-image-type')
        <meta property="og:image:type" content="@yield('meta-image-type')">
    @endif
    <meta property="og:url" content="{{ $cmsMetaUrl }}">
    <meta property="og:type" content="{{ $cmsMetaType }}">
    <meta property="og:site_name"
        content="{{ \Modules\CmsFrontend\Support\Brand::name() }}">
    @hasSection('article-published-time')
        <meta property="article:published_time" content="@yield('article-published-time')">
    @endif
    @hasSection('article-modified-time')
        <meta property="article:modified_time" content="@yield('article-modified-time')">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $cmsMetaTitle }}">
    <meta name="twitter:description" content="{{ $cmsMetaDescription }}">
    <meta name="twitter:image" content="{{ $cmsMetaImage }}">
    <meta itemprop="image" content="{{ $cmsMetaImage }}">
    <link rel="image_src" href="{{ $cmsMetaImage }}">


    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="cms-route-prefix" content="{{ config('cmsbackend.route_prefix') ? '/' . config('cmsbackend.route_prefix') : '' }}">
    <link rel="preload" href="{{ asset('assets/img/khuyen-nong-can-tho/fonts/remixicon/remixicon.woff2') }}"
        as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('assets/fonts/fontawesome/fa-solid-900.woff2') }}"
        as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/img/khuyen-nong-can-tho/fonts/remixicon/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.min.css') }}">
    @include('cmsfrontend::screens.components.layouts.sections.seo-links')

    <link rel="icon" href="{{ \Modules\CmsBackend\Support\SiteLogo::urlOr(config('cmsfrontend.brand.banner')) }}" />

    @include('cmsfrontend::layouts/sections/styles' . $isFront)

    @include('cmsfrontend::screens.components.layouts.scrollbars')
    @vite('Modules/CmsFrontend/resources/assets/css/screens/default/fonts.css')
    @vite('Modules/CmsFrontend/resources/assets/effects/reveal/style.css')
    @vite('Modules/CmsFrontend/resources/assets/effects/stagger/style.css')
    @vite('Modules/CmsFrontend/resources/assets/shared/search-dialog.css')
</head>

<body class="cms-public" style="--cms-brand-image: url('{{ asset(config('cmsfrontend.brand.banner')) }}'); --cms-masthead-image: url('{{ asset('assets/img/khuyen-nong-can-tho/masthead-field.jpg') }}')">
    @yield('layoutContent')
    @include('cmsfrontend::layouts/sections/scripts' . $isFront)
</body>

</html>
