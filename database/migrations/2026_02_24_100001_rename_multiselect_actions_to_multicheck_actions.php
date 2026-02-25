<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('multiselect_actions', 'multicheck_actions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('multicheck_actions', 'multiselect_actions');
    }
};
