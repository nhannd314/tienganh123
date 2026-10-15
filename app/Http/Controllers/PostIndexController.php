<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PostIndexController extends Controller
{
    private const POSTS_PER_PAGE = 12;

    /**
     * Show the published posts, newest first and paginated, with the categories above them to filter by.
     */
    public function __invoke(Request $request): View
    {
        $categories = Category::query()->whereHas('posts', fn ($query) => $query->published())->orderBy('sort_order')->orderBy('name')->get();
        $activeCategory = $categories->firstWhere('slug', $request->query('danh-muc'));

        $posts = Post::query()
            ->published()
            ->with('category')
            ->when($activeCategory, fn ($query) => $query->whereBelongsTo($activeCategory))
            ->orderByRaw('coalesce(published_at, created_at) desc')
            ->orderByDesc('id')
            ->paginate(self::POSTS_PER_PAGE)
            ->withQueryString();

        return view('posts.index', [
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
        ]);
    }
}
