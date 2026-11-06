<!-- Comments -->
<h2 class="blog-inner-title h5">
    {{ __('frontend.ui.binh_luan') }}
</h2>
@include('cmsfrontend::partials/commentsPartial', [
    'comments' => $comments,
    'comment_status' => $post->comment_status,
])
@if($post->comment_status === 'published')
<!-- Comment Form -->
<div id="comments" class="th-comment-form">  
    <div id="respond" class="comment-respond">
        <div class="form-title">
            <h3 class="blog-inner-title h5">
                {{ __('frontend.ui.de_lai_binh_luan') }}
                <small>
                    <a rel="nofollow" id="cancel-comment-reply-link"
                        href="/gain-insights-into-how-parents-can-support-childs-education-at-home-copy/#respond"
                        style="display:none;">{{ __('frontend.ui.huy') }}</a>
                </small>
            </h3>
        </div>

        {{-- <form action="{{ auth() -> user() != null ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.binh-luan-bai-viet.store', $post->id) : \Modules\CmsBackend\Support\ModuleUrl::route('register') }}" method="post"
            id="commentform" class="comment-form" novalidate="">
            @csrf
            <input type="hidden" name="post_id" value="{{ $post->id }}"/>
            <p class="text-theme-tin-tuc mb-4">Email của bạn sẽ không được hiển thị công khai. Các
                trường
                bắt buộc được đánh dấu *</p>
            <div class="row">
                <div class="col-md-12 form-group">
                    <textarea rows="10" class="form-control" name="content" placeholder="Viết bình luận *"></textarea>
                    <i class="fa-regular fa-pen-to-square"></i>
                    
                    <x-input-error
                        :messages="$errors->has('content') ? ['Trường viết bình luận không được bỏ trống.'] : []"
                        class="mt-2 text-danger"
                    />
                    
                </div> 
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <input class="form-control" type="text" name="name"
                        placeholder="Họ và tên *" required value="{{ old('name') }}">
                    <i class="fa-regular fa-user"></i>
                    <!-- @error('name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror -->
                </div>
                <div class="col-md-6 form-group">
                    <input class="form-control" type="email" name="email" 
                        placeholder="Địa chỉ email *" required value="{{ old('email') }}">
                    <i class="fa-regular fa-envelope"></i>
                    <!-- @error('email')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror -->
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="custom-checkbox notice mb-20">
                        <input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent"
                            type="checkbox" value="yes">
                        <label for="wp-comment-cookies-consent">
                            Lưu tên, email và trang web của tôi trong trình duyệt này cho lần bình luận
                            tiếp
                            theo.
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 form-group mb-0">
                <button type="submit" name="submit" id="submit" class="th-btn">
                    <span class="btn-text">Gửi bình luận</span>
                </button>

                <!-- <input type="hidden" name="comment_post_ID" value="35" id="comment_post_ID">
                <input type="hidden" name="comment_parent" id="comment_parent" value="0"> -->
            </div>
        </form> --}}

        @if(auth()->check())
        <form action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('cms.binh-luan-bai-viet.store', $post->slug) }}" method="post">
            @csrf
                <input type="hidden" name="post_id" value="{{ $post->id }}"/>
            <p class="text-theme-tin-tuc mb-4">{{ __('frontend.ui.email_cua_ban_se_khong_duoc_hien_thi_cong_khai_c') }}</p>
            <div class="row">
                <div class="col-md-12 form-group">
                    <textarea rows="10" class="form-control" name="content" placeholder="{{ __('frontend.ui.viet_binh_luan') }}"></textarea>
                    <i class="fa-regular fa-pen-to-square"></i>
                    {{-- <x-input-error :messages="$errors->get('content')" class="mt-2" /> --}}
                    {{-- @error('content')
                        <small class="text-danger d-block mt-2">Trường viết bình luận không được bỏ trống.</small>
                    @enderror --}}
                    <x-input-error
                        :messages="$errors->has('content') ? [__('pages.truong_viet_binh_luan_khong_duoc_bo_trong')] : []"
                        class="mt-2 text-danger"
                    />
                    
                </div> 
            </div>

            <div class="row">
                <div class="col-md-6 form-group">
                    <input class="form-control" type="text" name="name"
                        placeholder="{{ __('frontend.ui.ho_va_ten_2') }}" required value="{{ old('name') }}">
                    <i class="fa-regular fa-user"></i>
                    <!-- @error('name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror -->
                </div>
                <div class="col-md-6 form-group">
                    <input class="form-control" type="email" name="email" 
                        placeholder="{{ __('frontend.ui.dia_chi_email') }}" required value="{{ old('email') }}">
                    <i class="fa-regular fa-envelope"></i>
                    <!-- @error('email')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror -->
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="custom-checkbox notice mb-20">
                        <input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent"
                            type="checkbox" value="yes">
                        <label for="wp-comment-cookies-consent">
                            {{ __('frontend.ui.luu_ten_email_va_trang_web_cua_toi_trong_trinh_d') }}
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 form-group mb-0">
                <button type="submit" name="submit" id="submit" class="th-btn">
                    <span class="btn-text">{{ __('frontend.ui.gui_binh_luan') }}</span>
                </button>

                <!-- <input type="hidden" name="comment_post_ID" value="35" id="comment_post_ID">
                <input type="hidden" name="comment_parent" id="comment_parent" value="0"> -->
            </div>
        </form>
        @else
        <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('login') }}" class="th-btn">{{ __('frontend.ui.dang_nhap_de_binh_luan') }}</a>
        @if(Route::has('register'))
            <a href="{{ \Modules\CmsBackend\Support\ModuleUrl::route('register') }}" class="th-btn">{{ __('frontend.ui.dang_ky') }}</a>
        @endif
        @endif


    </div>
</div>
@endif