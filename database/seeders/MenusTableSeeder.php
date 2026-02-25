<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MenusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disabilita temporaneamente le foreign key per evitare problemi
        Schema::disableForeignKeyConstraints();
        
        // Svuota la tabella prima di inserire nuovi dati
        DB::table('menus')->truncate();
        
        // Inserisci i dati
        DB::table('menus')->insert([
            [
                'id' => 1,
                'name' => 'Configurazione',
                'description' => 'Configurazione',
                'icon' => 'fas fa-wrench',
                'order' => 50,
                'is_enabled' => 1,
                'created_at' => Carbon::parse('2021-06-01 10:28:32'),
                'updated_at' => Carbon::parse('2023-08-31 07:55:30')
            ],
            [
                'id' => 2,
                'name' => 'Profili utenti',
                'description' => 'Gestione profili utente',
                'icon' => 'fas fa-user-friends',
                'order' => 50,
                'is_enabled' => 1,
                'created_at' => Carbon::parse('2022-03-16 14:11:09'),
                'updated_at' => Carbon::parse('2023-08-31 07:54:36')
            ],
        ]);
        
        // Riabilita le foreign key
        Schema::enableForeignKeyConstraints();
    }
}
