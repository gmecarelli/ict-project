<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->integer('is_enabled')->default(1)->index()->comment('Indica se il report è abilitato o meno');
            $table->timestamps();
        });

        // Inserimento dei dati iniziali
        DB::table('profiles')->insert([
            [
                'id' => 1,
                'name' => 'Admin',
                'is_enabled' => 1,
                'created_at' => '2022-03-16 15:19:56',
                'updated_at' => '2022-03-16 15:19:56'
            ],
            [
                'id' => 2,
                'name' => 'Guest',
                'is_enabled' => 1,
                'created_at' => '2022-03-28 13:01:21',
                'updated_at' => '2024-06-10 14:56:48'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
