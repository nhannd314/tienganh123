<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Course;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * Opens a payment by hand, e.g. a learner who paid in cash or a transfer SePay never reported.
 */
class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        $refreshAmount = function (Get $get, Set $set): void {
            $course = Course::find($get('course_id'));

            if ($course && $get('months')) {
                $set('amount', $course->priceForMonths((int) $get('months')));
            }
        };

        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Khách hàng')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $user): string => "{$user->name} ({$user->email})")
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required(),
                Select::make('course_id')
                    ->label('Khóa học')
                    ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated($refreshAmount),
                Select::make('months')
                    ->label('Số tháng')
                    ->options(collect(Course::cycles())->mapWithKeys(fn (int $discount, int $months): array => [$months => "{$months} tháng"])->all())
                    ->default(array_key_first(Course::cycles()))
                    ->required()
                    ->live()
                    ->afterStateUpdated($refreshAmount),
                TextInput::make('amount')
                    ->label('Số tiền (₫)')
                    ->helperText('Tự điền theo giá khóa học và số tháng, có thể sửa.')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Toggle::make('is_paid')
                    ->label('Đã nhận tiền')
                    ->helperText('Bật để xác nhận thanh toán ngay: khách được mở khóa học. Tắt để tạo đơn chờ thanh toán.')
                    ->default(true),
            ])
            ->columns(2);
    }
}
