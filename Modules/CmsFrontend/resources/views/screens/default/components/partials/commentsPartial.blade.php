@if ($comments->count() > 0)
<div class="panel-comments w-100">
    <div class="panel-body">
        @foreach ($comments as $index => $comment)
        <div id="comment-45 " class="th-post-comment px-0">
            <div class="comment-avater">
                <a class="media-left" href="#">
                    @if ($comment->user != null)
                        <x-theme::avatar alt="Avatar" class="img-circle img-sm" :user="$comment->user" />
                    @else
                        <img src="https://secure.gravatar.com/avatar/?s=96&amp;d=mm&amp;r=g"
                            alt="?s=96&amp;d=mm&amp;r=g">
                    @endif
                </a>
            </div>
            <div class="comment-content">
                <div class='p-4  rounded-4 shadow-sm' style='background: #F5F8FC;'>
                    @if ($comment->user != null)
                        <h5 class="mb-0">{!! $comment->user->name !!}</h5>
                    @else
                        <h5 class="mb-0">{{ __('frontend.ui.an_danh') }}</h5>
                    @endif
                    <p class="fs-5">{!! $comment->content !!}</p>
                    <span class="commented-on d-flex align-items-center gap-1">
                        <i class="ri-time-line"></i>
                        {{ \Carbon\Carbon::parse($comment->created_at)->format('H:i - d/m/Y') }}
                    </span>
                    <!-- Hiển thị ở tablet, desktop -->
                    <div class="reply_and_edit p-4 ">
                        <a type="button" class="text-primary" data-bs-toggle="collapse"
                            data-bs-target="#cmt-{{ $comment->id }}" aria-controls="cmt-{{ $comment->id }}"
                            aria-expanded="false">
                            <i class="fas fa-reply"></i> {{ __('frontend.ui.phan_hoi') }}
                        </a>
                    </div>
                    <!-- Chỉ hiển thị ở mobile -->
                    <div class="pad-ver">
                        <a type="button" class="text-primary" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#cmt-{{$comment->id}}" aria-controls="cmt-{{$comment->id}}"
                            aria-expanded="false" >
                            {{ __('pages.phan_hoi') }}
                        </a>
                    </div>
                </div>
                @if($comment_status === 'published')
                <div class="mt-2 collapse p-4  rounded-4 shadow-sm" style='background: #F5F8FC;' 
                    id="cmt-{{$comment->id}}">
                    <form method="POST" action="{{ auth() -> user() != null ? \Modules\CmsBackend\Support\ModuleUrl::route('cms.binh-luan-bai-viet.store', $post->id) : \Modules\CmsBackend\Support\ModuleUrl::route('register') }}">
                        @csrf
                        <input type="hidden" name="post_id" value="{{ $post->id }}" />
                        <input type="hidden" name="parent_id" value="{{$comment->id}}" />
                        <textarea class="form-control" placeholder="{{ __('frontend.ui.nhap_y_kien_cua_ban') }}" name="content" minlength="5"></textarea>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('frontend.ui.gui') }}</button>
                        </div>
                    </form>
                </div>
                @endif
                @if (sizeof($comment->replies)>0)
                <div>
                    <a type="button" class="text-primary" style="margin-top:12px"
                        data-bs-toggle="collapse" id="btnTraLoi{{$comment->id}}"
                        data-bs-target="#xemthem-{{$comment->id}}" aria-controls="xemthem-{{$comment->id}}"
                        aria-expanded="false">
                        <i class="ri-corner-down-right-line"></i>{{sizeof($comment->replies)}} {{ __('pages.phan_hoi') }}
                    </a>
                    <div class="collapse" id="xemthem-{{$comment->id}}">
                        @include('cmsfrontend::partials/commentsPartial', [
                            'comments' => $comment->replies, 
                            'post' => $post
                        ])
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the button and collapse element
        var toggleButton = document.getElementById('btnTraLoi{{$comment->id}}');
        var collapseContent = document.getElementById('xemthem-{{$comment->id}}');

        // Listen for the show.bs.collapse event to hide the button when the content is shown
        // collapseContent.addEventListener('show.bs.collapse', function() {
        //     toggleButton.style.display = 'none'; // Hide the button
        // });

        // Listen for the hide.bs.collapse event to show the button when the content is hidden
        collapseContent.addEventListener('hide.bs.collapse', function() {
            toggleButton.style.display = 'inline-block'; // Show the button
        });

    });
</script>
<style>
    .th-post-comment {
        width: 100%;
    }

    .th-comments-wrap .th-post-comment {
        margin-bottom: 5px;
        padding: 10px;
    }

    .th-comments-wrap .comment-content p:last-of-type {
        margin-bottom: 0px;
        margin-top: 5px;
    }

    .pad-ver {
        display: none;
    }

    .th-comments-wrap .commented-on {
        margin-bottom: 0px;
    }
    
    @media (max-width: 480px) {
        .comment-avater {
            display: none;
        }

        .reply_and_edit {
            display: none;
        }

        .pad-ver {
            display: block;
        }
    }

    @media (max-width: 608px) {
        /* .comment-avater {
            display: none;
        } */

        .reply_and_edit {
            display: none;
        }

        .pad-ver {
            display: block;
        }
    }

</style>
@endif