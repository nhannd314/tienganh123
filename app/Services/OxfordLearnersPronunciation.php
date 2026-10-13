<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads the IPA and the MP3 of a word from its Oxford Learner's Dictionaries entry (https://www.oxfordlearnersdictionaries.com).
 *
 * The first pronunciation of the entry is used for each accent: British ("br") and North American ("n_am").
 */
class OxfordLearnersPronunciation
{
    private const BASE_URL = 'https://www.oxfordlearnersdictionaries.com';

    private const TIMEOUT_SECONDS = 20;

    /**
     * The entry's block class of each accent.
     *
     * @var array<string, string>
     */
    private const ACCENT_BLOCKS = [
        'us' => 'phons_n_am',
        'uk' => 'phons_br',
    ];

    /**
     * Whether looking words up is switched on.
     */
    public function isEnabled(): bool
    {
        return (bool) config('services.oxford_learners.enabled');
    }

    /**
     * The accents a word can be fetched in.
     *
     * @return list<string>
     */
    public function accents(): array
    {
        return array_keys(self::ACCENT_BLOCKS);
    }

    /**
     * The IPA (e.g. "/ˈæpl/") and MP3 url of each accent, keyed by accent; an accent the entry lacks is null.
     * Returns null when the dictionary has no entry for the word.
     *
     * @return array<string, array{ipa: string|null, audio: string|null}>|null
     *
     * @throws RequestException when the dictionary is unreachable or answers with an error other than "not found"
     */
    public function lookup(string $word): ?array
    {
        $response = $this->client()->get(self::BASE_URL.'/definition/english/'.rawurlencode(Str::slug($word)));

        if ($response->notFound()) {
            return null;
        }

        $xpath = $this->parse($response->throw()->body());
        $pronunciations = [];

        foreach (self::ACCENT_BLOCKS as $accent => $block) {
            $pronunciations[$accent] = [
                'ipa' => $this->firstValue($xpath, "//div[contains(concat(' ', normalize-space(@class), ' '), ' {$block} ')][1]//span[@class='phon']"),
                'audio' => $this->firstValue($xpath, "//div[contains(concat(' ', normalize-space(@class), ' '), ' {$block} ')][1]//div[@data-src-mp3]/@data-src-mp3"),
            ];
        }

        return collect($pronunciations)->flatten()->filter()->isEmpty() ? null : $pronunciations;
    }

    /**
     * The MP3 bytes at an audio url returned by lookup().
     *
     * @throws RequestException
     */
    public function download(string $url): string
    {
        return $this->client()->get($url)->throw()->body();
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            'Accept-Language' => 'en',
        ])->timeout(self::TIMEOUT_SECONDS);
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    private function firstValue(DOMXPath $xpath, string $query): ?string
    {
        $value = trim((string) $xpath->query($query)?->item(0)?->textContent);

        return $value === '' ? null : $value;
    }
}
