<?php

it('shows the word search tool page', function () {
    $this->get(route('tools.wordsearch'))
        ->assertOk()
        ->assertSee('data-wordsearch-words', false)
        ->assertSee('Regenerate')
        ->assertSee('Xuất ra file PNG');
});
