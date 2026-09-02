<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 24)->unique();
            $table->foreignId('blogger_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('seq');
            $table->string('full_name');
            $table->string('phone', 11)->index();
            $table->enum('grade', ['10', '11', '12', 'alumni']);
            $table->enum('field', ['math', 'science']);
            $table->string('school');
            $table->decimal('gpa', 4, 2)->nullable();
            $table->string('study_city');
            $table->string('father_job')->nullable();
            $table->string('province');
            $table->string('city');
            $table->string('area')->nullable();
            $table->string('guardian_name');
            $table->string('guardian_phone', 11);
            $table->enum('status', ['pending', 'calling', 'approved', 'canceled'])->default('pending');
            $table->string('ticket_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['blogger_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
