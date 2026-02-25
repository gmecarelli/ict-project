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
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name_surname', 200)->index();
            $table->string('email', 100);
            $table->date('born_at');
            $table->string('born_country', 100);
            $table->tinyInteger('is_enabled')->default(1);
            $table->unique(['name_surname', 'email'], 'authors_name_email_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
