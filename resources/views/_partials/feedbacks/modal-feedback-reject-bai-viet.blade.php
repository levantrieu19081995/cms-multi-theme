<!-- Add New Credit Card Modal -->
<div class="modal fade" id="addFeedBackModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered1 modal-simple modal-add-new-cc">
    <div class="modal-content">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body p-0">
        <div class="text-center mb-6">
          <h4 class="mb-2">Phản hồi đến tác giả</h4>
          <p>Nhập lý do không duyệt</p>
        </div>
        <form action="{{ \Modules\CmsBackend\Support\ModuleUrl::route('bai-viet.reject', $post->id) }}" method="POST">
          @csrf
          <div class="col-12 mb-5">
            <div class="input-group input-group-merge">
              <div class="form-floating form-floating-outline">
                <textarea class="form-control h-px-100" id="exampleFormControlTextarea1" name="feedback" placeholder="{{ __('Viết phản hồi') }}"></textarea>
                <label for="modalAddCard">{{ __('Phản hồi') }}</label>
              </div>
              <span class="input-group-text cursor-pointer p-1" id="modalAddCard2"><span class="card-type"></span></span>
            </div>
          </div>
          <div class="col-12 d-flex flex-wrap justify-content-center gap-4 row-gap-4">
            <button type="submit" class="btn btn-primary">Lưu lại</button>
            <button type="reset" class="btn btn-outline-secondary btn-reset" data-bs-dismiss="modal" aria-label="Close">Hủy bỏ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>

</script>

<!--/ Add New Credit Card Modal -->
