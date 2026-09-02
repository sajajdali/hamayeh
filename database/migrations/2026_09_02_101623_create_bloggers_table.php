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
        Schema::create('bloggers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->string('slug', 24)->unique();
            $table->string('phone', 11)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('seq')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloggers');
    }
};
