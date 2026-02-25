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
        Schema::create('profile_roles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id', 40);
            $table->integer('report_id');
            $table->boolean('has_create_button');
            $table->boolean('has_edit_button');
            $table->boolean('is_all_owner')->default(1)->comment('Indica se nel report devono essere visualizzati tutti gli owner o solo l\'owner corrispondente all\'utente loggato');
            $table->mediumText('fields_disabled')->nullable()->comment('testo formato json, deve essere una matrice dove la prima chiave è l\'id del form (form del report in questione) che ha come valore un array con i nomi dei campi da disabilitare. Se non c\'è un array ma il valore è all, deve disabilitare tutti i campi e bottoni del form');
            $table->boolean('is_enabled')->default(1)->index()->comment('Indica se il report è abilitato o meno');
            $table->timestamps();

            $table->unique(['profile_id', 'report_id']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_roles');
    }
};
