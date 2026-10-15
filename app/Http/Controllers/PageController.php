<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * Show a published fixed page with the list of the other fixed pages.
     */
    public function __invoke(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('pages.show', [
            'page' => $page,
            'otherPages' => Page::query()->published()->whereKeyNot($page->getKey())->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
}
