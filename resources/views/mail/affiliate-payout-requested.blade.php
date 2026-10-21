<x-mail::message>
# Có yêu cầu rút tiền affiliate

<x-mail::table>
| | |
|:--|:--|
| Affiliate | {{ $affiliate->name }} ({{ $affiliate->email }}) |
| Số tiền | {{ number_format($payout->amount, 0, ',', '.') }}đ |
| Số hoa hồng | {{ $payout->commissions()->count() }} |
| Ngân hàng | {{ $payout->bank_name }} |
| Số tài khoản | {{ $payout->bank_account_number }} |
| Chủ tài khoản | {{ $payout->bank_account_name }} |
</x-mail::table>

Hãy chuyển khoản rồi đánh dấu đợt chi trả là đã chuyển trong trang quản trị.
</x-mail::message>
