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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('label', 75);
            $table->string('reference', 50)->comment('La chiave di ricerca dei valori da visualizzare');
            $table->string('icon', 50)->nullable()->comment('codice fontawesome per eventuale icona da visualizzare');
            $table->string('class', 75)->nullable()->comment('classe eventualmente applicata allo stato ed al sue elemento');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Indici e chiavi uniche
            $table->unique(['label', 'reference'], 'options_label_reference_unique');
            $table->unique(['code', 'reference'], 'options_code_reference_unique');
            $table->index('code', 'options_code_index');
            $table->index('label', 'options_label_index');
        });

        // Inserimento dati iniziali
        DB::table('options')->insert([
            // Azioni di sistema
            ['code' => 'cancel', 'label' => 'Annulla/Disabilita', 'reference' => 'DELETE', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-20 10:42:26', 'updated_at' => '2021-07-20 10:42:26'],
            ['code' => 'destroy', 'label' => 'Elimina da DB', 'reference' => 'DELETE', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-20 10:42:26', 'updated_at' => '2021-07-20 10:42:26'],

            // Stati Enabled/Disabled
            ['code' => '0', 'label' => 'Disabled', 'reference' => 'ED', 'icon' => null, 'class' => 'text-danger font-weight-bolder', 'is_enabled' => 1, 'created_at' => '2021-06-17 10:42:26', 'updated_at' => '2021-06-17 10:42:26'],
            ['code' => '1', 'label' => 'Enabled', 'reference' => 'ED', 'icon' => null, 'class' => 'text-success font-weight-bolder', 'is_enabled' => 1, 'created_at' => '2021-06-17 10:42:26', 'updated_at' => '2021-06-17 10:42:26'],

            // Mesi
            ['code' => '1', 'label' => 'Gennaio', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '2', 'label' => 'Febbraio', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '3', 'label' => 'Marzo', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '4', 'label' => 'Aprile', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '5', 'label' => 'Maggio', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '6', 'label' => 'Giugno', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '7', 'label' => 'Luglio', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '8', 'label' => 'Agosto', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '9', 'label' => 'Settembre', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '10', 'label' => 'Ottobre', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '11', 'label' => 'Novembre', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],
            ['code' => '12', 'label' => 'Dicembre', 'reference' => 'MONTH', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-02 13:42:26', 'updated_at' => '2021-07-02 13:42:26'],

            // Tipi di colonne
            ['code' => 'string', 'label' => 'String', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'int', 'label' => 'Int', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'date', 'label' => 'Date', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'dateTime', 'label' => 'DateTime', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'enum', 'label' => 'Enum', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'currency', 'label' => 'Currency', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-24 10:42:26', 'updated_at' => '2021-07-24 10:42:26'],
            ['code' => 'integer', 'label' => 'Nr intero', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2022-12-24 10:42:26', 'updated_at' => '2022-12-24 10:42:26'],
            ['code' => 'float', 'label' => 'Nr decimale', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2022-12-24 10:42:26', 'updated_at' => '2022-12-24 10:42:26'],
            ['code' => 'percent', 'label' => 'Percentuale', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2022-12-24 10:42:26', 'updated_at' => '2022-12-24 10:42:26'],
            ['code' => 'array', 'label' => 'Multi valore', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2023-08-30 10:42:26', 'updated_at' => '2023-08-31 09:28:19'],
            ['code' => 'alert', 'label' => 'Alert', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-24 10:42:26', 'updated_at' => '2021-07-24 10:42:26'],
            ['code' => 'link', 'label' => 'Link', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-07 10:42:26', 'updated_at' => '2021-07-07 10:42:26'],
            ['code' => 'relations', 'label' => 'Stringa valori model', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'switch', 'label' => 'Switch', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'match', 'label' => 'Confronto dati', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'directLink', 'label' => 'Link diretto', 'reference' => 'TYPECOLS', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            
            //tipi di campi del form
            ['code' => 'text', 'label' => 'text', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'number', 'label' => 'number', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'date', 'label' => 'date', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'select', 'label' => 'select', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'hidden', 'label' => 'hidden', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-06-24 10:42:26', 'updated_at' => '2021-06-24 10:42:26'],
            ['code' => 'textarea', 'label' => 'textarea', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-09-16 10:42:26', 'updated_at' => '2021-09-16 10:42:26'],
            ['code' => 'file', 'label' => 'file', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-09-17 10:42:26', 'updated_at' => '2021-09-17 10:42:26'],
            ['code' => 'checkbox', 'label' => 'checkbox', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2022-03-21 10:42:26', 'updated_at' => '2022-03-21 10:42:26'],
            ['code' => 'button', 'label' => 'button', 'reference' => 'TYPEDATA', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2022-03-21 10:42:26', 'updated_at' => '2022-03-21 10:42:26'],
            
            // tipi di form
            ['code' => 'editable', 'label' => 'Insert/Edit', 'reference' => 'TYPEFORM', 'icon' => 'fa fa-edit', 'class' => 'text-primary', 'is_enabled' => 1, 'created_at' => '2021-07-12 10:42:26', 'updated_at' => '2023-08-31 08:12:08'],
            ['code' => 'filter', 'label' => 'Filter', 'reference' => 'TYPEFORM', 'icon' => 'fas fa-search', 'class' => 'text-danger', 'is_enabled' => 1, 'created_at' => '2021-07-12 10:42:26', 'updated_at' => '2023-08-31 08:11:54'],
            ['code' => 'modal', 'label' => 'Modal', 'reference' => 'TYPEFORM', 'icon' => 'far fa-window-maximize', 'class' => 'text-success', 'is_enabled' => 1, 'created_at' => '2021-09-20 22:00:00', 'updated_at' => '2021-09-20 22:00:00'],
            ['code' => 'child', 'label' => 'Item Child', 'reference' => 'TYPEFORM', 'icon' => null, 'class' => null, 'is_enabled' => 1, 'created_at' => '2021-07-12 10:42:26', 'updated_at' => '2021-07-12 10:42:26'],
            
            //SI / No
            ['code' => '0', 'label' => 'No', 'reference' => 'YN', 'icon' => null, 'class' => 'text-danger font-weight-bolder', 'is_enabled' => 1, 'created_at' => '2021-06-17 10:42:26', 'updated_at' => '2021-06-17 10:42:26'],
            ['code' => '1', 'label' => 'Si', 'reference' => 'YN', 'icon' => null, 'class' => 'text-success font-weight-bolder', 'is_enabled' => 1, 'created_at' => '2021-06-17 10:42:26', 'updated_at' => '2021-06-17 10:42:26'],
        ]);

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
