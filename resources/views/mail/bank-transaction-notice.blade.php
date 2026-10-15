<x-mail::message>
@if ($status === \App\Enums\BankTransactionStatus::Matched)
# Có thanh toán thành công
@elseif ($status === \App\Enums\BankTransactionStatus::Underpaid)
# Chuyển khoản thiếu tiền
Mã chuyển khoản khớp một đơn đang chờ nhưng số tiền nhỏ hơn giá, nên khóa học **chưa được kích hoạt**.
@else
# Chuyển khoản không khớp
Không có đơn nào đang chờ khớp với mã chuyển khoản của giao dịch này (sai mã, không có mã, hoặc đơn đã thanh toán / không còn chờ), nên **chưa kích hoạt gì**.
@endif

<x-mail::table>
| | |
|:--|:--|
| Số tiền nhận | {{ number_format($transaction->amount, 0, ',', '.') }}đ |
| Thời gian | {{ $transaction->transaction_date->timezone(config('app.timezone'))->format('H:i d/m/Y') }} |
| Ngân hàng | {{ $transaction->gateway }} |
| Nội dung chuyển khoản | {{ $transaction->content ?: '—' }} |
| Mã nhận diện | {{ $transaction->code ?: '—' }} |
| Mã giao dịch | {{ $transaction->reference_code ?: $transaction->sepay_id }} |
@if ($payment)
| Đơn hàng | {{ $payment->provider_reference }} - {{ $payment->payable?->title ?? $payment->payable?->name ?? '—' }} |
| Người mua | {{ $payment->user->name }} ({{ $payment->user->email }}) |
| Giá đơn hàng | {{ number_format($payment->amount, 0, ',', '.') }}đ |
@endif
</x-mail::table>

@if ($status === \App\Enums\BankTransactionStatus::Matched && $payment && $transaction->amount > $payment->amount)
Người mua chuyển **thừa {{ number_format($transaction->amount - $payment->amount, 0, ',', '.') }}đ**, khóa học đã được kích hoạt.
@elseif ($status !== \App\Enums\BankTransactionStatus::Matched)
Hãy kiểm tra giao dịch và liên hệ người chuyển, hoặc đánh dấu đã thanh toán thủ công trong trang quản trị nếu hợp lệ.
@endif
</x-mail::message>
