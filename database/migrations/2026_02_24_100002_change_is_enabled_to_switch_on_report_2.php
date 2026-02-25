<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Processo 8: Cambia il campo is_enabled del report "Configura Report" (id=2)
 * da type 'enum' a type 'switch' per abilitare il toggle inline via Livewire BoolSwitchComponent.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('report_columns')
            ->where('report_id', 2)
            ->where('field', 'is_enabled')
            ->update([
                'type' => 'switch',
                'type_params' => 'table:reports',
            ]);
    }

    public function down(): void
    {
        DB::table('report_columns')
            ->where('report_id', 2)
            ->where('field', 'is_enabled')
            ->update([
                'type' => 'enum',
                'type_params' => 'reference:ED',
            ]);
    }
};
