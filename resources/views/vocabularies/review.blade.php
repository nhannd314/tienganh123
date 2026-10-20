{{-- Review of a vocabulary (members only): the multiple-choice parts 1 to 3, graded in the browser and never saved. --}}
<x-layouts.app :title="'Ôn tập '.$vocabulary->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="vocab" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Từ vựng', 'href' => route('vocabularies.index')],
        ['label' => $vocabulary->title, 'href' => route('vocabularies.show', $vocabulary)],
        ['label' => 'Ôn tập'],
    ]" />

    <div class="flex flex-wrap items-start gap-5">
        <x-review-quiz
            :title="'Ôn tập '.$vocabulary->title"
            :hint="$hint"
            :step="$step"
            :steps="$steps"
            :active-tab="$activeTab"
            :questions="$questions"
            :next-url="$nextUrl"
            empty-message="Bộ từ vựng này chưa có từ để ôn tập."
            class="flex-[999_1_600px]"
        />

        <x-vocabulary-sidebar :sidebar="$sidebar" class="flex-[1_1_340px]" />
    </div>

    <x-slot:scripts>
        @if ($questions !== [])
            @vite('resources/js/review.js')
        @endif
    </x-slot:scripts>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
