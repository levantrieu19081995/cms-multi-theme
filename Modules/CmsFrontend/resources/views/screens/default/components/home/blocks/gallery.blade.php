{{-- Thư viện hình ảnh (tách từ cụm Thư viện ảnh và video). --}}
<section class="cms-section cms-screen-group cms-gallery cms-home-section cms-home-section--cream" aria-label="{{ __('frontend.ui.thu_vien_hinh_anh') }}">
    @include('cmsfrontend::screens.components.home.section-heading', ['heading' => __('pages.thu_vien_hinh_anh'), 'moreUrl' => \Modules\CmsBackend\Support\ModuleUrl::route('cms.hinh-anh.danh-sach')])
    @include(($homeTheme ?? app(\Modules\CmsFrontend\Services\HomeTheme::class))->view('gallery'))
</section>
