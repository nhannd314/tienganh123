<?php

namespace App\Filament\Resources\Sentences\Pages;

use App\Filament\Resources\Sentences\Schemas\SentenceForm;
use App\Filament\Resources\Sentences\SentenceResource;
use App\Models\Sentence;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSentence extends CreateRecord
{
    protected static string $resource = SentenceResource::class;

    /**
     * A sentence with the same words as a stored one updates it, keeping its meaning, sets and files when none are typed.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $existing = Sentence::findByPlainText($data['sentence']);

        if ($existing === null) {
            return parent::handleRecordCreation($data);
        }

        // The sets of the form are added to the ones the sentence has, as saving them would drop the rest.
        $this->data['sentenceSets'] = array_values(array_unique([
            ...$existing->sentenceSets()->pluck('sentence_sets.id')->all(),
            ...array_map('intval', $this->data['sentenceSets'] ?? []),
        ]));

        $existing->updateKeepingEmpty($data);

        return $existing;
    }

    protected function afterCreate(): void
    {
        // A reworded sentence lost its audio, so it is voiced again.
        SentenceForm::queueMissingAudio($this->getRecord());
    }
}
