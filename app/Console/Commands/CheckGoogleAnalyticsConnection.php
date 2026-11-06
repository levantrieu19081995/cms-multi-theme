<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;

class CheckGoogleAnalyticsConnection extends Command
{
    protected $signature = 'analytics:check';
    protected $description = 'Kiểm tra kết nối với Google Analytics';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Lấy dữ liệu lượt truy cập hôm nay
            $analyticsData = Analytics::fetchTotalVisitorsAndPageViews(Period::days(1));
            
            $this->info('Kết nối thành công! Dữ liệu truy cập hôm nay:');
            foreach ($analyticsData as $data) {
                $this->info("Ngày: {$data['date']}, Lượt truy cập: {$data['visitors']}, Lượt xem trang: {$data['screenPageViews']}");
                
            }
        } catch (Exception $e) {
            $this->error('Kết nối không thành công. Lỗi: ' . $e->getMessage());
        }
    }
}
