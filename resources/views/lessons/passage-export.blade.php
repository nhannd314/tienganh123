{{--
    The passage worksheet for print (DomPDF as PDF, or served as .doc for Word): the marked words are already blanks, see
    LessonController::exportPassage. DejaVu Sans is used because it has the Vietnamese letters.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $lesson->title }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 14px; color: #111; line-height: 1.9; }
        h1 { font-size: 20px; margin: 0 0 6px; text-align: center; }
        h2 { font-size: 16px; margin: 0 0 12px; }
        .student { margin: 10px 0 16px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #111; padding: 6px; }
    </style>
</head>
<body>
    <h1>{{ $lesson->title }}</h1>
    <p class="student">Họ và tên: ______________________________ &nbsp; Lớp: ____________</p>
    <h2>Listen and fill in the blanks</h2>
    {!! $passageHtml !!}
</body>
</html>
