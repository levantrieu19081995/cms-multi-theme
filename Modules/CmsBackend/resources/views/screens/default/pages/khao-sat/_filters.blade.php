{{-- Bộ lọc dùng chung cho Phản hồi và Thống kê. $action: URL của trang hiện tại; $full: hiện đủ trường (danh sách) hay chỉ khoảng ngày (thống kê). Dùng khuôn cms-filter-panel như các danh sách khác. --}}
<form method="get" action="{{ $action }}" class="cms-filter-panel" role="search">
    @if ($full)
        <label>{{ __('admin.survey_report.search') }}<input type="search" name="q" class="form-control" value="{{ $filters['q'] }}" maxlength="100"></label>
        <label>{{ __('admin.survey_report.status') }}<select name="status" class="form-select">
            <option value="">{{ __('admin.survey_report.all_status') }}</option>
            <option value="completed" @selected($filters['status'] === 'completed')>{{ __('admin.survey_report.completed') }}</option>
            <option value="in_progress" @selected($filters['status'] === 'in_progress')>{{ __('admin.survey_report.in_progress') }}</option>
        </select></label>
        <label>{{ __('admin.survey_report.participant') }}<select name="anonymous" class="form-select">
            <option value="">{{ __('admin.survey_report.all_identity') }}</option>
            <option value="1" @selected($filters['anonymous'] === '1')>{{ __('admin.survey_report.only_anonymous') }}</option>
            <option value="0" @selected($filters['anonymous'] === '0')>{{ __('admin.survey_report.only_named') }}</option>
        </select></label>
    @endif
    <label>{{ __('admin.survey_report.from') }}<input type="date" name="from" class="form-control" value="{{ $filters['from'] }}"></label>
    <label>{{ __('admin.survey_report.to') }}<input type="date" name="to" class="form-control" value="{{ $filters['to'] }}"></label>
    <button type="submit" class="btn btn-outline-primary align-self-end">{{ __('admin.survey_report.filter') }}</button>
    <a class="btn btn-outline-secondary align-self-end" href="{{ $action }}">{{ __('admin.survey_report.reset') }}</a>
</form>
