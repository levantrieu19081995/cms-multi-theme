<?php

namespace Modules\CmsBackend\Models;

use Illuminate\Database\Eloquent\Model;

class EditorialMeta extends Model
{
    protected $table = 'cms_editorial_meta';
    protected $primaryKey = 'post_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['event_starts_at' => 'datetime', 'event_ends_at' => 'datetime', 'revision' => 'integer', 'approved_revision' => 'integer'];
}
