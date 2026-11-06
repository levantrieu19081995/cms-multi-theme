<!-- Page Styles -->
@section('page-style')
@vite(Theme::asset(['assets/vendor/scss/pages/front-page-landing.scss', 'assets/vendor/scss/pages/ui-carousel.scss', 'assets/css/main.css']))
@vite(['Modules/CmsFrontend/resources/assets/css/banner.css', 'Modules/CmsFrontend/resources/assets/sass/app.scss', 'Modules/CmsFrontend/resources/assets/css/tin-tuc/chi-tiet-tin-tuc.css', 'resources/assets/css/slick/slick.css', 'resources/assets/css/slick/slick-theme.css'])
@endsection


<!-- Vendor Scripts -->
@section('vendor-script')
@vite(Theme::asset([
'assets/vendor/js/helpers.js',
'assets/vendor/libs/jquery/jquery.js',
'assets/vendor/js/dropdown-hover.js',
'assets/vendor/js/mega-dropdown.js',
'assets/vendor/libs/popper/popper.js',
'assets/vendor/js/bootstrap.js',
'assets/vendor/libs/node-waves/node-waves.js',
'assets/vendor/libs/toastr/toastr.js',
'assets/vendor/libs/sweetalert2/sweetalert2.js']))
@endsection


<!-- Page Scripts -->
@section('page-script')
@vite(['Modules/CmsFrontend/resources/assets/js/app.js'])
@endsection

{{-- filepath: Modules/CmsFrontend/resources/views/pages/frontend/su-kien/partials/danh-sach.blade.php --}}
@props(['eventsToday'])

<aside class="sidebar-area">
    <div id="edura_recent_posts_widget-3" class="widget widget">
        <div>
            <h3 class="widget_title text-center">
                <div style="color: #0d6efd">{{ __('frontend.ui.su_kien_dang_dien_ra_hom_nay') }}</div>
                <div class="fs-5 mt-2">
                    <i class="ri-timer-line"></i>
                    {{ ucfirst(\Carbon\Carbon::now()->isoFormat('dddd')) }},
                    {{ \Carbon\Carbon::now()->format('d/m/Y') }}

                </div>


            </h3>

        </div>

        <div class="recent-post-wrap cardSmall-su-kien" style="  margin-bottom: 0px;">
            @forelse($eventsToday as $event)
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.su-kien.chi-tiet', $event->slug) }}">
                <div class="recent-post card-item {{ !$loop->last ? ' border-bottom' : '' }}">
                    <div class="media-img">
                        <img src={{ $event->getFirstMediaUrl('avatars') ? $event->getFirstMediaUrl('avatars') : '/assets/img/khuyen-nong-can-tho/default-image.jpg' }}
                            alt="blog s 1 2 2 85x85">
                    </div>
                    <div class="media-body">
                        <div class="d-flex flex-column justify-content-between h-100">
                            <div class="card-text text-dark fw-bold fs-5 mb-1">{{ $event->title }}</div>
                            <div class="small text-muted mt-2">
                                {{ \Carbon\Carbon::parse(($event->event_starts_at ?? $event->published_at))->format('H:i d/m/Y') }}
                                -
                                {{ \Carbon\Carbon::parse(($event->event_ends_at ?? $event->publication_date_expires))->format('H:i d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            @empty
            <div class="text-muted">{{ __('frontend.ui.khong_co_su_kien_nao_dang_dien_ra_hom_nay') }}</div>
            @endforelse
        </div>
    </div>
</aside>