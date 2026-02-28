<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 150);
            $table->string('remember_token', 100)->nullable();
            $table->tinyInteger('is_enabled')->nullable()->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('email', 'users_email_unique');
        });

        // Seed data
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Giorgio Mecarelli',
                'email' => 'giorgio.mecarelli@gmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$xuA.1/MbLQBDX2JkbYWuROfhD7i5owd3VSU2BALbFLGeCZa47eNtG',
                'remember_token' => null,
                'is_enabled' => 1,
                'created_at' => '2026-02-17 17:26:06',
                'updated_at' => '2026-02-17 17:26:06',
            ],
        ]);

    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
