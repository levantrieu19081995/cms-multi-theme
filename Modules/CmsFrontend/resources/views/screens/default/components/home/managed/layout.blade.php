{{-- Trang chủ Khuyến nông theo Homepage Layout Manager ; từng cụm là một partial của theme. --}}
<main class="cms-home cms-container cms-theme-{{ ($homeTheme ?? null)?->key() ?? 'bao-chi' }}" id="main-content">
    <h1 class="cms-sr-only">{{ __('frontend.ui.cong_thong_tin_khuyen_nong_can_tho') }}</h1>
    @renderHomepageZone('hero')
    @renderHomepageZone('before-main')
    @renderHomepageZone('main')
</main>
