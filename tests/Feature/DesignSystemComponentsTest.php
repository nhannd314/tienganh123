<?php

it('renders a button as a link when given an href', function () {
    $this->blade('<x-button href="/khoa-hoc" icon-right="arrow-right">Tiếp tục</x-button>')
        ->assertSee('href="/khoa-hoc"', false)
        ->assertSee('icon-arrow-right', false)
        ->assertSee('Tiếp tục')
        ->assertDontSee('type="button"', false);
});

it('renders a loading button with a spinner instead of its left icon', function () {
    $this->blade('<x-button icon-left="check" loading>Lưu</x-button>')
        ->assertSee('aria-busy="true"', false)
        ->assertSee('icon-loader-circle', false)
        ->assertDontSee('icon-check', false);
});

it('renders an icon button with an accessible label and pressed state', function () {
    $this->blade('<x-icon-button icon="bookmark" label="Lưu từ" :active="true" />')
        ->assertSee('aria-label="Lưu từ"', false)
        ->assertSee('aria-pressed="true"', false)
        ->assertSee('icon-bookmark', false);
});

it('caps the badge counter at 9+ and hides it when empty', function () {
    $this->blade('<x-badge :count="12"><span>Chuông</span></x-badge>')
        ->assertSee('9+');

    $this->blade('<x-badge :count="0"><span>Chuông</span></x-badge>')
        ->assertDontSee('thông báo mới');
});

it('uses the alert role for danger alerts and status for the rest', function () {
    $this->blade('<x-alert tone="danger" title="Chưa đúng">Thử lại nhé!</x-alert>')
        ->assertSee('role="alert"', false)
        ->assertSee('icon-circle-x', false)
        ->assertSee('Thử lại nhé!');

    $this->blade('<x-alert tone="success" title="Chính xác!" />')
        ->assertSee('role="status"', false);
});

it('renders a real checkbox input that keeps its attributes', function () {
    $this->blade('<x-checkbox name="remember" checked />')
        ->assertSee('type="checkbox"', false)
        ->assertSee('name="remember"', false)
        ->assertSee('checked', false);
});

it('marks a wrong text input as invalid and links its hint', function () {
    $view = $this->blade('<x-text-input name="answer" label="Điền từ tiếng Anh" state="wrong" hint="Đáp án: apple" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="hint-', false)
        ->assertSee('icon-circle-x', false)
        ->assertSee('Đáp án: apple');
});

it('passes search input attributes to the input element', function () {
    $this->blade('<x-search-input name="q" value="apple" class="w-80" />')
        ->assertSee('name="q"', false)
        ->assertSee('value="apple"', false)
        ->assertSee('w-80', false);
});

it('highlights the active nav bar item', function () {
    $items = [
        ['id' => 'home', 'label' => 'Trang chủ', 'icon' => 'house', 'href' => '/'],
        ['id' => 'courses', 'label' => 'Khóa học', 'icon' => 'book-open', 'href' => '/khoa-hoc'],
    ];

    $this->blade('<x-nav-bar :items="$items" active="courses" />', ['items' => $items])
        ->assertSeeInOrder(['Trang chủ', 'aria-current="page"', 'Khóa học'], false)
        ->assertDontSee('Thông báo')
        ->assertSee('Học Tiếng Anh — Trang chủ')
        ->assertSee('<svg viewBox="0 -6 94 94"', false);
});

it('renders breadcrumbs with the last item as the current page', function () {
    $items = [
        ['label' => 'Khóa học', 'href' => '/khoa-hoc'],
        ['label' => 'Ôn tập mở đầu Hello'],
    ];

    $this->blade('<x-breadcrumbs :items="$items" />', ['items' => $items])
        ->assertSee('href="/khoa-hoc"', false)
        ->assertSeeInOrder(['aria-current="page"', 'Ôn tập mở đầu Hello'], false);
});

it('marks earlier steps as done and the active step as current', function () {
    $steps = ['Trả lời nghĩa', 'Nghe và trả lời nghĩa', 'Điền từ tiếng Anh'];

    $this->blade('<x-step-tabs :steps="$steps" :active="1" />', ['steps' => $steps])
        ->assertSee('Bước 1 trên 3: Trả lời nghĩa (đã xong)')
        ->assertSee('aria-current="step"', false)
        ->assertDontSee('Bước 3 trên 3: Điền từ tiếng Anh (đã xong)');
});

it('reveals the answer card result to screen readers', function () {
    $this->blade('<x-answer-card state="correct">(n) quả táo</x-answer-card>')
        ->assertSee('đáp án đúng')
        ->assertSee('icon-check', false);

    $this->blade('<x-answer-card selected>(n) con kiến</x-answer-card>')
        ->assertSee('aria-pressed="true"', false)
        ->assertDontSee('đáp án');
});

it('renders a flashcard with a listen button for the word', function () {
    $this->blade('<x-flashcard word="apple" phonetic="/ˈæp.əl/" meaning="(n) quả táo" tone="secondary" />')
        ->assertSee('data-audio-button', false)
        ->assertSee('data-text="apple"', false)
        ->assertSee('(n) quả táo');
});

it('renders a lesson card linking to its lesson', function () {
    $this->blade('<x-lesson-card title="Hello" href="/bai-hoc/hello" description="Chào hỏi" />')
        ->assertSee('href="/bai-hoc/hello"', false)
        ->assertSee('Chào hỏi');
});

it('clamps the progress bar width between 0 and 100 percent', function () {
    $this->blade('<x-progress-bar :value="15" :max="10" label="Từ vựng" />')
        ->assertSee('width: 100%', false)
        ->assertSee('aria-valuenow="15"', false);
});

it('renders progress ring, stat row, tip card, speech bubble and word prompt', function () {
    $this->blade('<x-progress-ring :value="80" />')->assertSee('Hoàn thành 80%')->assertSee('<div class="type-body-sm text-fg-body">Hoàn thành</div>', false);
    $this->blade('<x-progress-ring :value="80" :caption="false" />')->assertSee('Hoàn thành 80%')->assertDontSee('<div class="type-body-sm text-fg-body">Hoàn thành</div>', false);
    $this->blade('<x-stat-row icon="book-a" label="Số câu trả lời đúng" :value="8" :max="10" done />')
        ->assertSee('8/10')
        ->assertSee('Đã hoàn thành');
    $this->blade('<x-tip-card>Đọc to từ mới giúp bạn nhớ lâu hơn đó!</x-tip-card>')->assertSee('Mẹo học nhanh');
    $this->blade("<x-speech-bubble tail=\"right\">Let's practice!</x-speech-bubble>")->assertSee('right-5', false);
    $this->blade('<x-word-prompt word="quickly" phonetic="/ˈkwɪkli/" />')
        ->assertSee('quickly')
        ->assertSee('/ˈkwɪkli/');
});

it('renders card and pill tones', function () {
    $this->blade('<x-card tone="accent" padding="lg">Nội dung</x-card>')->assertSee('bg-surface-quiz', false);
    $this->blade('<x-pill tone="success" icon="circle-check" size="sm">Điểm: 8</x-pill>')
        ->assertSee('icon-circle-check', false)
        ->assertSee('Điểm: 8');
});

it('shows every component on the demo page', function () {
    $this->get(route('demo'))
        ->assertOk()
        ->assertSee('FunEnglish Design System')
        ->assertSeeInOrder(['id="foundations"', 'id="core"', 'id="forms"', 'id="feedback"', 'id="navigation"', 'id="learning"'], false);
});
