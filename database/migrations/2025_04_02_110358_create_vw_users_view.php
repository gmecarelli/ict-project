<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateVwUsersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW `vw_users` AS
            SELECT 
                `service`.`login`.`id` AS `id`,
                `service`.`login`.`login` AS `email`,
                `service`.`login`.`password` AS `password`,
                CONCAT_WS(' ',
                        `service`.`login`.`nome`,
                        `service`.`login`.`cognome`) AS `name`,
                `service`.`login`.`role` AS `role`,
                `service`.`login`.`status` AS `status`,
                `service`.`login`.`enabled` AS `is_enabled`,
                `service`.`login`.`email` AS `posta_elettronica`,
                `service`.`login`.`profilo_procter` AS `profilo_procter`
            FROM
                `service`.`login`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS `vw_users`");
    }
}
