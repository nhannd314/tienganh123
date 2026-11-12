<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlideDeckRequest;
use App\Http\Requests\UpdateSlideDeckRequest;
use App\Models\SlideDeck;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SlideDeckController extends Controller
{
    /**
     * The form to upload slides, and the slideshows the member made with their short links. Members (and admins) only,
     * see EnsureMember.
     */
    public function index(Request $request): View
    {
        return view('slides.index', [
            'decks' => $request->user()->slideDecks()->withCount('slides')->latest()->get(),
            'maxSlides' => SlideDeck::MAX_SLIDES,
        ]);
    }

    /**
     * Save the slides of the form as a slideshow and go back to the list, where its short link is.
     */
    public function store(StoreSlideDeckRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $deck = DB::transaction(function () use ($request, $data): SlideDeck {
            $deck = $request->user()->slideDecks()->create(['code' => SlideDeck::newCode(), 'title' => $data['title']]);

            foreach (array_values($data['slides']) as $position => $slide) {
                $deck->slides()->create([
                    'position' => $position + 1,
                    'image' => $slide['image']->store('slides', 'public'),
                    'text' => filled($slide['text'] ?? null) ? $slide['text'] : null,
                ]);
            }

            return $deck;
        });

        return redirect()->route('slides.index')->with('created_deck', $deck->code);
    }

    /**
     * The form to edit a slideshow. Only its maker may.
     */
    public function edit(Request $request, SlideDeck $slideDeck): View
    {
        abort_unless($slideDeck->user_id === $request->user()->id, 403);

        return view('slides.edit', [
            'deck' => $slideDeck->load('slides'),
            'maxSlides' => SlideDeck::MAX_SLIDES,
        ]);
    }

    /**
     * Save the edited title and slides: slides sent with an `id` are updated (a new image replaces the old one), slides
     * left out are deleted with their image, the others are added. The short link stays the same.
     */
    public function update(UpdateSlideDeckRequest $request, SlideDeck $slideDeck): RedirectResponse
    {
        $data = $request->validated();
        // The validated array is keyed by the form index but not always in order (slides without a new image come last).
        $slides = collect($data['slides'])->sortKeys()->values()->all();
        $staleImages = [];

        DB::transaction(function () use ($slideDeck, $data, $slides, &$staleImages): void {
            $slideDeck->update(['title' => $data['title']]);

            $existing = $slideDeck->slides()->get()->keyBy('id');
            $keptIds = collect($slides)->pluck('id')->filter()->map(fn ($id): int => (int) $id);

            foreach ($existing->except($keptIds->all()) as $removed) {
                $staleImages[] = $removed->image;
                $removed->delete();
            }

            foreach ($slides as $position => $slide) {
                $attributes = [
                    'position' => $position + 1,
                    'text' => filled($slide['text'] ?? null) ? $slide['text'] : null,
                ];

                if (isset($slide['image'])) {
                    $attributes['image'] = $slide['image']->store('slides', 'public');
                }

                if (filled($slide['id'] ?? null)) {
                    $model = $existing[(int) $slide['id']];

                    if (isset($attributes['image'])) {
                        $staleImages[] = $model->image;
                    }

                    $model->update($attributes);
                } else {
                    $slideDeck->slides()->create($attributes);
                }
            }
        });

        Storage::disk('public')->delete($staleImages);

        return redirect()->route('slides.index');
    }

    /**
     * Show the slideshow full screen. Anyone with the link may open it.
     */
    public function show(SlideDeck $slideDeck): View
    {
        return view('slides.show', ['deck' => $slideDeck->load('slides')]);
    }

    /**
     * Delete a slideshow, its link stops working. Only its maker may.
     */
    public function destroy(Request $request, SlideDeck $slideDeck): RedirectResponse
    {
        abort_unless($slideDeck->user_id === $request->user()->id, 403);

        $slideDeck->delete();

        return redirect()->route('slides.index');
    }
}
