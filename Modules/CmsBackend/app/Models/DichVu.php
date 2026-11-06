<?php

namespace Modules\CmsBackend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\CmsBackend\Database\Factories\DichVuFactory;

class DichVu extends Model
{
    protected $table = 'dichvu';
    protected $fillable = [
        'icon',
        'title',
        'link',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
