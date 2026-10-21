<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->allowIframesInSanitizedHtml();
    }

    /**
     * Filament's HTML sanitizer drops `<iframe>`, which removes the YouTube block from rendered rich content.
     */
    private function allowIframesInSanitizedHtml(): void
    {
        $this->app->extend(HtmlSanitizerConfig::class, fn (HtmlSanitizerConfig $config): HtmlSanitizerConfig => $config->allowElement(
            'iframe',
            ['src', 'title', 'class', 'loading', 'allow', 'allowfullscreen', 'referrerpolicy'],
        ));
    }
}
