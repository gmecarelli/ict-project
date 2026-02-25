<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IctModel extends Model
{
    use HasFactory;
    public $where = []; //Variabile necessaria dove vengono memorizzati eventuali variabili di filtraggio
    protected $guarded = ['form_id', 'report', 'id'];

    /**
     * setTable
     * Imposta il nome della tabella di riferimento del model
     * @param  mixed $value
     * @return void
     */
    public function setTable($value) {
        $this->table = $value;
    }

    /**
     * getTableName
     * Restituisce il nome della tabella di riferimento del model
     * @param  mixed $value
     * @return void
     */
    public function getTableName() {
        return $this->table;
    }
}
