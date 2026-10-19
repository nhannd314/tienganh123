<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\AudioBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\VideoBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YoutubeVideoBlock;
use App\Models\Unit;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_id')
                    ->relationship(
                        'unit',
                        'title',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->select('units.*')
                            ->join('courses', 'courses.id', '=', 'units.course_id')
                            ->orderBy('courses.title')
                            ->orderBy('units.sort_order'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Unit $record): string => $record->course->title.' » '.$record->title)
                    ->searchable(['units.title', 'courses.title'])
                    ->live()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('unit_id', $get('unit_id')),
                    )
                    ->helperText('Leave empty to generate it from the title. Must be unique within the unit.'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_free')
                    ->helperText('Free lessons can be tried without enrolling.')
                    ->default(false),
                Toggle::make('is_published')
                    ->default(false),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                self::richContentEditor('passage')
                    ->helperText('Opening passage of the lesson. Wrap a word in inline code (`apple`) to show its IPA, audio and meaning in a popover when the word exists.'),
                FileUpload::make('passage_audio')
                    ->label('Passage audio')
                    ->acceptedFileTypes(['audio/mpeg'])
                    ->disk('public')
                    ->directory('lessons/passage-audio')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->downloadable()
                    ->columnSpanFull(),
                Select::make('sentence_set_id')
                    ->label('Sentence set')
                    ->relationship('sentenceSet', 'title')
                    ->searchable()
                    ->preload()
                    ->helperText('The sentences shown in the example sentences section of the lesson.')
                    ->columnSpanFull(),
                self::richContentEditor('content'),
                FileUpload::make('attachments')
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('lessons/attachments')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->downloadable()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Rich text editor shared by the passage and the content: formatting, headings, lists, image attachments, and YouTube, video and audio blocks.
     */
    private static function richContentEditor(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'code', 'link'],
                ['h2', 'h3'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['attachFiles', 'customBlocks'],
                ['undo', 'redo'],
            ])
            ->customBlocks([YoutubeVideoBlock::class, VideoBlock::class, AudioBlock::class])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('lessons/content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(5120)
            ->helperText('Wrap a word in inline code (`apple`) to show its IPA, audio and meaning in a popover when the word exists.')
            ->columnSpanFull();
    }
}
