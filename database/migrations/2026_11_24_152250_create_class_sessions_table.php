<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: a class studies in a slot (a date and a shift) once, with an optional note. The table allows several
     * classes in one slot; "one class per slot" is a rule of the schedule page (ClassScheduleController), not of the schema.
     */
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('shift');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'date', 'shift']);
            $table->index(['date', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
