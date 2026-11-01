<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the crossword tool page', function () {
    $this->get(route('tools.crossword'))
        ->assertOk()
        ->assertSee('data-crossword-words', false)
        ->assertSee('Regenerate')
        ->assertSee('Xuất ra file PNG')
        ->assertSee('<footer', false);
});
