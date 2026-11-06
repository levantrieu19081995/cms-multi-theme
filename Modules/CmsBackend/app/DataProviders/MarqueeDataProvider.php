<?php

namespace Modules\CmsBackend\DataProviders;

use Modules\CmsBackend\Models\Marquee;
use Illuminate\Support\Str;

class MarqueeDataProvider extends BaseDataProvider
{
    public function __construct(Marquee $model)
    {
        // Kế thừa BaseDataProvider
        parent::__construct($model);
    }
    public function getDsMarquee($filter, $pageSize = 10)
    {
        return Marquee::with(['user'])
            ->where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('lvb_name',  'like', value: "%$filter%")
                        ->orWhere('nvb_description', 'like', value: "%$filter%");
                }
            })->orderBy('created_at', 'desc')
            ->paginate($pageSize);
    }
     public function create(array $data): Marquee
    {
        return $this->model->create($data);
    }
    public function updateMarquee(array $data, Marquee $marquee): Marquee
    {
        $marquee->fill($data);
        $marquee->save();

        return $marquee;
    }

    public function approveMarquee($id)
{
    $marquee = Marquee::find($id);

    if (!$marquee) {
        return false; // hoặc throw new \Exception("Không tìm thấy Marquee");
    }

    // Nếu đang bật => tắt
    if ($marquee->show_home_page) {
        $marquee->show_home_page = 0;
        return $marquee->save();
    }

    // Nếu đang tắt => bật và tắt các thằng khác
    // $this->disableAllMarquee();

    $marquee->show_home_page = 1;
    return $marquee->save();
}

    public function disableAllMarquee()
    {
        Marquee::query()->update(['show_home_page' => 0]);
    }
}