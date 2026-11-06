<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use App\Models\Taxonomy;

class Breadcrumbs
{
    public static function generate(array $customBreadcrumbs = [])
    {
        $breadcrumbs = [];
    
        // Nếu Controller truyền vào custom breadcrumbs, sử dụng chúng
        if (!empty($customBreadcrumbs)) {
            foreach ($customBreadcrumbs as $breadcrumb) {
                $breadcrumbs[] = [
                    'label' => $breadcrumb['label'],
                    'url' => $breadcrumb['url'] ?? '#'
                ];
            }
        } else {
            // Nếu không, tự động tạo breadcrumb dựa trên route hiện tại
            $currentRoute = Route::current();
            $currentRouteName = $currentRoute->getName();
            // Mảng ánh xạ tên route sang tên hiển thị tùy chỉnh
            $nameMapping = [
                'frontpage.home' => 'Trang chủ',
                'tin-tuc.danhSachTinTuc' => 'Danh sách tin tức',
                'tin-tuc.chiTietTinTuc' => 'Chi tiết tin tức',
                // Bạn có thể thêm các ánh xạ khác ở đây
            ];
    
            // Thêm breadcrumb cho trang chủ
            $breadcrumbs[] = [
                'label' => $nameMapping['frontpage.home'] ?? 'Trang chủ',
                'url' => route('frontpage.home')
            ];
    
            if ($currentRouteName) {
                $routeParts = explode('.', $currentRouteName);
                $routePath = '';
    
                foreach ($routeParts as $part) {
                    $routePath .= ($routePath ? '.' : '') . $part;
                    $routeLabel = $nameMapping[$routePath] ?? ucfirst(str_replace('_', ' ', $part));
    
                    try {
                        $routeUrl = route($routePath, $currentRoute->parameters());
                        $breadcrumbs[] = ['label' => $routeLabel, 'url' => $routeUrl];
                    } catch (\Exception $e) {
                        // Nếu route không tồn tại hoặc không có tham số thì bỏ qua
                    }
                }
            }
        }
    
        return $breadcrumbs;
    }
    
}
