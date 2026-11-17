{{-- Entry of the Login page. The look is a "login design" (views/auth/designs/<key>/) chosen per project in Developer Settings; the
     controllers (LoginController, RegisteredUserController) stay the same for every project and keep returning this view. --}}
@include(app(\Modules\CmsBackend\Services\LoginDesigns::class)->view())
