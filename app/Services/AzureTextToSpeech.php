<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns a word into an MP3 with Azure AI Speech text to speech, through its REST API
 * (https://learn.microsoft.com/azure/ai-services/speech-service/rest-text-to-speech).
 *
 * The default voice is en-US-AnaNeural, a child's voice. A cheerful adult one is en-US-JennyNeural with the
 * `cheerful` style (AZURE_SPEECH_VOICE and AZURE_SPEECH_VOICE_STYLE).
 *
 * To keep the audio from getting dull, the voice changes from one record to the next, by its id: the words go through women's and
 * men's voices in turn (wordVoice()), the flashcards through a woman's voice and a child's (flashcardVoice()) and the sentences
 * through a woman's, a man's and a child's (sentenceVoice()).
 */
class AzureTextToSpeech
{
    private const TIMEOUT_SECONDS = 30;

    private const OUTPUT_FORMAT = 'audio-24khz-48kbitrate-mono-mp3';

    /**
     * The adult voices that read a word, keyed by accent, a woman's and a man's in turn.
     *
     * @var array<string, list<string>>
     */
    private const WORD_VOICES = [
        'us' => ['en-US-JennyNeural', 'en-US-GuyNeural', 'en-US-AriaNeural', 'en-US-DavisNeural', 'en-US-JaneNeural', 'en-US-JasonNeural', 'en-US-SaraNeural', 'en-US-TonyNeural'],
        'uk' => ['en-GB-SoniaNeural', 'en-GB-RyanNeural', 'en-GB-LibbyNeural', 'en-GB-ThomasNeural'],
    ];

    private const WOMAN_VOICE = 'en-US-JennyNeural';

    private const MAN_VOICE = 'en-US-GuyNeural';

    /**
     * Whether a Speech key and a region are set.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.azure_speech.key')) && filled(config('services.azure_speech.region'));
    }

    /**
     * A voice of the accent ("us" or "uk"): the one the seed (the id of the word) falls on, so that consecutive words are read by a
     * woman and a man in turn; a random one without a seed.
     */
    public function wordVoice(string $accent, ?int $seed = null): string
    {
        $voices = self::WORD_VOICES[$accent];

        return $seed === null ? Arr::random($voices) : $voices[$seed % count($voices)];
    }

    /**
     * A woman's voice and a child's (the configured voice) in turn, by the seed (the id of the flashcard).
     */
    public function flashcardVoice(int $seed): string
    {
        return [self::WOMAN_VOICE, config('services.azure_speech.voice')][$seed % 2];
    }

    /**
     * A woman's voice, a man's and a child's (the configured voice) in turn, by the seed (the id of the sentence).
     */
    public function sentenceVoice(int $seed): string
    {
        return [self::WOMAN_VOICE, self::MAN_VOICE, config('services.azure_speech.voice')][$seed % 3];
    }

    /**
     * The spoken text as MP3 content, or null when Azure is not configured or the request fails (the failure is logged).
     *
     * @param  string|null  $voice  A voice to read with instead of the configured one (the configured style is then not applied).
     */
    public function synthesize(string $text, ?string $voice = null): ?string
    {
        if (! $this->isConfigured() || blank($text)) {
            return null;
        }

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.azure_speech.key'),
            'X-Microsoft-OutputFormat' => self::OUTPUT_FORMAT,
            'User-Agent' => config('app.name', 'Laravel'),
        ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->withBody($this->ssml($text, $voice), 'application/ssml+xml')
            ->post('https://'.config('services.azure_speech.region').'.tts.speech.microsoft.com/cognitiveservices/v1');

        if ($response->failed() || $response->body() === '') {
            Log::warning('Azure text to speech failed.', ['text' => $text, 'status' => $response->status(), 'error' => mb_substr($response->body(), 0, 200)]);

            return null;
        }

        return $response->body();
    }

    /**
     * The SSML of the text: the voice, a little slower than usual so a child can follow, in the configured style
     * when the configured voice is used.
     */
    private function ssml(string $text, ?string $voice): string
    {
        $style = $voice === null ? config('services.azure_speech.voice_style') : null;
        $voice ??= config('services.azure_speech.voice');
        $locale = implode('-', array_slice(explode('-', $voice), 0, 2));
        $speech = '<prosody rate="-10%">'.htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</prosody>';

        if (filled($style)) {
            $speech = '<mstts:express-as style="'.htmlspecialchars($style, ENT_XML1 | ENT_QUOTES).'">'.$speech.'</mstts:express-as>';
        }

        return '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xmlns:mstts="https://www.w3.org/2001/mstts" xml:lang="'.htmlspecialchars($locale, ENT_XML1 | ENT_QUOTES).'">'
            .'<voice name="'.htmlspecialchars($voice, ENT_XML1 | ENT_QUOTES).'">'.$speech.'</voice></speak>';
    }
}
