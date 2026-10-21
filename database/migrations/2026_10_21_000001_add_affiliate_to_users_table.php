<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `referral_code` is the code in the user's share link. `referred_by_id` is the affiliate who brought the user in,
     * set once at registration and kept for good. `affiliate_commission_rate` overrides the default rate (a percent)
     * for that affiliate; null uses the default. The bank columns are where the affiliate's payouts are sent.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique()->after('membership_expires_on');
            $table->foreignId('referred_by_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->timestamp('referred_at')->nullable()->after('referred_by_id');
            $table->unsignedTinyInteger('affiliate_commission_rate')->nullable()->after('referred_at');
            $table->string('affiliate_bank_name')->nullable()->after('affiliate_commission_rate');
            $table->string('affiliate_bank_account_number', 50)->nullable()->after('affiliate_bank_name');
            $table->string('affiliate_bank_account_name')->nullable()->after('affiliate_bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_id');
            $table->dropColumn([
                'referral_code',
                'referred_at',
                'affiliate_commission_rate',
                'affiliate_bank_name',
                'affiliate_bank_account_number',
                'affiliate_bank_account_name',
            ]);
        });
    }
};
