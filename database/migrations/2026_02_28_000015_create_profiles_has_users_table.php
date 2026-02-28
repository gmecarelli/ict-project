<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles_has_users', function (Blueprint $table) {
            $table->string('key_id', 30);
            $table->unsignedBigInteger('profile_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at');
            $table->unique('key_id', 'profiles_has_users_key_id_unique');
            $table->foreign('profile_id')->references('id')->on('profiles');
            $table->foreign('user_id')->references('id')->on('users');
        });

        // Seed data
        DB::table('profiles_has_users')->insert([
            [
                'key_id' => '4AlDIT-p3u33',
                'profile_id' => 1,
                'user_id' => 1,
                'created_at' => '2026-02-17 17:29:57',
            ],
        ]);

    }

    public function down(): void
    {
        Schema::dropIfExists('profiles_has_users');
    }
};
