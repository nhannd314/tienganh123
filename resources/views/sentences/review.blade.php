{{-- Review of a sentence set (members only): the sentence parts 7 to 9 it has sentences for, graded in the browser and never saved. --}}
<x-layouts.app :title="'Ôn tập '.$sentenceSet->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="sentences" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Câu mẫu', 'href' => route('sentences.index')],
        ['label' => $sentenceSet->title, 'href' => route('sentences.show', $sentenceSet)],
        ['label' => 'Ôn tập'],
    ]" />

    <div class="flex flex-wrap items-start gap-5">
        <x-review-quiz
            :title="'Ôn tập '.$sentenceSet->title"
            :hint="$hint"
            :step="$step"
            :steps="$steps"
            :active-tab="$activeTab"
            :questions="$questions"
            :next-url="$nextUrl"
            class="flex-[999_1_600px]"
        />

        <x-sentence-set-sidebar :sidebar="$sidebar" class="flex-[1_1_340px]" />
    </div>

    <x-slot:scripts>
        @vite('resources/js/review.js')
    </x-slot:scripts>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
