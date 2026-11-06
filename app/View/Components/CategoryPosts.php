<?php

namespace App\View\Components;

use App\DataProviders\TaxonomyDataProvider;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Taxonomy;
use Illuminate\View\Component;
use App\Enums\Taxonomy as EnumsTaxonomy;

class CategoryPosts extends Component
{
    public $slug;
    public $typeRender;
    public $posts;
    public $category;
    /**
     * Khởi tạo component với slug của category
     *
     * @param  string  $slug
     */
    public function __construct($slug,$typeRender)
    {
        $this->slug = $slug;
        $this->typeRender = $typeRender;
        $this->posts = $this->getPostsByCategory($slug);
        $this->category = $this->getCate($slug);
        // $this->taxonomyDataProvider = $taxonomyDataProvider;
    }

    /**
     * Lấy 10 bài viết theo slug của category.
     *
     * @param  string  $slug
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getPostsByCategory($slug)
    {
        $category =$this->getCate($slug);
        return $category ? $category->posts()->where('status', PostStatus::PUBLISHED->value)->orderBy('published_at','desc')->latest()->take(10)->get() : collect();
    }

    private function getCate($slug)
    {
        $category = Taxonomy::whereHas('term' ,function($builder) use ($slug)  {
            $builder->where('slug', $slug);
           
        })->where('taxonomy',EnumsTaxonomy::CATEGORY->value)->first();
        return $category;
    }
    
    /**
     * Hiển thị view của component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('components.category-posts',
            ['category'=> $this->category]);
    }
}
