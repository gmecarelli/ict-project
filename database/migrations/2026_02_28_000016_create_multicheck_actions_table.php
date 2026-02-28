<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multicheck_actions', function (Blueprint $table) {
            $table->id();
            $table->string('label', 70);
            $table->string('reference', 45)->comment('Stringa referente per determinare in quale report deve apparire la voce');
            $table->string('route')->nullable()->comment('Route della funzione personalizzata per l\'esecuzione dell\'action');
            $table->string('table', 70)->nullable()->comment('Tabella di riferimento per l\'aggiornamento massivo di uno o più campi');
            $table->string('set')->nullable()->comment('coppie campo/valore del SET della query');
            $table->string('where')->nullable()->comment('coppie campo/valore della WHERE della query');
            $table->string('raw')->nullable()->comment('Stringa query personalizzata');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Seed data
        DB::table('multicheck_actions')->insert([
            [
                'id' => 1,
                'label' => 'Elimina voce',
                'reference' => 'PUTY',
                'route' => 'delete.attachments',
                'table' => null,
                'set' => null,
                'where' => null,
                'raw' => null,
                'created_at' => '2026-02-24 14:53:25',
                'updated_at' => '2026-02-24 14:53:25',
            ],
            [
                'id' => 2,
                'label' => 'Disabilita',
                'reference' => 'PUTY',
                'route' => null,
                'table' => 'options',
                'set' => '{"is_enabled":0}',
                'where' => null,
                'raw' => null,
                'created_at' => '2026-02-24 14:53:25',
                'updated_at' => '2026-02-24 14:53:25',
            ],
        ]);

    }

    public function down(): void
    {
        Schema::dropIfExists('multicheck_actions');
    }
};
