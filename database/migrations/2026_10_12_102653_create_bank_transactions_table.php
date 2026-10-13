<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every transaction SePay reports for the bank account, kept as it arrived. `sepay_id` is SePay's own id, which stays
     * the same across retries, so a transaction is never handled twice. `payment_id` is the payment it paid for, if any.
     */
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sepay_id')->unique();
            $table->string('gateway');
            $table->string('account_number');
            $table->dateTime('transaction_date');
            $table->string('transfer_type', 3);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('accumulated')->default(0);
            $table->string('code')->nullable();
            $table->text('content');
            $table->string('reference_code')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->json('payload');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
