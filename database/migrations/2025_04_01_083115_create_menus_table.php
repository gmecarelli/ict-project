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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50)->unique();
            $table->string('tooltip', 75)->nullable();
            $table->string('icon', 75)->nullable();
            $table->integer('position')->default(0);
            $table->integer('is_enabled')->default(1);
            $table->timestamps();
        });

        // Inserimento dei dati iniziali
        DB::table('menus')->insert([
            [
                'id' => 1,
                'title' => 'Configurazione',
                'tooltip' => 'Configurazione',
                'icon' => 'fas fa-wrench',
                'position' => 50,
                'is_enabled' => 1,
                'created_at' => '2021-06-01 12:28:32',
                'updated_at' => '2023-08-31 09:55:30'
            ],
            [
                'id' => 2,
                'title' => 'Profili utenti',
                'tooltip' => 'Gestione profili utente',
                'icon' => 'fas fa-user-friends',
                'position' => 50,
                'is_enabled' => 1,
                'created_at' => '2022-03-16 15:11:09',
                'updated_at' => '2023-08-31 09:54:36'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
