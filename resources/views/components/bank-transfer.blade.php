{{-- Bank transfer instructions for a pending payment: QR code, account details, and the live "paid" notice. --}}
@props([
    'payment',
    'bank',
    'qrUrl' => null,
    'paidMessage' => 'Đang chuyển bạn sang trang tiếp theo…',
])

@php
    $rows = $payment ? array_filter([
        ['label' => 'Ngân hàng', 'value' => $bank['name'], 'copy' => false],
        ['label' => 'Chủ tài khoản', 'value' => $bank['account_name'], 'copy' => false],
        ['label' => 'Số tài khoản', 'value' => $bank['account_number'], 'copy' => $bank['account_number']],
        ['label' => 'Số tiền', 'value' => number_format($payment->amount, 0, ',', '.').'₫', 'copy' => (string) $payment->amount, 'strong' => true],
        ['label' => 'Nội dung chuyển khoản', 'value' => $payment->provider_reference, 'copy' => $payment->provider_reference, 'strong' => true],
    ], fn (array $row): bool => filled($row['value'])) : [];
@endphp


@if (blank($bank['account_number']))
    <x-alert tone="warning" title="Chưa có thông tin chuyển khoản">Thông tin tài khoản nhận tiền chưa được cấu hình, bạn quay lại sau nhé.</x-alert>
@else
    <div class="flex flex-wrap items-start gap-8">
        @if ($qrUrl)
            <div class="flex flex-[0_1_288px] flex-col items-center gap-3">
                <img src="{{ $qrUrl }}" alt="Mã QR chuyển khoản {{ $payment->provider_reference }}" width="288" height="288" class="size-72 max-w-full rounded-md border border-line-default bg-white object-contain">
                <p class="text-center type-body-sm text-fg-muted">Quét mã bằng ứng dụng ngân hàng, số tiền và nội dung đã được điền sẵn.</p>
            </div>
        @endif

        <dl class="flex min-w-0 flex-[1_1_320px] flex-col divide-y divide-line-subtle">
            @foreach ($rows as $row)
                <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0">
                        <dt class="type-body-sm text-fg-muted">{{ $row['label'] }}</dt>
                        <dd @class(['break-words', 'type-label-lg text-danger-text' => $row['strong'] ?? false, 'type-body text-fg-strong' => ! ($row['strong'] ?? false)])>{{ $row['value'] }}</dd>
                    </div>

                    @if ($row['copy'])
                        <x-icon-button icon="copy" :label="'Sao chép '.mb_strtolower($row['label'])" size="sm" :data-copy="$row['copy']" />
                    @endif
                </div>
            @endforeach
        </dl>
    </div>

    <div data-payment-status="{{ route('payments.status', $payment) }}" data-payment-channel="payments.{{ $payment->id }}" class="flex flex-col gap-6">
        <x-alert tone="warning" title="Lưu ý" data-payment-pending>
            Nhập đúng nội dung chuyển khoản <b>{{ $payment->provider_reference }}</b> để chúng mình nhận ra khoản thanh toán của bạn. Trang này tự chuyển trang ngay khi nhận được tiền.
        </x-alert>

        <x-alert tone="success" title="Thanh toán thành công" data-payment-paid hidden>
            {{ $paidMessage }}
        </x-alert>
    </div>
@endif
