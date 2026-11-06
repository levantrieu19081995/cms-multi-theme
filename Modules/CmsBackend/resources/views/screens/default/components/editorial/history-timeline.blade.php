@if($history->isNotEmpty())
    @php
        $historyActions = ['save_draft'=>__('admin.ui.luu_nhap'),'save_changes'=>__('admin.ui.bien_tap'),'submit_for_approval'=>__('admin.ui.gui_phe_duyet'),'approved'=>__('admin.ui.phe_duyet'),'reject'=>__('admin.ui.yeu_cau_chinh_sua'),'publish'=>__('admin.ui.dang'),'stop_publishing'=>__('admin.ui.ngung_hien_thi'),'delete'=>__('admin.ui.xoa')];
        $historyStates = [
            'published' => ['published', 'ri-check-line'],
            'approved' => ['approved', 'ri-check-line'],
            'pending_approval' => ['pending', 'ri-time-line'],
            'not_approved' => ['rejected', 'ri-arrow-go-back-line'],
            'stop_publishing' => ['stopped', 'ri-pause-line'],
            'draft' => ['draft', 'ri-save-3-line'],
        ];
    @endphp
    <section class="cms-field-card cms-history" aria-labelledby="cms-history-heading">
        <h2 id="cms-history-heading">{{ __('admin.ui.lich_su_xu_ly') }}</h2>
        <ol class="cms-history-timeline">
            @foreach($history as $entry)
                @php
                    [$historyTone, $historyIcon] = $historyStates[$entry->to_status] ?? ['draft', 'ri-history-line'];
                    $historyTime = \Carbon\Carbon::parse($entry->created_at);
                @endphp
                <li class="cms-history-entry cms-history-{{ $historyTone }}">
                    <span class="cms-history-marker" aria-hidden="true"><i class="{{ $historyIcon }}"></i></span>
                    <div class="cms-history-detail">
                        <time datetime="{{ $historyTime->toIso8601String() }}">{{ $historyTime->format('d/m/Y H:i') }}</time>
                        <dl>
                            <dt>{{ __('admin.ui.nguoi_xu_ly') }}</dt><dd>{{ $entry->actor ?: __('admin.ui.khong_xac_dinh') }}</dd>
                            <dt>{{ __('admin.ui.thao_tac_2') }}</dt><dd>{{ $historyActions[$entry->action] ?? $entry->action }}</dd>
                            <dt>{{ __('admin.ui.trang_thai_2') }}</dt><dd><span class="cms-history-badge">{{ \Modules\CmsBackend\Services\EditorialPolicy::stateLabel($entry->to_status) }}</span></dd>
                            <dt>{{ __('admin.ui.phan_hoi') }}</dt><dd class="cms-history-feedback">@if(filled($entry->note)){{ $entry->note }}@else<em>{{ __('admin.ui.khong_co_phan_hoi') }}</em>@endif</dd>
                        </dl>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif
