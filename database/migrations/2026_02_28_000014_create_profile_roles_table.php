<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_roles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id', 40);
            $table->integer('report_id');
            $table->tinyInteger('has_create_button');
            $table->tinyInteger('has_edit_button');
            $table->tinyInteger('is_all_owner')->default(1)->comment('Indica se nel report devono essere visualizzati tutti gli owner o solo l\'owner corrispondente all\'utente loggato');
            $table->mediumText('fields_disabled')->nullable()->comment('testo formato json, deve essere una matrice dove la prima chiave è l\'id del form (form del report in questione) che ha come valore un array con i nomi dei campi da disabilitare. Se non c\'è un array ma il valore è all, deve disabilitare tutti i campi e bottoni del form');
            $table->tinyInteger('is_enabled')->default(1)->comment('Indica se il report è abilitato o meno');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('is_enabled', 'profile_roles_is_enabled_index');
            $table->unique(['profile_id', 'report_id'], 'profile_roles_profile_id_report_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_roles');
    }
};
