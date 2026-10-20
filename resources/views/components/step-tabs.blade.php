{{--
    Lesson stepper: a numbered circle per step with its label underneath, joined by a line (green once the previous step
    is done). `steps` is a list of labels or ['label' => ..., 'href' => ..., 'number' => ...]; `active` is the current 0-based index; `start` is the number of the first step,
    the others following on, unless a step gives its own `number` (when some steps are left out).
    Steps before `active` count as done unless `completed` (list of indexes) is given.
--}}
@props([
    'steps' => [],
    'active' => 0,
    'completed' => null,
    'label' => 'Các bước',
    'start' => 1,
])

@php
    $isDone = fn (int $index): bool => $completed !== null ? in_array($index, $completed, true) : $index < $active;
    $total = count($steps);
    $stepNumber = fn (int $index): int => is_array($steps[$index] ?? null) && isset($steps[$index]['number']) ? $steps[$index]['number'] : $start + $index;
    $lastNumber = $total > 0 ? $stepNumber($total - 1) : $start;
@endphp

<nav aria-label="{{ $label }}" {{ $attributes->class(['[--r:1.5rem] max-sm:[--r:1.25rem]']) }}>
    <ol class="m-0 grid list-none items-start p-0" style="grid-template-columns: repeat({{ max($total, 1) }}, minmax(0, 1fr))">
        @foreach ($steps as $index => $step)
            @php
                $stepLabel = is_array($step) ? $step['label'] : $step;
                $stepHref = is_array($step) ? ($step['href'] ?? null) : null;
                $isCurrent = $index === $active;
                $isStepDone = ! $isCurrent && $isDone($index);
                $tag = $stepHref ? 'a' : 'button';
            @endphp

            <li class="relative min-w-0">
                @if ($index > 0)
                    <span
                        aria-hidden="true"
                        @class([
                            'absolute h-0.5 rounded-full transition-interactive',
                            'bg-success' => $isDone($index - 1),
                            'bg-line-strong' => ! $isDone($index - 1),
                        ])
                        style="top: calc(0.5rem + var(--r) - 1px); left: calc(-50% + var(--r) + 0.5rem); right: calc(50% + var(--r) + 0.5rem)"
                    ></span>
                @endif

                <{{ $tag }}
                    @if ($stepHref) href="{{ $stepHref }}" @else type="button" @endif
                    @if ($isCurrent) aria-current="step" @endif
                    aria-label="Bước {{ $stepNumber($index) }} trên {{ $lastNumber }}: {{ $stepLabel }}{{ $isStepDone ? ' (đã xong)' : '' }}"
                    class="group flex w-full cursor-pointer flex-col items-center gap-3 rounded-md border-0 bg-transparent px-1 py-2 no-underline transition-interactive hover:no-underline active:translate-y-px"
                >
                    <span aria-hidden="true" @class([
                        'flex size-12 flex-none items-center justify-center rounded-full border-2 text-lg leading-none font-bold transition-interactive max-sm:size-10 max-sm:text-base',
                        'border-secondary bg-secondary text-on-secondary shadow-[0_0_0_5px_var(--color-secondary-bg-hover)]' => $isCurrent,
                        'border-success bg-success text-white' => $isStepDone,
                        'border-line-strong bg-surface-card text-fg-muted group-hover:border-secondary group-hover:text-secondary-text' => ! $isCurrent && ! $isStepDone,
                    ])>
                        @if ($isStepDone)
                            <x-icon name="check" />
                        @else
                            {{ $stepNumber($index) }}
                        @endif
                    </span>
                    <span @class([
                        'max-w-full type-label-lg text-center text-balance max-sm:type-label',
                        'text-secondary-text' => $isCurrent,
                        'text-fg-body' => $isStepDone,
                        'text-fg-muted group-hover:text-fg-body' => ! $isCurrent && ! $isStepDone,
                    ])>{{ $stepLabel }}</span>
                </{{ $tag }}>
            </li>
        @endforeach
    </ol>
</nav>
