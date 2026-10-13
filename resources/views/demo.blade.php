@php
    $navItems = [
        ['id' => 'home', 'label' => 'Trang chủ', 'icon' => 'house', 'href' => '#'],
        ['id' => 'courses', 'label' => 'Khóa học', 'icon' => 'book-open', 'href' => '#'],
        ['id' => 'vocabulary', 'label' => 'Từ vựng', 'icon' => 'book-a', 'href' => '#'],
        ['id' => 'tests', 'label' => 'Bài kiểm tra', 'icon' => 'clipboard-check', 'href' => '#'],
        ['id' => 'games', 'label' => 'Trò chơi', 'icon' => 'gamepad-2', 'href' => '#'],
    ];

    $colorScales = ['blue', 'pink', 'yellow', 'green', 'red'];
    $colorSteps = [50, 100, 200, 500, 600, 700];
    $swatchClasses = [
        'blue' => ['bg-blue-50', 'bg-blue-100', 'bg-blue-200', 'bg-blue-500', 'bg-blue-600', 'bg-blue-700'],
        'pink' => ['bg-pink-50', 'bg-pink-100', 'bg-pink-200', 'bg-pink-500', 'bg-pink-600', 'bg-pink-700'],
        'yellow' => ['bg-yellow-50', 'bg-yellow-100', 'bg-yellow-200', 'bg-yellow-500', 'bg-yellow-600', 'bg-yellow-700'],
        'green' => ['bg-green-50', 'bg-green-100', 'bg-green-200', 'bg-green-500', 'bg-green-600', 'bg-green-700'],
        'red' => ['bg-red-50', 'bg-red-100', 'bg-red-200', 'bg-red-500', 'bg-red-600', 'bg-red-700'],
    ];
    $inkSwatches = ['bg-ink-50', 'bg-ink-100', 'bg-ink-200', 'bg-ink-300', 'bg-ink-400', 'bg-ink-500', 'bg-ink-600', 'bg-ink-700', 'bg-ink-800', 'bg-ink-900'];

    $typeStyles = [
        'type-word' => 'apple',
        'type-h1' => 'Ôn tập mở đầu Hello',
        'type-h2' => 'Tiến độ ôn tập',
        'type-h3' => 'Mẹo học nhanh',
        'type-answer' => '(n) quả táo',
        'type-body-lg' => 'Chọn đáp án đúng cho mỗi câu hỏi nhé!',
        'type-body' => 'Chọn đáp án đúng cho mỗi câu hỏi nhé!',
        'type-body-sm' => 'Chọn đáp án đúng cho mỗi câu hỏi nhé!',
        'type-label-xl' => 'Câu tiếp theo',
        'type-label-lg' => 'Câu tiếp theo',
        'type-label' => 'Câu tiếp theo',
        'type-label-sm' => 'Câu tiếp theo',
        'type-caption' => 'Câu 1 / 5',
    ];

    $sections = [
        'foundations' => 'Nền tảng',
        'core' => 'Core',
        'forms' => 'Forms',
        'feedback' => 'Feedback',
        'navigation' => 'Navigation',
        'learning' => 'Learning',
        'sounds' => 'Âm thanh',
    ];
@endphp

<x-layouts.app title="Design system">
    <x-slot:header>
        <x-nav-bar :items="$navItems" active="courses" :notifications="3" />
    </x-slot:header>

    <div class="grid gap-12">
        <div class="grid gap-4">
            <x-breadcrumbs :items="[['label' => 'Trang chủ', 'href' => '#'], ['label' => 'Design system']]" />
            <h1 class="type-h1 text-fg-strong">FunEnglish Design System</h1>
            <p class="type-body-lg text-fg-muted">Tất cả token và component có trong dự án.</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($sections as $id => $title)
                    <x-button href="#{{ $id }}" variant="soft" size="sm">{{ $title }}</x-button>
                @endforeach
            </div>
        </div>

        {{-- Foundations --}}
        <section id="foundations" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Nền tảng</h2>

            <x-card class="grid gap-6">
                <h3 class="type-h3 text-fg-strong">Màu sắc</h3>
                <div class="grid gap-3">
                    @foreach ($colorScales as $scale)
                        <div class="grid grid-cols-[80px_repeat(6,minmax(0,1fr))] items-center gap-2">
                            <span class="type-label-sm text-fg-body">{{ $scale }}</span>
                            @foreach ($swatchClasses[$scale] as $index => $swatchClass)
                                <div class="grid gap-1">
                                    <div @class(['h-12 rounded-sm border border-line-subtle', $swatchClass])></div>
                                    <span class="type-caption text-fg-muted">{{ $colorSteps[$index] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    <div class="grid grid-cols-[80px_repeat(10,minmax(0,1fr))] items-center gap-2">
                        <span class="type-label-sm text-fg-body">ink</span>
                        @foreach ($inkSwatches as $index => $swatchClass)
                            <div class="grid gap-1">
                                <div @class(['h-12 rounded-sm border border-line-subtle', $swatchClass])></div>
                                <span class="type-caption text-fg-muted">{{ $index === 0 ? 50 : $index * 100 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-card>

            <x-card class="grid gap-4">
                <h3 class="type-h3 text-fg-strong">Typography — Lexend</h3>
                @foreach ($typeStyles as $class => $sample)
                    <div class="grid grid-cols-[140px_1fr] items-baseline gap-4 border-b border-line-subtle pb-3 last:border-0">
                        <code class="type-caption text-fg-muted">{{ $class }}</code>
                        <span @class([$class, 'text-fg-strong'])>{{ $sample }}</span>
                    </div>
                @endforeach
            </x-card>

            <div class="grid gap-6 md:grid-cols-2">
                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Bo góc</h3>
                    <div class="flex flex-wrap items-end gap-4">
                        @foreach (['rounded-xs' => 'xs 6', 'rounded-sm' => 'sm 10', 'rounded-md' => 'md 14', 'rounded-lg' => 'lg 18', 'rounded-xl' => 'xl 24', 'rounded-full' => 'pill'] as $class => $name)
                            <div class="grid justify-items-center gap-2">
                                <div @class(['size-16 border-2 border-primary-border bg-primary-bg', $class])></div>
                                <span class="type-caption text-fg-muted">{{ $name }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Đổ bóng</h3>
                    <div class="flex flex-wrap gap-6 bg-surface-page p-6">
                        @foreach (['shadow-xs', 'shadow-card', 'shadow-raised', 'shadow-halo', 'shadow-focus'] as $class)
                            <div class="grid justify-items-center gap-2">
                                <div @class(['size-16 rounded-md bg-surface-card', $class])></div>
                                <span class="type-caption text-fg-muted">{{ $class }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </section>

        {{-- Core --}}
        <section id="core" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Core</h2>

            <x-card class="grid gap-6">
                <h3 class="type-h3 text-fg-strong">Button</h3>
                <div class="flex flex-wrap items-center gap-4">
                    <x-button>Primary</x-button>
                    <x-button variant="secondary">Secondary</x-button>
                    <x-button variant="accent">Accent</x-button>
                    <x-button variant="outline">Outline</x-button>
                    <x-button variant="soft">Soft</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-button size="sm" icon-left="arrow-left" variant="outline">Câu trước</x-button>
                    <x-button size="md" icon-right="arrow-right">Câu tiếp theo</x-button>
                    <x-button size="lg" icon-right="arrow-right" variant="secondary">Tiếp tục ôn tập</x-button>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-button disabled>Disabled</x-button>
                    <x-button variant="outline" disabled>Disabled</x-button>
                    <x-button loading>Đang lưu</x-button>
                    <x-button href="#" variant="soft" icon-left="link">Dạng link</x-button>
                </div>
                <x-button full-width size="lg" icon-left="check">Hoàn thành</x-button>
            </x-card>

            <div class="grid gap-6 md:grid-cols-2">
                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Icon button</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-icon-button icon="bookmark" label="Lưu từ" />
                        <x-icon-button icon="bookmark" label="Đã lưu" :active="true" />
                        <x-icon-button icon="heart" label="Yêu thích" variant="secondary" />
                        <x-icon-button icon="star" label="Đánh dấu" variant="accent" shape="circle" />
                        <x-icon-button icon="settings" label="Cài đặt" variant="ghost" shape="circle" />
                        <x-icon-button icon="user" label="Tài khoản" variant="soft" shape="circle" size="lg" />
                        <x-icon-button icon="trash-2" label="Xóa" size="sm" disabled />
                    </div>
                </x-card>

                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Badge</h3>
                    <div class="flex flex-wrap items-center gap-6">
                        <x-badge :count="3" inset>
                            <x-icon-button icon="bell" label="Thông báo" variant="ghost" shape="circle" />
                        </x-badge>
                        <x-badge :count="12">
                            <x-icon-button icon="mail" label="Tin nhắn" variant="outline" />
                        </x-badge>
                        <x-badge :count="0">
                            <x-icon-button icon="bell" label="Không có thông báo" variant="outline" />
                        </x-badge>
                    </div>
                </x-card>

                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Pill</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-pill tone="neutral">Câu 1 / 5</x-pill>
                        <x-pill tone="primary" icon="book-open">Unit 1</x-pill>
                        <x-pill tone="secondary">Mới</x-pill>
                        <x-pill tone="success" icon="circle-check">8/10</x-pill>
                        <x-pill tone="warning" icon="star">Điểm: 120</x-pill>
                        <x-pill tone="danger" icon="circle-x">Sai 2 câu</x-pill>
                        <x-pill tone="info" size="sm">Nhỏ</x-pill>
                    </div>
                </x-card>

                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Icon (Lucide)</h3>
                    <div class="flex flex-wrap items-end gap-4 text-primary">
                        <x-icon name="house" size="sm" />
                        <x-icon name="book-open" size="md" />
                        <x-icon name="gamepad-2" size="lg" />
                        <x-icon name="volume-2" size="xl" />
                        <x-icon name="circle-check" size="xl" class="text-success" />
                        <x-icon name="circle-x" size="xl" class="text-danger" />
                        <x-icon name="triangle-alert" size="xl" class="text-warning-text" />
                        <x-icon name="loader-circle" size="xl" spin />
                    </div>
                </x-card>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <x-card>
                    <h3 class="type-h3 text-fg-strong">Card default</h3>
                    <p class="mt-2 type-body-sm text-fg-muted">Nền trắng, viền mảnh và bóng nhẹ.</p>
                </x-card>
                <x-card tone="accent">
                    <h3 class="type-h3 text-fg-strong">Card accent</h3>
                    <p class="mt-2 type-body-sm text-fg-muted">Nền vàng dùng cho khu vực làm bài.</p>
                </x-card>
                <x-card tone="info">
                    <h3 class="type-h3 text-fg-strong">Card info</h3>
                    <p class="mt-2 type-body-sm text-fg-muted">Nền xanh nhạt cho thông tin phụ.</p>
                </x-card>
            </div>
        </section>

        {{-- Forms --}}
        <section id="forms" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Forms</h2>

            <div class="grid gap-6 md:grid-cols-2">
                <x-card class="grid gap-5">
                    <h3 class="type-h3 text-fg-strong">Text input</h3>
                    <x-text-input name="demo_answer" label="Điền từ tiếng Anh" placeholder="Nhập từ..." hint="Gõ từ tiếng Anh có nghĩa là quả táo." />
                    <x-text-input name="demo_correct" label="Đáp án đúng" value="apple" state="correct" hint="Chính xác!" />
                    <x-text-input name="demo_wrong" label="Đáp án sai" value="aple" state="wrong" hint="Đáp án đúng là: apple" />
                    <x-text-input name="demo_disabled" label="Đã khóa" value="apple" disabled />
                </x-card>

                <div class="grid content-start gap-6">
                    <x-card class="grid gap-4">
                        <h3 class="type-h3 text-fg-strong">Search input</h3>
                        <x-search-input name="demo_search" class="w-full" />
                        <x-search-input name="demo_search_pill" shape="pill" class="w-full" />
                        <x-search-input name="demo_search_disabled" class="w-full" disabled />
                    </x-card>

                    <x-card class="grid gap-4">
                        <h3 class="type-h3 text-fg-strong">Checkbox</h3>
                        <div class="flex flex-wrap items-center gap-6">
                            <label class="flex items-center gap-2 type-body"><x-checkbox name="demo_sm" size="sm" /> Nhỏ</label>
                            <label class="flex items-center gap-2 type-body"><x-checkbox name="demo_md" checked /> Vừa</label>
                            <label class="flex items-center gap-2 type-body"><x-checkbox name="demo_lg" size="lg" checked /> Lớn</label>
                            <label class="flex items-center gap-2 type-body text-fg-disabled"><x-checkbox name="demo_disabled" disabled /> Khóa</label>
                            <label class="flex items-center gap-2 type-body text-fg-disabled"><x-checkbox name="demo_disabled_checked" disabled checked /> Khóa</label>
                        </div>
                    </x-card>
                </div>
            </div>
        </section>

        {{-- Feedback --}}
        <section id="feedback" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Feedback</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <x-alert tone="success" title="Chính xác!">Bạn đã trả lời đúng 8/10 câu.</x-alert>
                <x-alert tone="warning" title="Sắp hết giờ">Còn 30 giây để hoàn thành bài.</x-alert>
                <x-alert tone="danger" title="Chưa đúng rồi">Thử lại một lần nữa nhé!</x-alert>
                <x-alert tone="info" title="Mẹo nhỏ">Nghe phát âm trước khi chọn đáp án.</x-alert>
            </div>
        </section>

        {{-- Navigation --}}
        <section id="navigation" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Navigation</h2>

            <x-card class="grid gap-6">
                <h3 class="type-h3 text-fg-strong">Nav bar</h3>
                <p class="type-body-sm text-fg-muted">Thanh điều hướng ở đầu trang này chính là <code>x-nav-bar</code>.</p>

                <h3 class="type-h3 text-fg-strong">Breadcrumbs</h3>
                <x-breadcrumbs :items="[
                    ['label' => 'Khóa học', 'href' => '#'],
                    ['label' => 'Tiếng Anh lớp 3', 'href' => '#'],
                    ['label' => 'Unit 1', 'href' => '#'],
                    ['label' => 'Ôn tập mở đầu Hello'],
                ]" />

                <h3 class="type-h3 text-fg-strong">Step tabs</h3>
                <x-step-tabs :steps="['Trả lời nghĩa', 'Nghe và trả lời nghĩa', 'Điền từ tiếng Anh', 'Hoàn thành']" :active="1" />
                <x-step-tabs :steps="['Trả lời nghĩa', 'Nghe và trả lời nghĩa', 'Điền từ tiếng Anh', 'Hoàn thành']" :active="3" />
            </x-card>
        </section>

        {{-- Learning --}}
        <section id="learning" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Learning</h2>

            <x-card tone="accent" padding="lg" class="grid gap-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <x-word-prompt word="quickly" phonetic="/ˈkwɪkli/" />
                    <x-speech-bubble tail="right">Let's practice!</x-speech-bubble>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <x-answer-card>(adverb) một cách nhanh chóng</x-answer-card>
                    <x-answer-card selected>(adj) chậm chạp</x-answer-card>
                    <x-answer-card state="correct">(adverb) một cách nhanh chóng</x-answer-card>
                    <x-answer-card state="wrong">(n) quả táo</x-answer-card>
                </div>
                <div class="flex flex-wrap justify-between gap-4">
                    <x-button variant="outline" icon-left="arrow-left">Câu trước</x-button>
                    <x-button icon-right="arrow-right">Câu tiếp theo</x-button>
                </div>
            </x-card>

            <div class="grid gap-6 md:grid-cols-2">
                <x-flashcard word="apple" phonetic="/ˈæp.əl/" meaning="(n) quả táo" />
                <x-flashcard word="ant" phonetic="/ænt/" meaning="(n) con kiến" tone="secondary" />
                <x-flashcard word="axe" phonetic="/æks/" meaning="(n) cái rìu" tone="accent" />
                <x-flashcard word="car" phonetic="/kɑːr/" meaning="(n) xe ô tô" tone="success" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <x-lesson-card title="Hello" description="Chào hỏi và giới thiệu bản thân bằng tiếng Anh." />
                <x-lesson-card title="My family" description="Từ vựng về các thành viên trong gia đình." tone="secondary" />
                <x-lesson-card title="Colours" description="Học tên các màu sắc qua trò chơi vui nhộn." tone="accent" />
                <x-lesson-card title="Animals" description="Gọi tên các con vật quen thuộc quanh em." tone="success" />
            </div>

            <div class="grid gap-6 md:grid-cols-[auto_1fr]">
                <x-card class="grid justify-items-center gap-4">
                    <h3 class="type-h3 text-fg-strong">Progress ring</h3>
                    <x-progress-ring :value="80" />
                </x-card>

                <x-card class="grid content-start gap-5">
                    <h3 class="type-h3 text-fg-strong">Tiến độ ôn tập</h3>
                    <x-stat-row icon="circle-check" label="Số câu trả lời đúng" :value="8" :max="10" tone="success" done />
                    <x-stat-row icon="book-a" label="Từ vựng đã học" :value="12" :max="20" />
                    <x-stat-row icon="headphones" label="Bài nghe" :value="3" :max="10" tone="secondary" />
                    <x-stat-row icon="star" label="Điểm thưởng" :value="45" :max="100" tone="accent" />

                    <h3 class="type-h3 text-fg-strong">Progress bar</h3>
                    <x-progress-bar :value="30" label="Nhỏ" />
                    <x-progress-bar :value="60" tone="success" size="md" label="Vừa" />
                    <x-progress-bar :value="90" tone="secondary" size="lg" label="Lớn" />
                </x-card>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <x-tip-card>Đọc to từ mới 3 lần sẽ giúp bạn nhớ lâu hơn đó!</x-tip-card>

                <x-card class="grid gap-4">
                    <h3 class="type-h3 text-fg-strong">Audio button</h3>
                    <div class="flex flex-wrap items-center gap-4">
                        <x-audio-button text="hello" />
                        <x-audio-button text="hello" size="sm" />
                        <x-audio-button text="hello" size="sm" tone="secondary" />
                        <x-audio-button text="hello" size="sm" tone="accent" />
                        <x-audio-button text="hello" size="sm" tone="success" />
                        <x-audio-button text="hello" disabled />
                    </div>
                    <div class="flex flex-wrap gap-6">
                        <x-speech-bubble>Great job!</x-speech-bubble>
                        <x-speech-bubble tail="right">Let's practice!</x-speech-bubble>
                    </div>
                </x-card>
            </div>
        </section>

        {{-- Answer sounds of the lesson review (resources/js/sound-effects.js) --}}
        <section id="sounds" class="grid gap-6">
            <h2 class="type-h2 text-fg-strong">Âm thanh</h2>
            <p class="type-body text-fg-muted">Âm thanh khi chọn đáp án đúng/sai trong phần ôn tập. Bấm để nghe thử.</p>

            <x-card class="grid gap-4">
                @foreach ([
                    'marimba' => 'Đàn gỗ (marimba)',
                    'pluck' => 'Đàn dây gảy (harp)',
                    'bell' => 'Chuông nhỏ',
                    'coin' => 'Trò chơi điện tử (8-bit)',
                    'pop' => 'Bong bóng (pop)',
                ] as $preset => $label)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line-subtle pb-4 last:border-b-0 last:pb-0">
                        <span class="type-label-lg text-fg-strong">{{ $loop->iteration }}. {{ $label }}</span>
                        <div class="flex gap-3">
                            <x-button variant="primary" size="sm" icon-left="circle-check" data-sound-preset="{{ $preset }}" data-sound-kind="correct">Đúng</x-button>
                            <x-button variant="danger" size="sm" icon-left="circle-x" data-sound-preset="{{ $preset }}" data-sound-kind="wrong">Sai</x-button>
                        </div>
                    </div>
                @endforeach
            </x-card>
        </section>
    </div>

    <x-slot:scripts>
        @vite('resources/js/sound-preview.js')
    </x-slot:scripts>
</x-layouts.app>
