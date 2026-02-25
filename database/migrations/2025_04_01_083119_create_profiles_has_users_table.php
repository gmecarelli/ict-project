<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles_has_users', function (Blueprint $table) {
            $table->string('key_id',30)->unique();
            $table->foreignId('profile_id')->constrained('profiles');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at');
        });

        DB::table('profiles_has_users')->insert([
            [
                'key_id' => '4AlDIT-p3u33',
                'profile_id' => 1,
                'user_id' => 1,
                'created_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles_has_users');
    }
};
