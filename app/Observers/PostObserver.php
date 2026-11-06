<?php

namespace App\Observers;

use App\Enums\PostType;
use App\Models\Post;

class PostObserver
{

    private ObserverHelper $observerHelper;

    public function __construct()
    {
        // $this->observerHelper = new ObserverHelper('Bài viết');
    }

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->checkPostType($post);
        $this->observerHelper->created($post);
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        $this->checkPostType($post);
        $this->observerHelper->updated($post);
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->checkPostType($post);
        $this->observerHelper->deleted($post);
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        $this->checkPostType($post);
        $this->observerHelper->restored($post);
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        $this->checkPostType($post);
        $this->observerHelper->forceDeleted($post);
    }

    private function checkPostType(Post $post)
    {

        if ($post->post_type ===  PostType::POST->value) {
            $this->observerHelper = new ObserverHelper(PostType::POST->label() . ': ' . $post->title);
        } else if ($post->post_type ===  PostType::PAGE->value) {
            $this->observerHelper = new ObserverHelper(PostType::PAGE->label() . ': ' . $post->title);
        } else if ($post->post_type ===  PostType::IMAGE->value) {
            $this->observerHelper = new ObserverHelper(PostType::IMAGE->label() . ': ' . $post->title);
        } else if ($post->post_type ===  PostType::VIDEO->value) {
            $this->observerHelper = new ObserverHelper(PostType::VIDEO->label() . ': ' . $post->title);
        } else if ($post->post_type ===  PostType::BANNER->value) {
            $this->observerHelper = new ObserverHelper(PostType::BANNER->label() . ': ' . $post->title);
        } else if ($post->post_type ===  PostType::EVENT->value) {
            $this->observerHelper = new ObserverHelper(PostType::EVENT->label() . ': ' . $post->title);
        }
    }
}
