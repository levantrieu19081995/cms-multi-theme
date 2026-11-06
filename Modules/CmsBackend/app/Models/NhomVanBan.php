<?php

namespace Modules\CmsBackend\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class NhomVanBan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'qlvb_nhom_van_ban';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'nvb_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nvb_id', 'nvb_name', 'nvb_description','nvb_slug', 'user_id'
    ];

    public function vanBan(): HasMany
    {
        return $this->hasMany(VanBan::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
