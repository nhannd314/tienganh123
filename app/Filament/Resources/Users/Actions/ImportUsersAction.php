<?php

namespace App\Filament\Resources\Users\Actions;

use App\Enums\Membership;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ImportUsersAction extends Action
{
    /**
     * The password of every imported user, to be changed by the user.
     */
    public const DEFAULT_PASSWORD = '12345678';

    public static function getDefaultName(): ?string
    {
        return 'importUsers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import users')
            ->icon(Heroicon::ArrowUpTray)
            ->color('gray')
            ->modalHeading('Import users')
            ->modalSubmitActionLabel('Import')
            ->modalWidth('2xl')
            ->schema([
                Textarea::make('user_lines')
                    ->label('Học sinh')
                    ->required()
                    ->rows(12)
                    ->placeholder("Nguyễn Văn An | an@example.com | 0912345678\nTrần Thị Bình | binh@example.com | 0987654321")
                    ->helperText('Mỗi dòng một học sinh: họ tên | email | số điện thoại. Mật khẩu là 12345678. Dòng có email hoặc số điện thoại đã dùng sẽ bị bỏ qua.'),
                Checkbox::make('is_member')
                    ->label('Thành viên Membership')
                    ->helperText('Membership không hết hạn.'),
            ])
            ->action(function (array $data): void {
                $result = self::import((string) $data['user_lines'], (bool) ($data['is_member'] ?? false));

                $notification = Notification::make()
                    ->title("Imported {$result['created']} users")
                    ->success();

                if (filled($result['skipped'])) {
                    $notification
                        ->body('Skipped: '.implode('; ', $result['skipped']))
                        ->warning()
                        ->persistent();
                }

                $notification->send();
            });
    }

    /**
     * Create a user for each line `name | email | phone` (phone optional), with the default password. Lines missing a
     * name, with an invalid email or phone, or whose email or phone is already used, are skipped.
     *
     * @return array{created: int, skipped: list<string>}
     */
    public static function import(string $text, bool $isMember): array
    {
        $created = 0;
        $skipped = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            if (trim($line) === '') {
                continue;
            }

            [$name, $email, $phone] = array_map('trim', [...explode('|', $line, 3), '', '']);
            $email = mb_strtolower($email);
            $phone = $phone === '' ? null : User::normalizePhone($phone);

            $reason = match (true) {
                $name === '' => 'thiếu họ tên',
                filter_var($email, FILTER_VALIDATE_EMAIL) === false => 'email không hợp lệ',
                $phone !== null && ! preg_match(User::PHONE_PATTERN, $phone) => 'số điện thoại không hợp lệ',
                User::where('email', $email)->exists() => 'email đã có',
                $phone !== null && User::where('phone', $phone)->exists() => 'số điện thoại đã có',
                default => null,
            };

            if ($reason !== null) {
                $skipped[] = trim($line)." ({$reason})";

                continue;
            }

            User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => self::DEFAULT_PASSWORD,
                'membership' => $isMember ? Membership::Member : null,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
