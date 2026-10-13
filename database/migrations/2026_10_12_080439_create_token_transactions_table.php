<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The ledger of the tokens of a user: a positive amount adds tokens (top-up, refund), a negative one spends them.
     * `reference` is what caused the entry (the payment of a top-up, the review of an AI scoring).
     */
    public function up(): void
    {
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('amount');
            $table->unsignedBigInteger('balance_after');
            $table->foreignId('ai_service_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};
