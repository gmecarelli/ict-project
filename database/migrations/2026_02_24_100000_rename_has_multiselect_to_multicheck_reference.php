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
        // Rinomina has_multiselect (boolean) → multicheck_reference (varchar)
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('has_multiselect');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('multicheck_reference', 45)
                ->nullable()
                ->after('class_delete_button')
                ->comment('Chiave reference per multicheck_actions. Se valorizzata, abilita il multicheck e il dropdown azioni massive');
        });

        // Aggiorna il form_field corrispondente (da select YN a text)
        DB::table('form_fields')
            ->where('name', 'has_multiselect')
            ->where('form_id', 2)
            ->update([
                'name' => 'multicheck_reference',
                'label' => 'Multicheck Reference',
                'type' => 'text',
                'type_attr' => null,
                'default_value' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('multicheck_reference');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('has_multiselect')->default(0)->after('class_delete_button');
        });

        DB::table('form_fields')
            ->where('name', 'multicheck_reference')
            ->where('form_id', 2)
            ->update([
                'name' => 'has_multiselect',
                'label' => 'Multiselect',
                'type' => 'select',
                'type_attr' => 'reference:YN',
                'default_value' => '0',
            ]);
    }
};
