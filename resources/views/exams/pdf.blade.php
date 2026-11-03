{{--
    The blank exam paper for print (DomPDF, see ExamController::pdf): the same numbering as exams/take, with no answers, no audio
    and no options drawn from the answers. DejaVu Sans is used because it has the Vietnamese letters.
--}}
@php
    use App\Enums\ExamGroupType;
    use App\Models\ExamQuestion;

    $formatPoints = fn (float $points): string => rtrim(rtrim(number_format($points, 2, ',', '.'), '0'), ',');
    $number = 0;
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $exam->title }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin: 0 0 6px; text-align: center; }
        h2 { font-size: 14px; margin: 22px 0 4px; }
        .meta { text-align: center; color: #555; margin-bottom: 14px; }
        .student { margin: 10px 0 4px; }
        .hint { color: #555; font-size: 11px; margin-bottom: 8px; }
        .question { margin: 0 0 12px; page-break-inside: avoid; }
        .number { font-weight: bold; }
        .options td { padding: 2px 14px 2px 0; vertical-align: top; }
        .blank { display: inline-block; width: 110px; border-bottom: 1px solid #111; }
        .line { border-bottom: 1px solid #111; height: 20px; margin-top: 6px; }
        .passage { line-height: 2; white-space: pre-line; }
    </style>
</head>
<body>
    <h1>{{ $exam->title }}</h1>

    <div class="meta">
        {{ $exam->duration_minutes ? 'Thời gian: '.$exam->duration_minutes.' phút' : 'Không giới hạn thời gian' }} · Thang điểm 100
    </div>

    <div class="student">Họ và tên: ................................................ &nbsp;&nbsp; Lớp: ................</div>

    @foreach ($sections as $section)
        @php
            $group = $section['group'];
            $isWritten = $group->type === ExamGroupType::Questions;
        @endphp

        <h2>Phần {{ $loop->iteration }}. {{ $group->title }}</h2>

        @if (filled($group->instructions_html))
            <div>{!! $group->instructions_html !!}</div>
        @endif

        <div class="hint">{{ count($section['questions']) }} câu · mỗi câu {{ $formatPoints($section['points']) }} điểm</div>

        @if ($group->type === ExamGroupType::ListenAndFill)
            <div class="passage">
                @foreach ($section['segments'] as $segment){{ $segment['blank'] === null ? $segment['text'] : '' }}@if ($segment['blank'] !== null)<span class="number">({{ $number + $segment['blank'] }})</span> <span class="blank"></span>@endif @endforeach
            </div>
            @php($number += count($section['questions']))
        @elseif ($group->type === ExamGroupType::ListenSentenceAndFill)
            @foreach ($section['lines'] as $line)
                <div class="question">
                    <span class="number">{{ $number + $line['number'] }}.</span>
                    @foreach ($line['segments'] as $segment)
                        @if ($segment['blank'] === null){{ $segment['text'] }} @else<span class="blank"></span> @endif
                    @endforeach
                </div>
            @endforeach
            @php($number += count($section['lines']))
        @else
            @foreach ($section['questions'] as $question)
                @php($number++)
                <div class="question">
                    <span class="number">{{ $number }}.</span>
                    @if ($group->type === ExamGroupType::ListenToMeaning)
                        Nghe từ và chọn nghĩa đúng.
                    @else
                        {!! $isWritten ? ExamQuestion::markedHtml($question['prompt']) : e($question['prompt']) !!}
                        @if ($group->type === ExamGroupType::WordToMeaning && $question['phonetic'])
                            {{ $question['phonetic'] }}
                        @endif
                    @endif

                    @if ($question['kind'] === 'choice')
                        <table class="options">
                            @foreach (array_chunk($question['options'] ?? [], 2) as $row)
                                <tr>
                                    @foreach ($row as $option)
                                        <td>{{ $option['letter'] ?? '' }}. {!! $isWritten ? ExamQuestion::markedHtml($option['label']) : e($option['label']) !!}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                    @elseif ($question['kind'] === 'text')
                        <div class="line"></div>
                    @endif
                </div>
            @endforeach
        @endif
    @endforeach
</body>
</html>
