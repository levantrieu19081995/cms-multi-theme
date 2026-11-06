<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Models;

use Illuminate\Database\Eloquent\Model;

/** Project that owns a post (table post_projects, one row per post). */
class PostProject extends Model
{
    protected $table = 'post_projects';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    protected $fillable = ['post_id', 'project_key'];
}
