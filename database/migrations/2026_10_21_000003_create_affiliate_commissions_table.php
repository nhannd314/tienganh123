<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The commission an affiliate earns on one paid payment of a user they referred. `payment_id` is unique, so a
     * payment never pays commission twice. `payment_amount` and `rate` (a percent) are kept as they were at the time,
     * so the commission stays the same if the rate changes later. A commission is pending until it is approved
     * (after the refund window), then paid once it is part of a payout.
     */
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('affiliate_payout_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('payment_amount');
            $table->unsignedTinyInteger('rate');
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
