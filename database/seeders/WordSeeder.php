<?php

namespace Database\Seeders;

use App\Models\Word;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WordSeeder extends Seeder
{
    /**
     * Seed sample vocabulary words.
     */
    public function run(): void
    {
        foreach ($this->words() as $data) {
            $slug = Str::slug($data['word']);

            Word::updateOrCreate(
                ['slug' => $slug],
                [
                    'word' => $data['word'],
                    'ipa_us' => $data['ipa_us'],
                    'ipa_uk' => $data['ipa_uk'],
                    'meanings' => $data['meanings'],
                    'examples' => collect($data['examples'])
                        ->map(fn (array $example, int $index): array => [
                            ...$example,
                            'audio' => 'audio/examples/'.$slug.'-'.($index + 1).'.mp3',
                        ])
                        ->all(),
                    'image' => 'images/words/'.$slug.'.jpg',
                ],
            );
        }
    }

    /**
     * Get the sample words.
     *
     * @return list<array{
     *     word: string,
     *     ipa_us: string,
     *     ipa_uk: string,
     *     meanings: list<array{pos: string, meaning: string}>,
     *     examples: list<array{sentence: string, translation: string}>
     * }>
     */
    private function words(): array
    {
        return [
            [
                'word' => 'apple',
                'ipa_us' => '/ˈæp.əl/',
                'ipa_uk' => '/ˈæp.əl/',
                'meanings' => [
                    ['pos' => 'noun', 'meaning' => 'quả táo'],
                ],
                'examples' => [
                    ['sentence' => 'I eat an apple every morning.', 'translation' => 'Tôi ăn một quả táo mỗi sáng.'],
                    ['sentence' => 'She bought a kilo of apples.', 'translation' => 'Cô ấy đã mua một ký táo.'],
                ],
            ],
            [
                'word' => 'book',
                'ipa_us' => '/bʊk/',
                'ipa_uk' => '/bʊk/',
                'meanings' => [
                    ['pos' => 'noun', 'meaning' => 'quyển sách'],
                    ['pos' => 'verb', 'meaning' => 'đặt trước (vé, phòng)'],
                ],
                'examples' => [
                    ['sentence' => 'This book is very interesting.', 'translation' => 'Quyển sách này rất thú vị.'],
                    ['sentence' => 'I want to book a table for two.', 'translation' => 'Tôi muốn đặt một bàn cho hai người.'],
                ],
            ],
            [
                'word' => 'water',
                'ipa_us' => '/ˈwɑː.t̬ɚ/',
                'ipa_uk' => '/ˈwɔː.tər/',
                'meanings' => [
                    ['pos' => 'noun', 'meaning' => 'nước'],
                    ['pos' => 'verb', 'meaning' => 'tưới nước'],
                ],
                'examples' => [
                    ['sentence' => 'Can I have a glass of water?', 'translation' => 'Cho tôi một ly nước được không?'],
                    ['sentence' => 'He waters the plants every day.', 'translation' => 'Anh ấy tưới cây mỗi ngày.'],
                ],
            ],
            [
                'word' => 'work',
                'ipa_us' => '/wɝːk/',
                'ipa_uk' => '/wɜːk/',
                'meanings' => [
                    ['pos' => 'verb', 'meaning' => 'làm việc'],
                    ['pos' => 'noun', 'meaning' => 'công việc'],
                ],
                'examples' => [
                    ['sentence' => 'I work in an office.', 'translation' => 'Tôi làm việc trong một văn phòng.'],
                    ['sentence' => 'She has a lot of work to do.', 'translation' => 'Cô ấy có rất nhiều việc phải làm.'],
                ],
            ],
            [
                'word' => 'ice cream',
                'ipa_us' => '/ˈaɪs ˌkriːm/',
                'ipa_uk' => '/ˌaɪs ˈkriːm/',
                'meanings' => [
                    ['pos' => 'noun', 'meaning' => 'kem'],
                ],
                'examples' => [
                    ['sentence' => 'The children love chocolate ice cream.', 'translation' => 'Bọn trẻ rất thích kem sô-cô-la.'],
                ],
            ],
        ];
    }
}
