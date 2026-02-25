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
        Schema::create('multiselect_actions', function (Blueprint $table) {
            $table->id();
            $table->string('label', 70);
            $table->string('reference', 45)->comment('Stringa referente per determinare in quale report deve apparire la voce');
            $table->string('route')->nullable()->comment('Route della funzione personalizzata per l\'esecuzione dell\'action');
            $table->string('table', 70)->nullable()->comment('Tabella di riferimento per l\'aggiornamento massivo di uno o più campi');
            $table->string('set')->nullable()->comment('coppie campo/valore del SET della query');
            $table->string('where')->nullable()->comment('coppie campo/valore della WHERE della query');
            $table->string('raw')->nullable()->comment('Stringa query personalizzata');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multiselect_actions');
    }
};
