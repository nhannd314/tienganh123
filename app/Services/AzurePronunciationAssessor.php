<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Scores how well a word was pronounced with Azure AI Speech pronunciation assessment, through the speech to text REST
 * API for short audio (https://learn.microsoft.com/azure/ai-services/speech-service/rest-speech-to-text-short).
 *
 * The audio must be WAV, 16 kHz, 16-bit mono PCM, under 30 seconds.
 */
class AzurePronunciationAssessor
{
    private const TIMEOUT_SECONDS = 15;

    /**
     * Whether a Speech key and a region (or an endpoint) are set.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.azure_speech.key'))
            && (filled(config('services.azure_speech.region')) || filled(config('services.azure_speech.endpoint')));
    }

    /**
     * The pronunciation of `$referenceText` in the recording, from 0 to 10 (Azure's overall PronScore out of 100,
     * rounded to a whole point). Speech that could not be recognised scores 0.
     *
     * @throws RequestException when Azure answers with an error (bad key, bad audio, outage)
     */
    public function assess(string $wav, string $referenceText): int
    {
        $parameters = json_encode([
            'ReferenceText' => $referenceText,
            'GradingSystem' => 'HundredMark',
            'Granularity' => 'Word',
            'Dimension' => 'Comprehensive',
        ]);

        $result = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.azure_speech.key'),
            'Pronunciation-Assessment' => base64_encode($parameters),
            'Accept' => 'application/json',
        ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->withBody($wav, 'audio/wav; codecs=audio/pcm; samplerate=16000')
            ->post($this->endpoint().'?'.http_build_query(['language' => 'en-US', 'format' => 'detailed']))
            ->throw()
            ->json();

        if (($result['RecognitionStatus'] ?? null) !== 'Success') {
            return 0;
        }

        $best = $result['NBest'][0] ?? [];
        $score = $best['PronScore'] ?? $best['PronunciationAssessment']['PronScore'] ?? $best['AccuracyScore'] ?? 0;

        return (int) max(0, min(10, round($score / 10)));
    }

    private function endpoint(): string
    {
        return config('services.azure_speech.endpoint')
            ?: 'https://'.config('services.azure_speech.region').'.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1';
    }
}
