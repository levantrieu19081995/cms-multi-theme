{{-- <table class="table table-bordered table-striped table-bai-viet" id="table-img">
  <thead>
      <tr>
        <th style="width:5%">{{ __('stt') }}</th>
          <th>{{ __('Thông tin thư viện hình ảnh') }}</th>
          <th>{{ __('Người tạo') }}</th>
          <th>{{ __('Hành động') }}</th>
      </tr>
  </thead>
  <tbody class="table-border-bottom-0">
      @if (!isset($posts) || $posts->isEmpty())
          <tr>
              <td colspan="5" class="text-center">Không tìm thấy dữ liệu</td>
          </tr>
      @else
          @foreach ($posts as $index => $post)
              <tr>
                  <th scope="row" class="text-center">{{ $index + 1 }}</th>
                  <td class="td-thong-tin-bai-viet">
                      <p class="h6"><b><a href="{{ route('hinh-anh.edit', $post) }}">{{ $post->title }}</a></b></p>
                      <p><i class="ri-calendar-event-line"></i> {{ $post->published_at ? 'Ngày xuất bản: '.$post->published_at : 'Ngày tạo: '.$post->created_at }}</p>
                      <p><i class="ri-information-line"></i> Trạng thái:
                          @if ($post->status == App\Enums\PostStatus::DRAFT->value)
                              <span class="badge bg-label-secondary">{{ App\Enums\PostStatus::DRAFT->label() }}</span>
                          @elseif ($post->status == App\Enums\PostStatus::PENDING_APPROVAL->value)
                              <span class="badge bg-label-info">{{ App\Enums\PostStatus::PENDING_APPROVAL->label() }}</span>
                          @elseif ($post->status == App\Enums\PostStatus::APPROVED->value)
                              <span class="badge bg-label-primary">{{ App\Enums\PostStatus::APPROVED->label() }}</span>
                          @elseif ($post->status == App\Enums\PostStatus::PUBLISHED->value)
                              <span class="badge bg-label-success">{{ App\Enums\PostStatus::PUBLISHED->label() }}</span>
                          @elseif ($post->status == App\Enums\PostStatus::NOT_APPROVED->value)
                              <span class="badge bg-label-danger">{{ App\Enums\PostStatus::NOT_APPROVED->label() }}</span>
                          @elseif ($post->status == App\Enums\PostStatus::STOP_PUBLISHING->value)
                              <span class="badge bg-label-danger">{{ App\Enums\PostStatus::STOP_PUBLISHING->label() }}</span>
                          @endif
                      </p>
                      <p><i class="ri-numbers-line"></i> Số lượng hình ảnh: {{  $post->getMedia('images')->count() }}</p>
                  </td>
                  <td>{{ $post->author ? $post->author->name : '-' }}</td>
                  <td class="text-center">
                      <div class="gap-50">
                          <a href="{{ route('hinh-anh.edit', $post) }}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-toggle="tooltip" title="{{ __('Sửa') }}">
                              <i class="ri-edit-box-line ri-20px"></i>
                          </a>
                          <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                              <i class="ri-more-2-line ri-20px"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-end m-0">
                              <a href="{{ route('hinh-anh.edit', $post) }}" class="dropdown-item">
                                  <i class="ri-edit-box-line me-2"></i><span>{{ __('Sửa') }}</span>
                              </a>
                            
                              <a href="javascript:void(0)" class="dropdown-item delete-record"
                                 data-action="menu-delete"
                                 data-id="{{ $post->id }}"
                                 data-route="{{ route('hinh-anh.destroy', $post) }}">
                                  <i class="ri-delete-bin-7-line me-2"></i><span>{{ __('Xóa') }}</span>
                              </a>
                          </div>
                      </div>
                  </td>
              </tr>
          @endforeach
      @endif
  </tbody>
</table>

<div class="form-layout-footer" style="margin-top: 20px; margin-right: 20px; margin-left: 20px;">
  {{ $posts->onEachSide(2)->links('layouts.paginate') }}
</div> --}}
@include('_partials.bai-viet.table-bai-viet', ['mode' => 'normal', 'type' => 'image', 'route' => 'hinh-anh'])