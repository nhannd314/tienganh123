<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveWordSceneRequest;
use App\Http\Requests\Admin\StoreWordSceneItemRequest;
use App\Models\WordScene;
use App\Models\WordSceneItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * The pages to make a word scene: upload its picture, then draw a rectangle around each object of it and type the word of the
 * object (see resources/js/word-scene-editor.js). The list of the scenes is in the Filament panel.
 */
class WordScenesController extends Controller
{
    public function create(): View
    {
        return view('admin.word-scenes.create');
    }

    public function store(SaveWordSceneRequest $request): RedirectResponse
    {
        $scene = WordScene::create([
            'title' => $request->validated('title'),
            'image' => $request->file('image')->store('word-scenes', 'public'),
        ]);

        return redirect()->route('admin.word-scenes.edit', $scene);
    }

    public function edit(WordScene $wordScene): View
    {
        return view('admin.word-scenes.edit', [
            'wordScene' => $wordScene,
            'items' => $wordScene->items()->with('word')->get()->map(fn (WordSceneItem $item): array => $this->itemPayload($wordScene, $item))->all(),
        ]);
    }

    /**
     * Rename the scene and/or replace its picture. The rectangles drawn on the old picture do not fit the new one, so they are
     * cleared (the words stay and are drawn again by typing them once more).
     */
    public function update(SaveWordSceneRequest $request, WordScene $wordScene): RedirectResponse
    {
        $wordScene->title = $request->validated('title');

        if ($request->hasFile('image')) {
            $previousImage = $wordScene->image;

            $wordScene->image = $request->file('image')->store('word-scenes', 'public');
            $wordScene->items()->update(['x' => null, 'y' => null, 'width' => null, 'height' => null]);

            if (filled($previousImage)) {
                Storage::disk('public')->delete($previousImage);
            }
        }

        $wordScene->save();

        return redirect()->route('admin.word-scenes.edit', $wordScene)->with('status', 'Đã lưu.');
    }

    /**
     * Save the word typed for an object with its rectangle (see WordScene::placeWord) and answer with the item to list.
     */
    public function storeItem(StoreWordSceneItemRequest $request, WordScene $wordScene): JsonResponse
    {
        $item = $wordScene->placeWord($request->validated('word'), [
            'x' => round((float) $request->validated('x'), 4),
            'y' => round((float) $request->validated('y'), 4),
            'width' => round((float) $request->validated('width'), 4),
            'height' => round((float) $request->validated('height'), 4),
        ]);

        return response()->json($this->itemPayload($wordScene, $item));
    }

    public function destroyItem(WordScene $wordScene, WordSceneItem $item): Response
    {
        $item->delete();

        return response()->noContent();
    }

    /**
     * @return array{id: int, word: string, x: float|null, y: float|null, width: float|null, height: float|null, deleteUrl: string}
     */
    private function itemPayload(WordScene $wordScene, WordSceneItem $item): array
    {
        return [
            'id' => $item->id,
            'word' => $item->word->word,
            'x' => $item->x,
            'y' => $item->y,
            'width' => $item->width,
            'height' => $item->height,
            'deleteUrl' => route('admin.word-scenes.items.destroy', [$wordScene, $item]),
        ];
    }
}
