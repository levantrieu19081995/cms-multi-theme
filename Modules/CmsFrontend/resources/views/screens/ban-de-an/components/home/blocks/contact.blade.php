{{-- Cụm liên hệ cuối trang (chỉ hiện khi chức năng Liên hệ bật). --}}
@if (app(\Modules\CmsBackend\Features\FeatureRegistry::class)->isEnabled('contact'))
@include('cmsfrontend::screens.components.home.proposal-contact')
@endif
