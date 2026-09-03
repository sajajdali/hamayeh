<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('bloggers')->updateOrInsert(
            ['code' => 'a0'],
            [
                'name' => 'ثبت‌نام پیش‌فرض',
                'slug' => 'default',
                'phone' => null,
                'avatar_path' => null,
                'password' => null,
                'is_active' => true,
                'seq' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('bloggers')->where('code', 'a0')->where('slug', 'default')->delete();
    }
};
