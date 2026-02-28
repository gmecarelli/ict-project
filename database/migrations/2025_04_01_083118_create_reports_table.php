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
        Schema::disableForeignKeyConstraints();

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->unsigned();
            $table->string('title')->nullable();
            $table->string('route', 150)->comment('Nome della rotta della chiamata');
            $table->string('table', 150)->nullable();
            $table->string('blade', 150)->default('report')->comment('File view/blade dove viene visualizzato il report');
            $table->string('sum')->nullable();
            $table->integer('position')->default(10);
            $table->string('where_condition', 100)->nullable();
            $table->string('group_by', 150)->nullable();
            $table->string('href_url')->nullable();
            $table->string('href_target')->default('_self');
            $table->string('include_file')->default('_self');
            $table->integer('has_create_button')->default(0)->comment('Indica se deve essere presente il pulsante di inserimento nuovo record. Valori da 0 a 100 (service.login.role)');
            $table->integer('has_edit_button')->default(0)->comment('Indica se devono essere presenti i pulsanti di modifica. Valori da 0 a 100 (service.login.role)');
            $table->string('class_delete_button')->nullable();
            $table->boolean('has_multiselect')->default(0);
            $table->boolean('is_enabled')->default(1);
            $table->boolean('is_show_menu')->default(1);
            $table->boolean('is_editable')->default(1);
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
        });

        // Inserimento dei dati iniziali
        DB::table('reports')->insert([
            [
                'id' => 1,
                'menu_id' => 1,
                'title' => 'Configura Menu',
                'route' => 'menu',
                'table' => 'menus',
                'blade' => 'report',
                'sum' => null,
                'position' => 10,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/menu',
                'href_target' => '_self',
                'include_file' => '',
                'has_create_button' => 1,
                'has_edit_button' => 1,
                'class_delete_button' => 'destroy',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2021-06-01 12:28:32',
                'updated_at' => '2023-08-31 09:45:13'
            ],
            [
                'id' => 2,
                'menu_id' => 1,
                'title' => 'Configura Report',
                'route' => 'report',
                'table' => 'reports',
                'blade' => 'report',
                'sum' => null,
                'position' => 15,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/report',
                'href_target' => '_self',
                'include_file' => '',
                'has_create_button' => 1,
                'has_edit_button' => 1,
                'class_delete_button' => 'destroy',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2021-06-01 12:28:32',
                'updated_at' => '2021-07-21 08:44:40'
            ],
            [
                'id' => 3,
                'menu_id' => 1,
                'title' => 'Conf. colonne Report',
                'route' => 'reportcol',
                'table' => 'report_columns',
                'blade' => 'report',
                'sum' => null,
                'position' => 15,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/reportcol',
                'href_target' => '_self',
                'include_file' => '',
                'has_create_button' => 0,
                'has_edit_button' => 1,
                'class_delete_button' => 'destroy',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2021-06-01 12:28:32',
                'updated_at' => '2021-07-21 08:42:43'
            ],
            [
                'id' => 4,
                'menu_id' => 1,
                'title' => 'Configura form',
                'route' => 'form',
                'table' => 'forms',
                'blade' => 'report',
                'sum' => null,
                'position' => 50,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/form',
                'href_target' => '_self',
                'include_file' => '_self',
                'has_create_button' => 1,
                'has_edit_button' => 1,
                'class_delete_button' => 'destroy',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2021-07-12 10:41:10',
                'updated_at' => '2023-08-31 13:55:27'
            ],
            [
                'id' => 5,
                'menu_id' => 1,
                'title' => 'Conf. Form Fields',
                'route' => 'formfield',
                'table' => 'form_fields',
                'blade' => 'report',
                'sum' => null,
                'position' => 55,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/formfield',
                'href_target' => '_self',
                'include_file' => '_self',
                'has_create_button' => 0,
                'has_edit_button' => 1,
                'class_delete_button' => 'destroy',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2021-07-12 11:24:51',
                'updated_at' => '2021-12-20 11:28:51'
            ],
            [
                'id' => 6,
                'menu_id' => 2,
                'title' => 'Profili utenti',
                'route' => 'profiles',
                'table' => 'profiles',
                'blade' => 'report',
                'sum' => null,
                'position' => 25,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/profiles',
                'href_target' => '_self',
                'include_file' => '_self',
                'has_create_button' => 1,
                'has_edit_button' => 1,
                'class_delete_button' => 'cancel',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2022-02-26 11:11:29',
                'updated_at' => '2022-03-16 15:11:29'
            ],
            [
                'id' => 7,
                'menu_id' => 2,
                'title' => 'Dettaglio ruoli profilo',
                'route' => 'roles',
                'table' => 'profile_roles',
                'blade' => 'report',
                'sum' => null,
                'position' => 30,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/roles',
                'href_target' => '_self',
                'include_file' => '_self',
                'has_create_button' => 0,
                'has_edit_button' => 1,
                'class_delete_button' => 'cancel',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2022-02-26 15:29:10',
                'updated_at' => '2022-03-16 15:11:19'
            ],
            [
                'id' => 8,
                'menu_id' => 1,
                'title' => 'Parametri di utilità',
                'route' => 'options',
                'table' => 'options',
                'blade' => 'report',
                'sum' => null,
                'position' => 10,
                'where_condition' => null,
                'group_by' => null,
                'href_url' => '/options',
                'href_target' => '_self',
                'include_file' => '_self',
                'has_create_button' => 1,
                'has_edit_button' => 1,
                'class_delete_button' => 'destroy',
                'has_multiselect' => 0,
                'is_enabled' => 1,
                'is_show_menu' => 1,
                'is_editable' => 1,
                'created_at' => '2023-08-31 07:31:20',
                'updated_at' => '2023-08-31 07:40:42'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
