<?php

namespace Modules\CmsBackend\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VanBan extends Model implements HasMedia
{
    use \Modules\CmsBackend\Support\UsesSourceMedia;
    use HasFactory, SoftDeletes;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'qlvb_van_ban';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'vb_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lvb_id',
        'vb_so_hieu',
        'vb_trich_yeu',
        'vb_ngay_ban_hanh',
        'vb_file_url',
        'vb_ngay_hieu_luc',
        'vb_thoi_gian_luu_tru',
        'vb_lan_ban_hanh',
        'vb_ngay_cong_bo',
        'vb_co_quan_ban_hanh',
        'user_id',
        'nvb_id'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vb_ngay_ban_hanh' => 'date',
            'vb_ngay_hieu_luc' => 'date',
            'vb_ngay_cong_bo' => 'date',
        ];
    }

    public function loaiVanBan(): HasOne
    {
        return $this->hasOne(LoaiVanBan::class, 'lvb_id', 'lvb_id');
    }

    public function nhomVanBan(): HasOne
    {
        return $this->hasOne(NhomVanBan::class, 'nvb_id', 'nvb_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
