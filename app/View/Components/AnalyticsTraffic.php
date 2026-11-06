<?php

namespace App\View\Components;

use Illuminate\View\Component;
// use App\Models\SiteVisitStub;
// use Carbon\Carbon;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunRealtimeReportRequest;

class AnalyticsTraffic extends Component
{

    public $todaySessions;
    public $yesterdaySessions;
    public $monthSessions;
    public $totalSessions;
    public $activeUserSessions;

    // public function __construct()
    public function __construct()
    {

        try {
            //  Thiết lập biến môi trường
            $path =
                env('GA4_ENV') === 'prod'
                ? storage_path(env('GA4_CREDENTIALS_PATH_PROD'))
                : storage_path(env('GA4_CREDENTIALS_PATH_DEV'));
            $propertyId = env('GA4_ENV') === 'prod' ? env('GA4_PROPERTY_ID_PROD') : env('GA4_PROPERTY_ID_DEV');
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $path);

            $client = new BetaAnalyticsDataClient();

            //  // Lấy ngày hiện tại và đầu tháng
            $today = date('Y-m-d'); // Ví dụ: 2025-07-16
            $monthStart = date('Y-m-01'); // Ví dụ: 2025-07-01
            $startDate = date('2025-01-01');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            // Số session hôm nay
            $requestToday = new RunReportRequest([
                'property' => 'properties/' . $propertyId,
                'date_ranges' => [
                    new DateRange([
                        'start_date' => $today,
                        'end_date' => $today,
                    ]),
                ],
                'metrics' => [new Metric(['name' => 'sessions'])],
            ]);
            $responseToday = $client->runReport($requestToday);
            $todayRows = $responseToday->getRows();
            $this->todaySessions = count($todayRows) > 0 ? (int) $todayRows[0]->getMetricValues()[0]->getValue() : 1;

            // Số session tháng này
            $requestMonth = new RunReportRequest([
                'property' => 'properties/' . $propertyId,
                'date_ranges' => [
                    new DateRange([
                        'start_date' => $monthStart,
                        'end_date' => $today,
                    ]),
                ],
                'dimensions' => [new Dimension(['name' => 'month'])],
                'metrics' => [new Metric(['name' => 'sessions'])],
            ]);
            $responseMonth = $client->runReport($requestMonth);
            $monthRows = $responseMonth->getRows();
            $this->monthSessions = count($monthRows) > 0 ? (int) $monthRows[0]->getMetricValues()[0]->getValue() : 0;

            //Tổng số session (từ đầu tháng đến hôm nay)
            $requestTotal = new RunReportRequest([
                'property' => 'properties/' . $propertyId,
                'date_ranges' => [
                    new DateRange([
                        'start_date' => $startDate,
                        'end_date' => $today,
                    ]),
                ],
                'metrics' => [new Metric(['name' => 'sessions'])],
            ]);
            $responseTotal = $client->runReport($requestTotal);
            $totalRows = $responseTotal->getRows();
            $this->totalSessions = count($totalRows) > 0 ? (int) $totalRows[0]->getMetricValues()[0]->getValue() : 0;

            //số người dùng hiện tại
            $requestActiveUser = new RunRealtimeReportRequest([
                'property' => 'properties/' . $propertyId,
                'metrics' => [new Metric(['name' => 'activeUsers'])],
            ]);
            $responseActiveUser = $client->runRealtimeReport($requestActiveUser);
            $activeUserRows = $responseActiveUser->getRows();
            $this->activeUserSessions = count($activeUserRows) > 0 ? (int) $activeUserRows[0]->getMetricValues()[0]->getValue() : 1;
        } catch (\Exception $e) {
            //  echo($e->getMessage());
            $this->todaySessions = 'Không thể lấy dữ liệu';
            $this->monthSessions = 'Không thể lấy dữ liệu';
            $this->totalSessions = 'Không thể lấy dữ liệu';
        }


        // $target = new SiteVisitStub();

        // Log timezone hiện tại
        // Log::info('Timezone hiện tại là: ' . Carbon::now()->timezoneName);

        // $this->todaySessions = visits($target)->period('day', 0)->count();
        // // $this->yesterdaySessions = visits($target)->period('day', -2)->count();
        // $this->monthSessions = visits($target)->period('month', 0)->count();
        // $this->allSessions = visits($target)->count();

        // // Lấy thời gian hiện tại theo múi giờ +07:00
        // $now = Carbon::now('Asia/Ho_Chi_Minh');

        // // Debug: In ra thời gian hiện tại để kiểm tra
        // Log::info('Current time (+07:00): ' . $now->toDateTimeString());

        // // Hôm nay: Từ 00:00:00 đến thời gian bản ghi mới nhất (14:37:04 +07:00)
        // $todayStart = $now->copy()->startOfDay();
        // $todayEnd = Carbon::parse('2025-06-06 14:37:04', 'Asia/Ho_Chi_Minh');

        // // Hôm qua: Từ 00:00:00 đến 23:59:59 ngày 05/06/2025
        // $yesterdayStart = $now->copy()->subDay()->startOfDay();
        // $yesterdayEnd = $now->copy()->subDay()->endOfDay();

        // // Tháng này: Từ 01/06/2025 đến thời gian bản ghi mới nhất
        // $monthStart = $now->copy()->startOfMonth();
        // $monthEnd = Carbon::parse('2025-06-06 14:37:04', 'Asia/Ho_Chi_Minh');

        // $ipVisitFilter = fn($q) => $q->where('primary_key', 'LIKE', 'visits:sitevisitstubs_visits_recorded_ips:%');

        // // Hôm nay
        // $this->todaySessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') BETWEEN ? AND ?", [
        //         $todayStart->toDateTimeString(),
        //         $todayEnd->toDateTimeString()
        //     ])
        //     ->count();

        // // Hôm qua
        // $this->yesterdaySessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') BETWEEN ? AND ?", [
        //         $yesterdayStart->toDateTimeString(),
        //         $yesterdayEnd->toDateTimeString()
        //     ])
        //     ->count();

        // // Tháng này
        // $this->monthSessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') BETWEEN ? AND ?", [
        //         $monthStart->toDateTimeString(),
        //         $monthEnd->toDateTimeString()
        //     ])
        //     ->count();

        // // Tổng
        // $this->allSessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->count();

        // $this->request = $request;

        // // Ghi dữ liệu phiên mới
        // $target = new SiteVisitStub();
        // $target->recordVisit($this->request);

        // $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Log::info('Current time (+07:00): ' . $now->toDateTimeString());

        // $todayStart = $now->copy()->startOfDay();
        // $todayEnd = $now;

        // $yesterdayStart = $now->copy()->subDay()->startOfDay();
        // $yesterdayEnd = $now->copy()->subDay()->endOfDay();

        // $monthStart = $now->copy()->startOfMonth();
        // $monthEnd = $now;

        // $ipVisitFilter = fn($q) => $q->where('primary_key', 'LIKE', 'visits:sitevisitstubs_visits_recorded_ips:%');

        // $this->todaySessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') BETWEEN ? AND ?", [
        //         $todayStart->toDateTimeString(),
        //         $todayEnd->toDateTimeString()
        //     ])
        //     ->distinct('session_id')
        //     ->count('session_id');
        // Log::info('Today Sessions: ' . $this->todaySessions);

        // $this->yesterdaySessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') BETWEEN ? AND ?", [
        //         $yesterdayStart->toDateTimeString(),
        //         $yesterdayEnd->toDateTimeString()
        //     ])
        //     ->distinct('session_id')
        //     ->count('session_id');
        // Log::info('Yesterday Sessions: ' . $this->yesterdaySessions);

        // $this->monthSessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->whereRaw("CONVERT_TZ(created_at, '+00:00', '+07:00') BETWEEN ? AND ?", [
        //         $monthStart->toDateTimeString(),
        //         $monthEnd->toDateTimeString()
        //     ])
        //     ->distinct('session_id')
        //     ->count('session_id');
        // Log::info('Month Sessions: ' . $this->monthSessions);

        // $this->allSessions = DB::table('visits')
        //     ->where($ipVisitFilter)
        //     ->distinct('session_id')
        //     ->count('session_id');
        // Log::info('All Sessions: ' . $this->allSessions);
    }

    public function render()
    {
        return view('components.analytics-traffic');
    }
}
