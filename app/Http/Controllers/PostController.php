<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class PostController extends Controller
{
    private const RELATED_POSTS = 4;

    private const SUGGESTED_COURSES = 3;

    /**
     * Show a published post with the related posts and the latest courses.
     */
    public function __invoke(Post $post): View
    {
        abort_unless(Post::query()->published()->whereKey($post->getKey())->exists(), 404);

        $post->load('category');

        return view('posts.show', [
            'post' => $post,
            'relatedPosts' => $this->relatedPosts($post),
            'courses' => Course::query()->published()->latest()->latest('id')->limit(self::SUGGESTED_COURSES)->get(),
        ]);
    }

    /**
     * Other published posts, the ones of the same category first, then the newest.
     *
     * @return Collection<int, Post>
     */
    private function relatedPosts(Post $post): Collection
    {
        return Post::query()
            ->published()
            ->with('category')
            ->whereKeyNot($post->getKey())
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$post->category_id ?? 0])
            ->orderByRaw('coalesce(published_at, created_at) desc')
            ->orderByDesc('id')
            ->limit(self::RELATED_POSTS)
            ->get();
    }
}
