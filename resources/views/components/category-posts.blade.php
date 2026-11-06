{{-- <div>
 @if($posts->isNotEmpty())
    <h2>Bài viết trong danh mục: {{ $slug }}</h2>
    <ul>
        @foreach($posts as $post)
            <li><a href="{{ route('tin-tuc.chiTietTinTuc', $post->slug) }}">{{ $post->title }}</a></li>
        @endforeach
    </ul>
@else
    <p>Không có bài viết nào trong danh mục này.</p>
@endif

</div> --}}
@if($posts->isNotEmpty())
@if($typeRender ==="12")
<div class="col-sm-12 col-xs-12">
  <div class="card" style="box-shadow: none">
      <div class="card-body">
          <h2 class="mini-title " style="font-size: 26px;">
              <a href="{{ route('tin-tuc.danhSachTinTuc',$category->term->slug) }}">
                  {{ $category->term->name }}
              </a>
          </h2>
      </div>
      <div class="card-body">
         @foreach ($posts as $index => $post) 
        <div class="row news-item with-border">
          <div class="col-md-4 news-image">
            <a href="{{ route('tin-tuc.chiTietTinTuc', $post->slug) }}">
              <img src="{{$post->getFirstMediaUrl('avatars')}}" >
            </a>
          </div>
          <div class="col-md-8 news-content">
            <a href="{{ route('tin-tuc.chiTietTinTuc', $post->slug) }}">
              <h2 class="news-title-short">{{$post->title}} </h2>
              <p class="news-time">{{\Carbon\Carbon::parse($post->published_at)->format('H:i - d/m/Y') }}</p>
            </a>
          </div>
        </div>
      @endforeach
      </div>
    </div>
</div>
@else

@endif
@endif
