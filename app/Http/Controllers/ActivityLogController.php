<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;

// class ActivityLogController extends Controller
// {
//     private $datetimeFormat = 'Y-m-d\TH:i';

//     public function index(Request $request)
//     {
//         $filter = trim($request->filter);
//         $toDate = $request->to_date ?? null;
//         $fromDate = $request->from_date ?? Carbon::now()->subMonth()->format($this->datetimeFormat);
//         $userIds = $request->users ?? [];
//         foreach ($userIds as $index => $userIdEncode) {
//             $userIds[$index] = base64_decode($userIdEncode);
//         }

//         $toDate_ = $toDate ? Carbon::createFromFormat($this->datetimeFormat, $toDate) : Carbon::now();
//         $fromDate_ = Carbon::createFromFormat($this->datetimeFormat, $fromDate);

//         $activities = Activity::orderBy('created_at', 'desc')
//             ->where('created_at', '>=', $fromDate_)
//             ->where('created_at', '<=', $toDate_)
//             ->where(function ($builder) use ($filter, $userIds) {
//                 if ($filter) {
//                     $builder->whereRaw("lower(description) like '%$filter%'")
//                         ->orWhereHas('causer', function ($query) use ($filter) {
//                             $query->whereRaw("lower(name) like '%$filter%'");
//                         });
//                 }
//                 if ($userIds) {
//                     $builder->whereIn('causer_id', $userIds);
//                 }
//             })
//             ->paginate(20);

//         $users = User::all();
//         $users->each(function ($user) use ($userIds) {
//             $user->selected = in_array($user->id, $userIds);
//         });

//         return view('pages.activity.activity-list', compact(['activities', 'fromDate', 'toDate', 'users']));
//     }
// }


class ActivityLogController extends Controller
{
    private string $datetimeFormat = 'Y-m-d\TH:i';

    public function index(Request $request)
    {
        // ---- Đọc và chuẩn hóa input
        $filter   = trim((string) $request->query('filter', ''));
        $fromRaw  = $request->query('from_date');
        $toRaw    = $request->query('to_date');

        // Mặc định: từ 1 tháng trước tới hiện tại
        $fromAt = $fromRaw
            ? Carbon::createFromFormat($this->datetimeFormat, $fromRaw)
            : now()->subMonth();

        $toAt = $toRaw
            ? Carbon::createFromFormat($this->datetimeFormat, $toRaw)
            : now();

        // users[] đang là base64 -> decode và ép int
        $userIds = collect((array) $request->query('users', []))
            ->map(fn($v) => (int) base64_decode($v))
            ->filter()
            ->values()
            ->all();

        // ---- Query
        $activities = Activity::with('causer')
            ->latest('created_at')
            ->whereBetween('created_at', [$fromAt, $toAt])
            ->when($filter !== '', function ($q) use ($filter) {
                $q->where(function ($qq) use ($filter) {
                    // Tránh whereRaw có nội suy chuỗi -> dùng binding
                    $qq->where('description', 'like', "%{$filter}%")
                        ->orWhereHas('causer', fn($s) => $s->where('name', 'like', "%{$filter}%"));
                });
            })
            ->when(!empty($userIds), fn($q) => $q->whereIn('causer_id', $userIds))
            ->paginate(20)
            ->withQueryString();             // 👈 Mang theo toàn bộ query hiện tại

        // Danh sách user + đánh dấu selected
        $users = User::select('id', 'name')->get();
        $users->each(fn($u) => $u->selected = in_array($u->id, $userIds, true));

        // Giá trị đưa lại cho <input type="datetime-local">
        $fromDate = $fromAt->format($this->datetimeFormat);
        $toDate   = $toAt->format($this->datetimeFormat);

        return view('pages.activity.activity-list', compact(
            'activities',
            'fromDate',
            'toDate',
            'users'
        ));
    }
}
