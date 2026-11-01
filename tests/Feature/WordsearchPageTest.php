<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the word search tool page', function () {
    $this->get(route('tools.wordsearch'))
        ->assertOk()
        ->assertSee('data-wordsearch-words', false)
        ->assertSee('Kích thước ô chữ')
        ->assertSee('data-wordsearch-size', false)
        ->assertSee('value="11"', false)
        ->assertSee('Regenerate')
        ->assertSee('Xuất ra file PNG')
        ->assertSee('<footer', false);
});
