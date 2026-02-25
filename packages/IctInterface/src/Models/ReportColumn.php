<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportColumn extends IctModel
{
    use HasFactory;

    public $where = []; //Variabile necessaria dove vengono memorizzati eventuali variabili di filtraggio
    protected $guarded = ['form_id', 'report', 'id'];

    /**
     * Relationship: relazione 1/n inversa report_columns -> report
     */
    public function report()
    {
        return $this->belongsTo('Packages\IctInterface\Models\Report');
    }


}
