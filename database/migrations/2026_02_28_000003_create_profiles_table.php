<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->integer('is_enabled')->default(1)->comment('Indica se il report è abilitato o meno');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('is_enabled', 'profiles_is_enabled_index');
            $table->unique('name', 'profiles_name_unique');
        });

        // Seed data
        DB::table('profiles')->insert([
            [
                'id' => 1,
                'name' => 'Admin',
                'is_enabled' => 1,
                'created_at' => '2022-03-16 15:19:56',
                'updated_at' => '2022-03-16 15:19:56',
            ],
            [
                'id' => 2,
                'name' => 'Guest',
                'is_enabled' => 1,
                'created_at' => '2022-03-28 13:01:21',
                'updated_at' => '2024-06-10 14:56:48',
            ],
        ]);

    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
