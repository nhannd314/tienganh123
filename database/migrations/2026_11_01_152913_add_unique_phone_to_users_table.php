<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The phone number signs a learner in like the email does, so no two accounts may share one. Blank numbers become
     * null first: many accounts (e.g. those made with Google) have no phone number.
     */
    public function up(): void
    {
        DB::table('users')->where('phone', '')->update(['phone' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
