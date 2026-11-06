<?php

namespace App\DataProviders;

use App\Models\Posts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Taxonomy;
use App\Models\Terms;
use App\Enums\Taxonomy as EnumsTaxonomy;

class TagDataProvider
{
    /**
     * Undocumented function
     * @param integer $parentId
     * @return void
     */
    public function getTag()
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $tags = Taxonomy::with('term')
            ->where('taxonomy', EnumsTaxonomy::POSTTAG->value)
            ->get();
        return $tags;
    }
    public function paginate( string $filter, int $pageSize = 20) : LengthAwarePaginator
    {
        // Lấy các chuyên mục có taxonomy là 'category' và parent bằng parentId
        $categories = Taxonomy::with('term')
            ->where('taxonomy', EnumsTaxonomy::POSTTAG->value)
            ->whereHas('term',function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('terms.name', 'like', "%$filter%");
                }
            })
            ->paginate($pageSize);

        return $categories;
    }
   
}
