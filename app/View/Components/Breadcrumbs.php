<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    public $breadcrumbs;

    public function __construct($breadcrumbs = null)
    {
        $defaultBreadcrumbs = [
            ['label' => 'Trang chủ', 'url' => route('frontpage.home')],
        ];

        // Gộp "Trang chủ" với các breadcrumbs tùy chỉnh từ Controller (nếu có)
        $this->breadcrumbs = array_merge($defaultBreadcrumbs, $breadcrumbs);
    }

    public function render()
    {
        return view('components.breadcrumbs');
    }
}
