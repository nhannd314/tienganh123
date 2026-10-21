<x-mail::message>
# Có thành viên mới đăng ký

<x-mail::table>
| | |
|:--|:--|
| Họ và tên | {{ $user->name }} |
| Email | {{ $user->email }} |
| Đăng ký lúc | {{ $user->created_at->timezone(config('app.timezone'))->format('H:i d/m/Y') }} |
| Đăng nhập bằng | {{ filled($user->google_id) ? 'Google' : 'Email' }} |
| Người giới thiệu | {{ $referrer ? $referrer->name.' ('.$referrer->email.')' : '—' }} |
</x-mail::table>
</x-mail::message>
